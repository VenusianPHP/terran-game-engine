<?php
declare(strict_types=1);
namespace Terran;

use Closure;
use Surface\Contracts\Drawing\OutputTarget;
use Surface\Contracts\Drawing\RenderingEngine;
use Surface\Contracts\Windows\StagedWindow;
use Surface\NutsAndBolts\Affine;
use Surface\NutsAndBolts\Color;
use Terran\Scenes\TwoD\Camera2D;
use Terran\Scenes\ThreeD\Camera3D;
use Terran\Scenes\ThreeD\Scene3D;
use Terran\Scenes\TwoD\Scene2D;
use Terran\Scenes\UI\UIControl;

/**
 * One output target and the rendering engine Surface gave it. Each render()
 * first checks the target's pixel size: zero (a window not shown or
 * minimised) skips the frame; a size the engine was not made for re-mints the
 * engine through $remint, since an engine draws into the framebuffer it was
 * made over. Then it draws, inside one engine frame, a 2D pass (every visible
 * Scene2D and plain Scene by effective z, through the current Camera2D's view)
 * and a UI pass (top-level UIControls laid out at this viewport's size, drawn
 * by effective z outside any camera) and presents. A throwing draw drops the
 * frame and skips present().
 */
final class Viewport
{
    /** @param (Closure(OutputTarget): RenderingEngine)|null $remint */
    public function __construct(
        public readonly string $name,
        protected readonly OutputTarget $target,
        protected RenderingEngine $engine,
        protected readonly Color $background,
        protected readonly ?Closure $remint = null,
        protected readonly ?string $window = null,
    ) {}

    protected ?Camera2D $camera2d = null;

    protected ?Camera3D $camera3d = null;

    /** A camera takes the slot of its kind; null clears both. */
    public function setCamera(Camera2D|Camera3D|null $camera): static
    {
        if (is_null($camera)) {
            [$this->camera2d, $this->camera3d] = [null, null];
        } elseif ($camera instanceof Camera3D) {
            $this->camera3d = $camera;
        } else {
            $this->camera2d = $camera;
        }

        return $this;
    }

    public function camera2d(): ?Camera2D { return $this->camera2d; }

    public function camera3d(): ?Camera3D { return $this->camera3d; }

    /** Unbind $camera if it is this viewport's. */
    public function release(Camera2D|Camera3D $camera): static
    {
        if ($this->camera2d === $camera) {
            $this->camera2d = null;
        }
        if ($this->camera3d === $camera) {
            $this->camera3d = null;
        }

        return $this;
    }

    /** World space → this viewport's pixels: the current Camera2D's view, else identity. */
    public function view2d(): Affine
    {
        [$width, $height] = $this->target->pixelSize();

        return is_null($this->camera2d) ? Affine::identity() : $this->camera2d->view($width, $height);
    }

    /**
     * A framebuffer pixel to 2D world space, through the inverse view.
     *
     * @return array{float, float}
     */
    public function toWorld(float $x, float $y): array
    {
        $inverse = $this->view2d()->inverse() ?? throw new TerranException("Viewport '{$this->name}' has a view that flattens the plane, so its pixels have no world position.");

        return $inverse->apply($x, $y);
    }

    public function target(): OutputTarget { return $this->target; }

    public function engine(): RenderingEngine { return $this->engine; }

    /** The Surface window this target is, when Terran opened one; mouse events match on it. */
    public function window(): ?string { return $this->window; }

    /** @return array{int, int} */
    public function size(): array { return $this->target->pixelSize(); }

    public function render(Scene $root): void
    {
        [$width, $height] = $this->target->pixelSize();
        if ($width < 1 || $height < 1) {
            return;
        }
        if ($width !== $this->engine->width() || $height !== $this->engine->height()) {
            if (is_null($this->remint)) {
                throw new TerranException("Viewport '{$this->name}' changed size from {$this->engine->width()}x{$this->engine->height()} to {$width}x{$height} and has no remint closure to make an engine for it; pass one to World::addViewport().");
            }
            $this->engine = ($this->remint)($this->target);
        }

        $this->engine->frame(function (RenderingEngine $ge) use ($root): void {
            $ge->clear($this->background);
            $view = $this->view2d();
            foreach ($this->pass2d($root) as $scene) {
                $ge->push();
                $ge->transform($scene instanceof Scene2D ? $view->multiply($scene->globalTransform()) : $view);
                $scene->draw($ge);
                $ge->pop();
            }
            $this->passUi($root, $ge);
        });

        $this->target->present();
    }

    /**
     * Every visible Scene2D, and every plain Scene at z 0 with an identity own
     * transform, by effective z then tree order.
     *
     * @return list<Scene>
     */
    protected function pass2d(Scene $root): array
    {
        $items = [];
        foreach ($root->walk() as $order => $scene) {
            if ($scene instanceof UIControl || $scene instanceof Scene3D || ! $scene->visibleInTree()) {
                continue;
            }
            $items[] = [$scene instanceof Scene2D ? $scene->effectiveZ() : 0, $order, $scene];
        }
        usort($items, fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return array_column($items, 2);
    }

    /** Lay out every top-level UIControl at this viewport's size, then draw the visible ones by effective z then tree order, each from its rect's top-left. */
    protected function passUi(Scene $root, RenderingEngine $ge): void
    {
        $top = $root instanceof UIControl ? [$root] : UIControl::nearestControls($root);
        foreach ($top as $control) {
            $control->layout([0.0, 0.0, (float) $ge->width(), (float) $ge->height()], $this->name, $ge);
        }
        $items = [];
        foreach ($root->walk() as $order => $scene) {
            if ($scene instanceof UIControl && $scene->visibleInTree()) {
                $items[] = [$scene->effectiveZ(), $order, $scene];
            }
        }
        usort($items, fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
        foreach ($items as [, , $control]) {
            [$x, $y] = $control->rect($this->name);
            $ge->push();
            $ge->translate($x, $y);
            $control->drawIn($ge, $this->name);
            $ge->pop();
        }
    }

    /**
     * Window coordinates (as HumanInput's Mouse reports them) to this target's
     * framebuffer pixels. A staged window's points scale by pixels per point;
     * other targets are taken one to one.
     *
     * @return array{float, float}
     */
    public function toPixels(float $x, float $y): array
    {
        [$across, $down] = $this->pixelsPerPoint();

        return [$x * $across, $y * $down];
    }

    /**
     * Framebuffer pixels per window point, across and down: pixelSize / size on
     * a staged window, 1 on other targets or a window not yet sized.
     *
     * @return array{float, float}
     */
    public function pixelsPerPoint(): array
    {
        if (! $this->target instanceof StagedWindow) {
            return [1.0, 1.0];
        }
        [$points_across, $points_down] = $this->target->size();
        [$pixels_across, $pixels_down] = $this->target->pixelSize();
        if ($points_across < 1 || $points_down < 1) {
            return [1.0, 1.0];
        }

        return [$pixels_across / $points_across, $pixels_down / $points_down];
    }
}
