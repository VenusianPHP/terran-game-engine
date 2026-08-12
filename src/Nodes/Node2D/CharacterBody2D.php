<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class CharacterBody2D extends PhysicsObject2D
{
    public function setVelocity(float $vx, float $vy): static
    {
        $this->vx = $vx;
        $this->vy = $vy;

        return $this;
    }

    public function velocity(): array
    {
        return [$this->vx, $this->vy];
    }
}
