<?php

namespace ScrapyardIO\GameEngine\Signals;

use InvalidArgumentException;
use ReflectionClass;
use WeakMap;

/**
 * Adds connect/emit/disconnect semantics to a node-like object.
 */
trait HasSignals
{
    /** @var array<string, list<SignalConnection>> */
    private array $signal_connections = [];

    /** @var array<string, array<string, string>>|null */
    private ?array $signal_declarations = null;

    /**
     * Inbound connection index keyed by target object.
     *
     * @var WeakMap<object, list<array{source: object, signal: string}>>
     */
    private static ?WeakMap $signal_inbound_connections = null;

    /**
     * @param  object  $target  Usually a Node, but any object is allowed.
     */
    public function connect(string $signal, object $target, string|callable $handler, bool $oneshot = false): static
    {
        $connection = new SignalConnection($signal, $target, $handler, $oneshot);
        $this->signal_connections[$signal] ??= [];
        $this->signal_connections[$signal][] = $connection;

        $this->registerInboundConnection($target, $signal);

        return $this;
    }

    /**
     * @param  object  $target  Usually a Node, but any object is allowed.
     */
    public function disconnect(string $signal, object $target, ?string $handler = null): static
    {
        if (! isset($this->signal_connections[$signal])) {
            return $this;
        }

        $remaining = [];

        foreach ($this->signal_connections[$signal] as $connection) {
            $target_matches = $connection->target === $target;
            $handler_matches = is_null($handler)
                || (is_string($connection->handler) && $connection->handler === $handler);

            if ($target_matches && $handler_matches) {
                $this->unregisterInboundConnection($connection->target, $signal);
                continue;
            }

            $remaining[] = $connection;
        }

        if ($remaining === []) {
            unset($this->signal_connections[$signal]);
        } else {
            $this->signal_connections[$signal] = $remaining;
        }

        return $this;
    }

    public function disconnectAll(?string $signal = null): static
    {
        if (! is_null($signal)) {
            if (! isset($this->signal_connections[$signal])) {
                return $this;
            }

            foreach ($this->signal_connections[$signal] as $connection) {
                $this->unregisterInboundConnection($connection->target, $signal);
            }

            unset($this->signal_connections[$signal]);

            return $this;
        }

        foreach ($this->signal_connections as $registered_signal => $connections) {
            foreach ($connections as $connection) {
                $this->unregisterInboundConnection($connection->target, $registered_signal);
            }
        }

        $this->signal_connections = [];

        return $this;
    }

    /**
     * @param  array<int|string, mixed>  $args
     */
    public function emit(string $signal, array $args = []): void
    {
        $this->assertSignalIsDeclared($signal);

        $ordered_args = $this->normalizeSignalArgs($signal, $args);
        $connections = $this->signal_connections[$signal] ?? [];

        foreach ($connections as $connection) {
            if (is_string($connection->handler)) {
                if (! method_exists($connection->target, $connection->handler)) {
                    throw new InvalidArgumentException(sprintf(
                        'Signal "%s" handler "%s" does not exist on %s.',
                        $signal,
                        $connection->handler,
                        $connection->target::class,
                    ));
                }

                $connection->target->{$connection->handler}(...$ordered_args);
            } else {
                ($connection->handler)(...$ordered_args);
            }

            if ($connection->oneshot) {
                $this->removeConnectionInstance($signal, $connection);
            }
        }
    }

    public function hasConnections(string $signal): bool
    {
        return ! empty($this->signal_connections[$signal] ?? []);
    }

    /**
     * Clears outbound and inbound signal links for this object.
     * Intended to run during node teardown.
     */
    public function cleanupSignalConnectionsOnExit(): void
    {
        $this->disconnectAll();
        $this->disconnectInboundConnections();
    }

