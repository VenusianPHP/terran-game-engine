<?php
declare(strict_types=1);
namespace Terran\Autoload;

use ReflectionClass;
use Terran\Scene;
use Terran\TerranException;
use Voyager\Contracts\Vessel\TheServiceContainer;

/** One instance per autoloaded class for a World's lifetime. Services come from the container; Scene classes are built under their alias. */
final class AutoloadRegistry
{
    /** @var array<class-string, object> */
    private array $instances = [];

    public function __construct(private readonly TheServiceContainer $app) {}

    /**
     * @param class-string $class
     * @param string $source who asks (a declaring class or terran.autoloads), named when the alias conflicts
     */
    public function get(string $class, ?string $as = null, string $source = 'terran.autoloads'): object
    {
        if (! is_subclass_of($class, Scene::class)) {
            return $this->instances[$class] ??= $this->app->make($class);
        }

        $short = (new ReflectionClass($class))->getShortName();
        $name = $as ?? strtolower($short);
        $held = $this->instances[$class] ?? null;
        // A removed Scene autoload is gone for good; the next Scene that needs one gets a new one.
        if ($held instanceof Scene && ! $held->freed()) {
            if ($held->name !== $name) {
                throw new TerranException("{$short} autoloads once per World and is already '{$held->name}'; {$source} asks for it as '{$name}'. Give every #[Autoload({$short}::class)] the same alias.");
            }

            return $held;
        }

        return $this->instances[$class] = new $class($name);
    }

    public function has(string $class): bool { return isset($this->instances[$class]); }

    /** @return array<class-string, object> */
    public function all(): array { return $this->instances; }

    public function forget(): void { $this->instances = []; }
}
