<?php
declare(strict_types=1);
namespace Terran\Scenes;

use Surface\NutsAndBolts\Color;

/**
 * What 2D and UI Scenes share: z order relative to the parent, visibility
 * that hides the subtree (every Scene under it, of any kind; see
 * Scene::visibleInTree()), and opacity multiplied down the chain. The z and
 * modulate chain is each class's own kind of parent (canvasParent()).
 */
trait CanvasItem
{
    public int $zIndex = 0;

    public bool $visible = true;

    /** 0..1, multiplied by every ancestor's. */
    public float $modulate = 1.0;

    abstract protected function canvasParent(): ?self;

    public function effectiveZ(): int
    {
        $parent = $this->canvasParent();

        return $this->zIndex + (is_null($parent) ? 0 : $parent->effectiveZ());
    }

    public function hidden(): bool { return ! $this->visible; }

    public function effectiveModulate(): float
    {
        $parent = $this->canvasParent();

        return max(0.0, min(1.0, $this->modulate)) * (is_null($parent) ? 1.0 : $parent->effectiveModulate());
    }

    /** $color with its alpha multiplied by the effective modulate. */
    protected function tint(Color $color): Color
    {
        return new Color($color->red, $color->green, $color->blue, $color->alpha * $this->effectiveModulate());
    }
}
