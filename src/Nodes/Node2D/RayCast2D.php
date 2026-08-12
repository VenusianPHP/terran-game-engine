<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

class RayCast2D extends Node2D
{
    public float $targetX = 0.0;
    public float $targetY = 50.0;
    public bool $enabled = true;
    public bool $collideWithBodies = true;
    protected bool $colliding = false;
    protected ?CollisionObject2D $collider = null;

    public function isColliding(): bool
    {
        return $this->colliding;
    }

    public function getCollider(): ?CollisionObject2D
    {
        return $this->collider;
    }

    /**
     * @param  list<CollisionObject2D>  $candidates
     */
    public function forceRaycastUpdate(array $candidates): void
    {
        $this->colliding = false;
        $this->collider = null;

        if (! $this->enabled) {
            return;
        }

        $x0 = $this->globalX();
        $y0 = $this->globalY();
        $x1 = $x0 + $this->targetX;
        $y1 = $y0 + $this->targetY;

        foreach ($candidates as $body) {
            if ($body === $this) {
                continue;
            }

            $bx = $body->globalX();
            $by = $body->globalY();

            if (min($x0, $x1) <= $bx + $body->width
                && max($x0, $x1) >= $bx
                && min($y0, $y1) <= $by + $body->height
                && max($y0, $y1) >= $by) {
                $this->colliding = true;
                $this->collider = $body;

                return;
            }
        }
    }
}