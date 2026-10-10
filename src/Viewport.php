<?php
declare(strict_types=1);
namespace Terran;

use Closure;
use Surface\Contracts\Drawing\OutputTarget;
use Surface\Contracts\Drawing\RenderingEngine;
use Surface\Contracts\Windows\StagedWindow;
use Surface\NutsAndBolts\Color;

/**
 * One output target and the rendering engine Surface gave it. Each render()
 * first checks the target's pixel size: zero (a window not shown or
 * minimised) skips the frame; a size the engine was not made for re-mints the
 * engine through $remint, since an engine draws into the framebuffer it was
 * made over. Then it draws the tree depth first inside one engine frame and
 * presents. A throwing draw drops the frame and skips present().
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
            foreach ($root->walk() as $scene) {
                $scene->draw($ge);
            }
        });

        $this->target->present();
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
