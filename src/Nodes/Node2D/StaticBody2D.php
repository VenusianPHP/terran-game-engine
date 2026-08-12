<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

/**
 * Immovable collision body (vx/vy forced to 0 each export).
 */
class StaticBody2D extends PhysicsObject2D
{
    public function exportState(): array
    {
        $this->vx = 0.0;
        $this->vy = 0.0;

        return parent::exportState();
    }
}
