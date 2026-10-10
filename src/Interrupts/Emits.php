<?php
declare(strict_types=1);
namespace Terran\Interrupts;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use Terran\Scene;
use Terran\TerranException;

/**
 * Godot's signals, on a Scene. #[Interrupt] declares what a class emits; the
 * declarations of every parent class count too, a child's winning on a name.
 * emit() runs the Scene's connections in the order they were made, then, when
 * the Scene is live, dispatches a SceneInterrupt on the World's signals.
 */
trait Emits
{
    /** @var array<class-string, array<string, list<string>>> */
    private static array $declared = [];

    /** @var array<string, list<array{0: callable|object, 1: ?string}>> */
    private array $connections = [];

    /** @return array<string, list<string>> name → payload types */
    public static function interrupts(): array
    {
        if (isset(self::$declared[static::class])) {
            return self::$declared[static::class];
        }

        $out = [];
        for ($class = new ReflectionClass(static::class); $class !== false; $class = $class->getParentClass()) {
            foreach ($class->getAttributes(Interrupt::class) as $attribute) {
                $interrupt = $attribute->newInstance();
                $out[$interrupt->name] ??= $interrupt->payload;
            }
        }

        return self::$declared[static::class] = $out;
    }

    public function emit(string $name, mixed ...$payload): void
    {
        $types = $this->declaredOrThrow($name);
        $this->checkPayload($name, $types, array_values($payload));

        // Runs a snapshot, so a handler may connect, disconnect or emit again; dead targets are pruned from the live list after.
        foreach ($this->connections[$name] ?? [] as [$target, $method]) {
            if (self::targetsFreedScene($target)) {
                continue;
            }
            is_null($method) ? $target(...$payload) : $target->{$method}(...$payload);
        }
        if (isset($this->connections[$name])) {
            $this->connections[$name] = array_values(array_filter(
                $this->connections[$name],
                fn (array $connection): bool => ! self::targetsFreedScene($connection[0]),
            ));
        }

        $signals = $this->world()?->signals();
        if (! is_null($signals)) {
            $signals->dispatch(new SceneInterrupt($this, $name, array_values($payload)));
        }
    }

    public function connect(string $name, callable|object $target, ?string $method = null): static
    {
        $this->declaredOrThrow($name);
        if (! is_null($method) && ! method_exists($target, $method)) {
            throw new TerranException((new ReflectionClass($target))->getShortName()." has no method '{$method}'.");
        }
        if (is_null($method) && ! is_callable($target)) {
            throw new TerranException('connect() takes a callable, or an object and the name of its method; got '.get_debug_type($target).' alone.');
        }

        $this->connections[$name][] = [$target, $method];

        return $this;
    }

    public function disconnect(string $name, callable|object $target, ?string $method = null): static
    {
        $this->connections[$name] = array_values(array_filter(
            $this->connections[$name] ?? [],
            fn (array $connection): bool => ! (self::sameTarget($connection[0], $target) && $connection[1] === $method),
        ));

        return $this;
    }

    /** The World frees this Scene: its own connections go with it. */
    protected function severConnections(): void
    {
        $this->connections = [];
    }

    /** A Scene target, an array callable on one ([$scene, 'method']), or a closure bound to one ($scene->method(...)), that was freed. */
    private static function targetsFreedScene(callable|object $target): bool
    {
        if (is_array($target)) {
            $target = $target[0];
        }
        if ($target instanceof Closure) {
            $target = (new ReflectionFunction($target))->getClosureThis();
        }

        return $target instanceof Scene && $target->freed();
    }

    /** Identity, except that two closures made from one method of one object ($scene->onHit(...)) are the same target. */
    private static function sameTarget(callable|object $a, callable|object $b): bool
    {
        return $a === $b || ($a instanceof Closure && $b instanceof Closure && $a == $b);
    }

    /** @return list<string> */
    private function declaredOrThrow(string $name): array
    {
        $declared = static::interrupts();
        if (! array_key_exists($name, $declared)) {
            $class = (new ReflectionClass($this))->getShortName();
            throw new TerranException($declared === []
                ? "{$class} does not emit '{$name}'. It emits nothing; declare #[Interrupt('{$name}')] on the class."
                : "{$class} does not emit '{$name}'. It emits: ".implode(', ', array_keys($declared)).'.');
        }

        return $declared[$name];
    }

    /** @param list<string> $types @param list<mixed> $payload */
    private function checkPayload(string $name, array $types, array $payload): void
    {
        $class = (new ReflectionClass($this))->getShortName();
        if (count($payload) !== count($types)) {
            throw new TerranException("{$class}'s '{$name}' carries ".count($types).' value'.(count($types) === 1 ? '' : 's').' ('.implode(', ', $types).'); emit() gave '.count($payload).'.');
        }
        foreach ($types as $i => $type) {
            if (! self::matches($payload[$i], $type)) {
                throw new TerranException("{$class}'s '{$name}' takes {$type} as value ".($i + 1).'; got '.get_debug_type($payload[$i]).'.');
            }
        }
    }

    private static function matches(mixed $value, string $type): bool
    {
        if (str_starts_with($type, '?')) {
            return is_null($value) || self::matches($value, substr($type, 1));
        }

        return match ($type) {
            'mixed' => true,
            'int', 'float', 'string', 'bool', 'array', 'null' => get_debug_type($value) === $type,
            'object' => is_object($value),
            'callable' => is_callable($value),
            default => $value instanceof $type,
        };
    }
}
