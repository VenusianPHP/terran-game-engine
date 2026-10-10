<?php
declare(strict_types=1);
namespace Terran;

use ReflectionClass;
use Surface\Contracts\Drawing\RenderingEngine;
use Terran\Autoload\Autoload;
use Terran\Input\Actions;
use Terran\Input\Events\InputEvent;
use Terran\Interrupts\Emits;

/**
 * One element of the tree, and the unit of composition: a level is a Scene
 * whose constructor adds child Scenes. Five hooks, Godot's by other names:
 * ready() once when the tree goes live, process($delta) every rendered frame,
 * physicsProcess($step) every fixed step on the GameLoop, exit() once when
 * removed, and draw($ge) when the Viewport renders this Scene.
 */
abstract class Scene
{
    use Emits;

    protected ?Scene $parent = null;

    /** @var array<string, Scene> name → child, insertion order */
    protected array $children = [];

    protected ?World $world = null;

    protected bool $freed = false;

    public function __construct(public readonly string $name) {}

    public function ready(): void {}

    public function process(float $delta): void {}

    public function physicsProcess(float $delta): void {}

    /** Godot's _input: return true when this Scene handled the event, which stops it going further down the tree. */
    public function input(InputEvent $event): bool { return false; }

    public function exit(): void {}

    public function draw(RenderingEngine $ge): void {}

    public function add(Scene $child): static
    {
        if ($child->freed) {
            throw new TerranException("'{$child->name}' was removed from its tree, and a removed Scene is not re-added. Add a new one.");
        }
        if (! is_null($child->parent)) {
            throw new TerranException("'{$child->name}' is already a child of '{$child->parent->name}'.");
        }
        if (isset($this->children[$child->name])) {
            throw new TerranException("'{$this->name}' already has a child named '{$child->name}'.");
        }

        $child->parent = $this;
        $this->children[$child->name] = $child;

        if (! is_null($this->world)) {
            $this->world->wentLive($child);
        }

        return $this;
    }

    /** Terminal: the subtree leaves the tree and the loop; it is not re-addable. */
    public function remove(Scene $child): static
    {
        if (($this->children[$child->name] ?? null) !== $child) {
            throw new TerranException("'{$child->name}' is not a child of '{$this->name}'.");
        }

        unset($this->children[$child->name]);
        $child->parent = null;

        if (! is_null($this->world)) {
            $this->world->wentDead($child);
        }

        return $this;
    }

    public function parent(): ?Scene { return $this->parent; }

    /** @return list<Scene> */
    public function children(): array { return array_values($this->children); }

    /** 'a/b/c' relative to this Scene; null when any segment is missing. */
    public function find(string $path): ?Scene
    {
        $scene = $this;
        foreach (explode('/', $path) as $segment) {
            $scene = $scene->children[$segment] ?? null;
            if (is_null($scene)) {
                return null;
            }
        }

        return $scene;
    }

    public function path(): string
    {
        return is_null($this->parent) ? $this->name : $this->parent->path().'/'.$this->name;
    }

    /** @return list<Scene> this Scene, then every descendant depth first */
    public function walk(): array
    {
        $out = [$this];
        foreach ($this->children as $child) {
            array_push($out, ...$child->walk());
        }

        return $out;
    }

    /** The World marks a subtree live, or dead with null; dead is final. Not for app code. */
    public function attachedTo(?World $world): void
    {
        foreach ($this->walk() as $scene) {
            $scene->world = $world;
            if (is_null($world)) {
                $scene->freed = true;
                $scene->severConnections();
            }
        }
    }

    public function world(): ?World { return $this->world; }

    /** @var array<class-string, array<class-string, ?string>> */
    private static array $autoload_declarations = [];

    /** @return array<class-string, ?string> class → alias, from this class and every parent */
    public static function declaredAutoloads(): array
    {
        if (isset(self::$autoload_declarations[static::class])) {
            return self::$autoload_declarations[static::class];
        }

        $out = [];
        for ($class = new ReflectionClass(static::class); $class !== false; $class = $class->getParentClass()) {
            foreach ($class->getAttributes(Autoload::class) as $attribute) {
                $autoload = $attribute->newInstance();
                $out[$autoload->class] ??= $autoload->as;
            }
        }

        return self::$autoload_declarations[static::class] = $out;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function autoload(string $class): object
    {
        $world = $this->world ?? throw new TerranException("'{$this->name}' is not in a live tree; autoloads resolve once it is.");
        $declared = static::declaredAutoloads() + $world->worldAutoloads();
        if (! array_key_exists($class, $declared)) {
            $short = (new ReflectionClass($class))->getShortName();
            throw new TerranException("'{$this->name}' does not declare #[Autoload({$short}::class)] and terran.autoloads does not list it.");
        }

        return $world->autoloads()->get($class, $declared[$class]);
    }

    /** The actions: the frame's snapshot, or the physics latch while physicsProcess() runs; every source, or one seat's. */
    public function actions(?int $seat = null): Actions
    {
        $world = $this->world ?? throw new TerranException("'{$this->name}' is not in a live tree; actions are read once it is.");

        return $world->actions($seat);
    }

    /** True once this Scene left a live tree (remove() or World::destroy()); it never comes back. */
    public function freed(): bool { return $this->freed; }
}