    private function assertSignalIsDeclared(string $signal): void
    {
        $declared = $this->declaredSignals();

        if (! array_key_exists($signal, $declared)) {
            throw new InvalidArgumentException(sprintf(
                'Signal "%s" is not declared on %s.',
                $signal,
                static::class,
            ));
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function declaredSignals(): array
    {
        if (! is_null($this->signal_declarations)) {
            return $this->signal_declarations;
        }

        $signals = [];
        $reflection = new ReflectionClass($this);

        do {
            foreach ($reflection->getAttributes(Signal::class) as $attribute) {
                /** @var Signal $definition */
                $definition = $attribute->newInstance();
                $signals[$definition->name] ??= $definition->args;
            }
            $reflection = $reflection->getParentClass();
        } while ($reflection !== false);

        $this->signal_declarations = $signals;

        return $this->signal_declarations;
    }

    /**
     * @param  array<int|string, mixed>  $args
     * @return list<mixed>
     */
    private function normalizeSignalArgs(string $signal, array $args): array
    {
        $expected = $this->declaredSignals()[$signal];

        if ($expected === []) {
            if ($args !== []) {
                throw new InvalidArgumentException(sprintf(
                    'Signal "%s" expects no arguments, received %d.',
                    $signal,
                    count($args),
                ));
            }

            return [];
        }

        $ordered = [];

        if (array_is_list($args)) {
            if (count($args) !== count($expected)) {
                throw new InvalidArgumentException(sprintf(
                    'Signal "%s" expects %d argument(s): %s.',
                    $signal,
                    count($expected),
                    $this->formatSignalSignature($signal, $expected),
                ));
            }

            $index = 0;
            foreach ($expected as $name => $type) {
                $value = $args[$index];
                $this->assertSignalArgType($signal, $name, $type, $value);
                $ordered[] = $value;
                $index++;
            }

            return $ordered;
        }

        foreach ($expected as $name => $type) {
            if (! array_key_exists($name, $args)) {
                throw new InvalidArgumentException(sprintf(
                    'Signal "%s" is missing argument "%s". Signature: %s.',
                    $signal,
                    $name,
                    $this->formatSignalSignature($signal, $expected),
                ));
            }

            $value = $args[$name];
            $this->assertSignalArgType($signal, $name, $type, $value);
            $ordered[] = $value;
        }

        $unexpected = array_diff(array_keys($args), array_keys($expected));
        if ($unexpected !== []) {
            throw new InvalidArgumentException(sprintf(
                'Signal "%s" received unexpected argument(s): %s.',
                $signal,
                implode(', ', $unexpected),
            ));
        }

        return $ordered;
    }

    /**
     * @param  array<string, string>  $expected
     */
    private function formatSignalSignature(string $signal, array $expected): string
    {
        $parts = [];
        foreach ($expected as $name => $type) {
            $parts[] = sprintf('%s: %s', $name, $type);
        }

        return sprintf('%s(%s)', $signal, implode(', ', $parts));
    }

    private function assertSignalArgType(string $signal, string $name, string $type, mixed $value): void
    {
        if ($this->isTypeMatch($type, $value)) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Signal "%s" argument "%s" expects type "%s", got "%s".',
            $signal,
            $name,
            $type,
            get_debug_type($value),
        ));
    }

    private function isTypeMatch(string $type, mixed $value): bool
    {
        foreach (explode('|', $type) as $candidate) {
            $candidate = trim($candidate);
            $nullable = str_starts_with($candidate, '?');

            if ($nullable) {
                $candidate = ltrim($candidate, '?');
            }

            if (is_null($value) && ($nullable || $candidate === 'null')) {
                return true;
            }

            if ($candidate === 'mixed') {
                return true;
            }

            if ($candidate === 'int' && is_int($value)) {
                return true;
            }

            if ($candidate === 'float' && is_float($value)) {
                return true;
            }

            if ($candidate === 'string' && is_string($value)) {
                return true;
            }

            if ($candidate === 'bool' && is_bool($value)) {
                return true;
            }

            if ($candidate === 'array' && is_array($value)) {
                return true;
            }

            if ($candidate === 'callable' && is_callable($value)) {
                return true;
            }

            if ($candidate === 'object' && is_object($value)) {
                return true;
            }

            if ((interface_exists($candidate) || class_exists($candidate)) && is_object($value) && is_a($value, $candidate)) {
                return true;
            }
        }

        return false;
    }

    private function registerInboundConnection(object $target, string $signal): void
    {
        self::$signal_inbound_connections ??= new WeakMap;
        self::$signal_inbound_connections[$target] ??= [];
        self::$signal_inbound_connections[$target][] = [
            'source' => $this,
            'signal' => $signal,
        ];
    }

    private function unregisterInboundConnection(object $target, string $signal): void
    {
        if (is_null(self::$signal_inbound_connections) || ! isset(self::$signal_inbound_connections[$target])) {
            return;
        }

        $remaining = [];
        $removed = false;

        foreach (self::$signal_inbound_connections[$target] as $entry) {
            if (! $removed && $entry['source'] === $this && $entry['signal'] === $signal) {
                $removed = true;
                continue;
            }

            $remaining[] = $entry;
        }

        if ($remaining === []) {
            unset(self::$signal_inbound_connections[$target]);
        } else {
            self::$signal_inbound_connections[$target] = $remaining;
        }
    }

    private function disconnectInboundConnections(): void
    {
        if (is_null(self::$signal_inbound_connections) || ! isset(self::$signal_inbound_connections[$this])) {
            return;
        }

        $inbound = self::$signal_inbound_connections[$this];
        unset(self::$signal_inbound_connections[$this]);

        foreach ($inbound as $entry) {
            $source = $entry['source'];
            $signal = $entry['signal'];

            if (! method_exists($source, 'disconnect')) {
                continue;
            }

            $source->disconnect($signal, $this);
        }
    }

    private function removeConnectionInstance(string $signal, SignalConnection $needle): void
    {
        if (! isset($this->signal_connections[$signal])) {
            return;
        }

        $remaining = [];

        foreach ($this->signal_connections[$signal] as $connection) {
            if ($connection === $needle) {
                $this->unregisterInboundConnection($connection->target, $signal);
                continue;
            }

            $remaining[] = $connection;
        }

        if ($remaining === []) {
            unset($this->signal_connections[$signal]);
        } else {
            $this->signal_connections[$signal] = $remaining;
        }
    }
}
