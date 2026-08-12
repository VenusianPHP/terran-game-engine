<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\Shapes\Shape2D;

class ShapeCast2D extends Node2D
{
    protected ?Shape2D $shape = null;
    public float $targetX = 0.0;
    public float $targetY = 50.0;
    public bool $enabled = true;
    protected bool $colliding = false;

    public function setShape(?Shape2D $shape): static
    {
        $this->shape = $shape;

        return $this;
    }

    public function isColliding(): bool
    {
        return $this->colliding;
    }

    /** @param  list<CollisionObject2D>  $candidates */
    public function forceShapecastUpdate(array $candidates): void
    {
        $this->colliding = false;
        $w = is_null($this->shape) ? 8 : (int) $this->shape->width();
        $h = is_null($this->shape) ? 8 : (int) $this->shape->height();
        $x = $this->globalX() + $this->targetX;
        $y = $this->globalY() + $this->targetY;

        foreach ($candidates as $body) {
            if ($x < $body->globalX() + $body->width
                && $x + $w > $body->globalX()
                && $y < $body->globalY() + $body->height
                && $y + $h > $body->globalY()) {
                $this->colliding = true;

                return;
            }
        }
    }
}