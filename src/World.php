<?php
declare(strict_types=1);
namespace Terran;

use Closure;
use Surface\Contracts\Drawing\OutputTarget;
use Surface\Contracts\Drawing\RenderingEngine;
use Surface\NutsAndBolts\Color;
use Terran\Assets\AssetCache;
use Terran\Autoload\AutoloadRegistry;
use Terran\Input\Actions;
use Terran\Input\Events\ActionPressed;
use Terran\Input\Events\InputEvent;
use Terran\Input\Events\MouseButtonPressed;
use Terran\Input\Events\MouseButtonReleased;
use Terran\Input\Events\MouseMoved;
use Terran\Input\Events\MouseWheel;
use Terran\Input\InputMap;
use Terran\Scenes\ThreeD\Camera3D;
use Terran\Scenes\TwoD\Camera2D;
use Terran\Scenes\UI\MouseFilter;
use Terran\Scenes\UI\UIControl;
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

    /** @var list<Camera2D|Camera3D> live current cameras waiting for the viewport they name */
    protected array $pending_cameras = [];

    protected ?UIControl $focused = null;

    protected ?UIControl $hovered = null;

    /** @var array<string, list<UIControl>> mouse button value → every control its press visited */
    protected array $captured = [];

    /** @var array<string, array{float, float}> viewport name ('' for none) → last pointer position */
    protected array $pointer = [];

    /** @var array<class-string, bool> */
    private static array $overrides_input = [];

    /** @param array<class-string, ?string> $autoload_classes terran.autoloads: class → alias */
    public function __construct(
        protected readonly GameLoop $loop,
        protected readonly ?SignalDispatcher $signals = null,
        protected readonly ?AutoloadRegistry $autoloads = null,
        protected readonly array $autoload_classes = [],
        protected readonly ?InputMap $actions = null,
        protected readonly ?AssetCache $assets = null,
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

    public function assets(): AssetCache
    {
        return $this->assets ?? throw new TerranException('This World has no AssetCache. TerranServiceProvider binds one as terran.assets; a World built by hand takes one as its sixth argument.');
    }

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

        $viewport = $this->viewports[$name] = new Viewport($name, $target, $engine, $background ?? Color::hex(self::DEFAULT_BACKGROUND), $remint, $window);
        foreach ($this->pending_cameras as $camera) {
            $this->bindCamera($camera);
        }

        return $viewport;
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

    /** A viewport by name; null = the first added. */
    public function viewport(?string $name): ?Viewport
    {
        if (is_null($name)) {
            return $this->viewports === [] ? null : $this->viewports[array_key_first($this->viewports)];
        }

        return $this->viewports[$name] ?? null;
    }

    /** Make a live current camera its viewport's camera of its kind, or wait for that viewport. */
    public function bindCamera(Camera2D|Camera3D $camera): void
    {
        if (! $camera->current || $camera->freed() || $camera->world() !== $this) {
            return;
        }
        $viewport = $this->viewport($camera->viewport);
        if (is_null($viewport)) {
            if (! in_array($camera, $this->pending_cameras, true)) {
                $this->pending_cameras[] = $camera;
            }

            return;
        }
        $this->pending_cameras = array_values(array_filter($this->pending_cameras, fn (Camera2D|Camera3D $c): bool => $c !== $camera));
        foreach ($this->viewports as $other) {
            if ($other !== $viewport) {
                $other->release($camera);
            }
        }
        $old = $camera instanceof Camera3D ? $viewport->camera3d() : $viewport->camera2d();
        if (! is_null($old) && $old !== $camera) {
            $old->current = false;
        }
        $viewport->setCamera($camera);
    }

    /** Unbind $camera from every viewport and stop waiting for the one it names. */
    public function releaseCamera(Camera2D|Camera3D $camera): void
    {
        foreach ($this->viewports as $viewport) {
            $viewport->release($camera);
        }
        $this->pending_cameras = array_values(array_filter($this->pending_cameras, fn (Camera2D|Camera3D $c): bool => $c !== $camera));
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
                $this->deliver($event, $root);
                $this->signals?->dispatch($event);
            }
            foreach ($root->walk() as $scene) {
                if (! $scene->freed()) {
                    $scene->process($delta);
                }
            }
        });
    }

    public function focused(): ?UIControl { return $this->focused; }

    public function focus(?UIControl $control): void
    {
        if (! is_null($control) && ! $control->focusable) {
            throw new TerranException("'{$control->name}' is not focusable; set focusable = true.");
        }
        if (! is_null($control) && $control->world() !== $this) {
            throw new TerranException("'{$control->name}' is not in this World's live tree.");
        }
        if (! is_null($control) && ! $control->visibleInTree()) {
            throw new TerranException("'{$control->name}' is hidden; show it before focusing it.");
        }
        $this->focused = $control;
    }

    public function render(): void
    {
        if ($this->viewports !== [] && $this->pending_cameras !== []) {
            $camera = $this->pending_cameras[0];
            $kind = $camera instanceof Camera3D ? 'Camera3D' : 'Camera2D';
            throw new TerranException("{$kind} '{$camera->name}' names viewport '{$camera->viewport}', which this World does not have. Viewports: ".implode(', ', array_keys($this->viewports)).'.');
        }
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
        foreach ($subtree->walk() as $scene) {
            if (($scene instanceof Camera2D || $scene instanceof Camera3D) && $scene->current && ! $scene->freed()) {
                $this->bindCamera($scene);
            }
        }
    }

    public function wentDead(Scene $subtree): void
    {
        foreach ($subtree->walk() as $scene) {
            if ($scene === $this->focused) {
                $this->focused = null;
            }
            if ($scene === $this->hovered) {
                $this->hovered = null;
            }
            foreach ($this->captured as $button => $controls) {
                $this->captured[$button] = array_values(array_filter($controls, fn (UIControl $c): bool => $c !== $scene));
            }
        }
        foreach ($subtree->walk() as $scene) {
            if ($scene instanceof Camera2D || $scene instanceof Camera3D) {
                $this->releaseCamera($scene);
            }
        }
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
        $this->pending_cameras = [];
        [$this->focused, $this->hovered, $this->captured, $this->pointer] = [null, null, [], []];
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

    /**
     * One event's walk: ui_next / ui_prev move focus first; mouse events visit
     * the controls under the pointer topmost first, a press focusing the first
     * focusable one and capturing every control it visits, a release going to
     * those captured controls first; a STOP control or a true return ends the
     * walk; other events visit the focused control first (focus is dropped
     * from a control that became hidden); then every Scene that is not a
     * UIControl, depth first, until one returns true.
     */
    protected function deliver(InputEvent $event, Scene $root): void
    {
        if ($event instanceof ActionPressed && ($event->action === 'ui_next' || $event->action === 'ui_prev')) {
            $this->moveFocus($root, $event->action === 'ui_next' ? 1 : -1);
        }
        $pointer = $this->pointerOf($event);
        if (! is_null($pointer)) {
            [$viewport, $x, $y] = $pointer;
            $controls = $this->controlsAt($root, $x, $y, $viewport);
            if ($event instanceof MouseMoved) {
                $this->hover($controls[0] ?? null);
            }
            $captured = [];
            if ($event instanceof MouseButtonReleased) {
                $captured = $this->captured[$event->button->value] ?? [];
                unset($this->captured[$event->button->value]);
                $ended = false;
                foreach ($captured as $control) {
                    if (! $control->freed() && ($control->input($event) || $control->mouseFilter === MouseFilter::STOP)) {
                        $ended = true;
                    }
                }
                if ($ended) {
                    return;
                }
            }
            if ($event instanceof MouseButtonPressed) {
                $this->captured[$event->button->value] = [];
            }
            $focused = false;
            foreach ($controls as $control) {
                if (in_array($control, $captured, true) || $control->freed()) {
                    continue;
                }
                if ($event instanceof MouseButtonPressed) {
                    $this->captured[$event->button->value][] = $control;
                    if (! $focused && $control->focusable) {
                        $this->focused = $control;
                        $focused = true;
                    }
                }
                if ($control->input($event) || $control->mouseFilter === MouseFilter::STOP) {
                    return;
                }
            }
        } elseif (! is_null($this->focused)) {
            if (! $this->focused->visibleInTree()) {
                $this->focused = null;
            } elseif (! $this->focused->freed() && $this->focused->input($event)) {
                return;
            }
        }
        foreach ($root->walk() as $scene) {
            if ($scene instanceof UIControl || $scene->freed()) {
                continue;
            }
            if ($scene->input($event)) {
                return;
            }
        }
    }

    /** @return array{?string, float, float}|null viewport and pixel position of a mouse event; a wheel takes the last pointer position (NAN, hitting nothing, before any) */
    private function pointerOf(InputEvent $event): ?array
    {
        if ($event instanceof MouseButtonPressed || $event instanceof MouseButtonReleased || $event instanceof MouseMoved) {
            $this->pointer[$event->viewport ?? ''] = [$event->x, $event->y];

            return [$event->viewport, $event->x, $event->y];
        }
        if ($event instanceof MouseWheel) {
            [$x, $y] = $this->pointer[$event->viewport ?? ''] ?? [NAN, NAN];

            return [$event->viewport, $x, $y];
        }

        return null;
    }

    /** @return list<UIControl> under the point in $viewport, mouse filter not IGNORE, topmost first */
    private function controlsAt(Scene $root, float $x, float $y, ?string $viewport): array
    {
        if (is_null($viewport)) {
            return [];
        }
        $items = [];
        foreach ($root->walk() as $order => $scene) {
            if ($scene instanceof UIControl && $scene->mouseFilter !== MouseFilter::IGNORE && $scene->hitTest($x, $y, $viewport)) {
                $items[] = [$scene->effectiveZ(), $order, $scene];
            }
        }
        usort($items, fn (array $a, array $b): int => [$b[0], $b[1]] <=> [$a[0], $a[1]]);

        return array_column($items, 2);
    }

    private function hover(?UIControl $top): void
    {
        if ($top === $this->hovered) {
            return;
        }
        if (! is_null($this->hovered) && ! $this->hovered->freed()) {
            $this->hovered->mouseExited();
        }
        $top?->mouseEntered();
        $this->hovered = $top;
    }

    private function moveFocus(Scene $root, int $direction): void
    {
        $order = array_values(array_filter($root->walk(), fn (Scene $s): bool => $s instanceof UIControl && $s->focusable && $s->visibleInTree()));
        if ($order === []) {
            return;
        }
        $at = array_search($this->focused, $order, true);
        $count = count($order);
        $this->focused = $order[$at === false ? ($direction > 0 ? 0 : $count - 1) : ($at + $direction + $count) % $count];
    }

    protected function liveRoot(): Scene
    {
        return $this->root ?? throw new TerranException('World has no root Scene. setRoot() first.');
    }
}
