<?php

namespace ScrapyardIO\GameEngine\Nodes;

use ScrapyardIO\UX\Geometry\Rect;

/**
 * 2D transform node (position, rotation, scale) — Godot Node2D.
 */
class Node2D extends CanvasItem
{
    public float $positionX = 0.0;

    public float $positionY = 0.0;

    public float $rotation = 0.0;

    public float $scaleX = 1.0;

    public float $scaleY = 1.0;

    public int $width = 0;

    public int $height = 0;

    public function setPosition(float $x, float $y): static
    {
        $this->positionX = $x;
        $this->positionY = $y;

        return $this;
    }

    public function globalX(): float
    {
        $x = $this->positionX;
        $ancestor = $this->parent();

        while (! is_null($ancestor)) {
            if ($ancestor instanceof Node2D) {
                $x += $ancestor->positionX;
            }

            $ancestor = $ancestor->parent();
        }

        return $x;
    }

    public function globalY(): float
    {
        $y = $this->positionY;
        $ancestor = $this->parent();

        while (! is_null($ancestor)) {
            if ($ancestor instanceof Node2D) {
                $y += $ancestor->positionY;
            }

            $ancestor = $ancestor->parent();
        }

        return $y;
    }

    public function moveLocal(float $dx, float $dy): static
    {
        $this->positionX += $dx;
        $this->positionY += $dy;

        return $this;
    }

    public function worldRect(): ?Rect
    {
        if ($this->width <= 0 || $this->height <= 0) {
            return null;
        }

        return new Rect(
            (int) round($this->globalX()),
            (int) round($this->globalY()),
            $this->width,
            $this->height,
        );
    }
}
