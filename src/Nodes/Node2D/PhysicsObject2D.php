<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Physics\PhysicsBodyState;

/**
 * Moving AABB body with velocity + sub-pixel remainder (PhysicsServer snapshot).
 */
class PhysicsObject2D extends PhysicsBody2D
{
    public int $id = 0;

    public float $vx = 0.0;

    public float $vy = 0.0;

    public float $remX = 0.0;

    public float $remY = 0.0;

    /**
     * @return array{id: int, x: int, y: int, w: int, h: int, vx: float, vy: float, rem_x: float, rem_y: float}
     */
    public function exportState(): array
    {
        return PhysicsBodyState::make(
            id: $this->id,
            x: (int) round($this->positionX),
            y: (int) round($this->positionY),
            w: $this->width,
            h: $this->height,
            vx: $this->vx,
            vy: $this->vy,
            rem_x: $this->remX,
            rem_y: $this->remY,
        );
    }

    /**
     * @param  array{id: int, x: int, y: int, w: int, h: int, vx: float, vy: float, rem_x: float, rem_y: float}  $state
     */
    public function importState(array $state): void
    {
        $this->id = (int) $state['id'];
        $this->positionX = (float) $state['x'];
        $this->positionY = (float) $state['y'];
        $this->width = (int) $state['w'];
        $this->height = (int) $state['h'];
        $this->vx = (float) $state['vx'];
        $this->vy = (float) $state['vy'];
        $this->remX = (float) $state['rem_x'];
        $this->remY = (float) $state['rem_y'];
    }
}
