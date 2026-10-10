<?php
declare(strict_types=1);
namespace Terran;

use Closure;
use Surface\Contracts\Drawing\OutputTarget;
use Surface\Contracts\Drawing\RenderingEngine;
use Surface\NutsAndBolts\Color;
use Terran\Autoload\AutoloadRegistry;
use Terran\Input\Actions;
use Terran\Input\Events\InputEvent;
use Terran\Input\InputMap;
use Voyager\Contracts\Signals\SignalDispatcher;
use Voyager\IOPools\Deferrals\Task;

/**
 * The tree's owner. setRoot() makes the tree live: every Scene is attached to
 * the GameLoop (ready(), then physics steps) parent before child. Scenes added
 * to or removed from a live tree report here. tick() is the render-side hook
 * pass; render() draws every Viewport.
 */
class World
{
    public const string DEFAULT_BACKGROUND = '#000000';

    protected ?Scene $root = null;

    /** @var array<string, Viewport> */
    protected array $viewports = [];

    /** @var array<class-string, bool> */
    private static array $overrides_input = [];

    /** @param array<class-string, ?string> $autoload_classes terran.autoloads: class → alias */
    public function __construct(
        protected readonly GameLoop $loop,
        protected readonly ?SignalDispatcher $signals = null,
        protected readonly ?AutoloadRegistry $autoloads = null,
        protected readonly array $autoload_classes = [],
        protected readonly ?InputMap $actions = null,
    ) {
        if (! is_null($actions)) {
            $latch = $actions->physics();
            $loop->afterStep(fn () => $latch->clear());
        }
    }

    public function loop(): GameLoop { return $this->loop; }

    public function signals(): ?SignalDispatcher { return $this->signals; }

    public function autoloads(): ?AutoloadRegistry { return $this->autoloads; }

    /** @return array<class-string, ?string> */
    public function worldAutoloads(): array { return $this->autoload_classes; }

    public function inputMap(): ?InputMap { return $this->actions; }

    /** Every source with no seat, or one seat's; the physics latch while physicsProcess() runs. */
    public function actions(?int $seat = null): Actions
    {
        $map = $this->actions ?? throw new TerranException('This World has no InputMap. TerranServiceProvider binds one from terran.input.actions; a World built by hand takes one as its fifth argument.');
        $view = $this->loop->inPhysics() ? $map->physics() : $map;

        return is_null($seat) ? $view : $view->seat($seat);
    }

    /** Whether any live Scene overrides input(). */
    public function wantsInput(): bool
    {
        if (is_null($this->root)) {
            return false;
        }
        foreach ($this->root->walk() as $scene) {
            $class = $scene::class;
            self::$overrides_input[$class] ??= (new \ReflectionMethod($scene, 'input'))->getDeclaringClass()->getName() !== Scene::class;
            if (self::$overrides_input[$class]) {
                return true;
            }
        }

        return false;
    }

    /** @param (Closure(OutputTarget): RenderingEngine)|null $remint makes an engine over the target after it changes size */
    public function addViewport(string $name, OutputTarget $target, RenderingEngine $engine, ?Color $background = null, ?Closure $remint = null, ?string $window = null): Viewport
    {
        if (isset($this->viewports[$name])) {
            throw new TerranException("World already has a viewport named '{$name}'.");
        }

        return $this->viewports[$name] = new Viewport($name, $target, $engine, $background ?? Color::hex(self::DEFAULT_BACKGROUND), $remint, $window);
    }

    public function viewportForWindow(?string $window): ?Viewport
    {
        if (is_null($window)) {
            return null;
        }
        foreach ($this->viewports as $viewport) {
            if ($viewport->window() === $window) {
                return $viewport;
            }
        }

        return null;
    }

    /** @return array<string, Viewport> */
    public function viewports(): array { return $this->viewports; }

    public function setRoot(Scene $root): static
    {
        if (! is_null($this->root)) {
            throw new TerranException("World already has a root ('{$this->root->name}'). destroy() it first.");
        }

        $this->root = $root;
        $root->attachedTo($this);
        $this->resolveAutoloads($this->autoload_classes, $root, 'terran.autoloads');
        $this->wentLive($root);

        return $this;
    }

    public function root(): ?Scene { return $this->root; }

    /** Work that waits on I/O, run as a loop fiber; take the result with ->then(). */
    public function async(callable $body): Task
    {
        return $this->loop->async($body);
    }

    /** @param list<InputEvent> $events the frame's input, from InputReader */
    public function tick(array $events, float $delta): void
    {
        $root = $this->liveRoot();
        $this->loop->hooks(function () use ($root, $events, $delta): void {
            // A Scene removed earlier in a walk has exited; it gets no further hooks.
            foreach ($events as $event) {
                foreach ($root->walk() as $scene) {
                    if (! $scene->freed() && $scene->input($event)) {
                        break;
                    }
                }
                $this->signals?->dispatch($event);
            }
            foreach ($root->walk() as $scene) {
                if (! $scene->freed()) {
                    $scene->process($delta);
                }
            }
        });
    }

    public function render(): void
    {
        $root = $this->liveRoot();
        foreach ($this->viewports as $viewport) {
            $viewport->render($root);
        }
    }

    public function wentLive(Scene $subtree): void
    {
        $subtree->attachedTo($this);
        foreach ($subtree->walk() as $scene) {
            $declared = $scene::declaredAutoloads();
            if ($declared !== []) {
                $this->resolveAutoloads($declared, $scene, (new \ReflectionClass($scene))->getShortName());
            }
        }
        foreach ($subtree->walk() as $scene) {
            // a parent's ready() may have removed it
            if (! $scene->freed()) {
                $this->loop->attach($scene);
            }
        }
    }

    public function wentDead(Scene $subtree): void
    {
        foreach ($subtree->walk() as $scene) {
            $this->loop->detach($scene);
        }
        $subtree->attachedTo(null);
    }

    public function destroy(): void
    {
        if (! is_null($this->root)) {
            $this->wentDead($this->root);
            $this->root = null;
        }
        $this->viewports = [];
        $this->autoloads?->forget();
    }

    /**
     * Resolve each class now; a Scene class joins the root under its alias, so
     * it is attached (and readied) before the Scenes that declared it.
     *
     * @param array<class-string, ?string> $classes
     * @param string $source who asked: terran.autoloads or the declaring class, for error messages
     */
    protected function resolveAutoloads(array $classes, Scene $for, string $source): void
    {
        if ($classes === []) {
            return;
        }
        $registry = $this->autoloads ?? throw new TerranException("'{$for->name}' declares autoloads but this World has no AutoloadRegistry.");
        foreach ($classes as $class => $as) {
            $instance = $registry->get($class, $as, $source);
            if ($instance instanceof Scene && is_null($instance->parent()) && $instance !== $this->root) {
                $this->root->add($instance);
            }
        }
    }

    protected function liveRoot(): Scene
    {
        return $this->root ?? throw new TerranException('World has no root Scene. setRoot() first.');
    }
}
