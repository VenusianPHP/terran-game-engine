<?php

namespace ScrapyardIO\GameEngine\Nodes;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\UX\Core\Drawable;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

/**
 * Visible engine node — UX Drawable + Godot lifecycle.
 *
 * Subclasses implement {@see draw()} (UX) / may override {@see onDraw()}.
 */
abstract class CanvasItem extends Drawable implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;

    protected int $zIndex = 0;

    public function zIndex(): int
    {
        return $this->zIndex;
    }

    public function setZIndex(int $zIndex): static
    {
        $this->zIndex = $zIndex;

        return $this;
    }

    public function worldRect(): ?Rect
    {
        return null;
    }

    protected function draw(PaintContext $ctx): void
    {
        $this->onDraw($ctx);
    }

    /**
     * Godot-style draw hook (pixels via PaintContext / Renderer2D).
     */
    protected function onDraw(PaintContext $ctx): void
    {
        //
    }
}
