<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class RigidBody2D extends PhysicsObject2D
{
    public float $mass = 1.0;

    public float $gravityScale = 1.0;

    protected function physicsProcess(float $delta): void
    {
        $this->vy += 980.0 * $this->gravityScale * $delta;
    }
}
