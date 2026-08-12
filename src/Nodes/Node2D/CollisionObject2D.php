<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;
use ScrapyardIO\GameEngine\Resources\Shapes\Shape2D;
use ScrapyardIO\GameEngine\Signals\Signal;

#[Signal('body_entered', ['body' => 'object'])]
#[Signal('body_exited', ['body' => 'object'])]
#[Signal('area_entered', ['area' => 'object'])]
#[Signal('area_exited', ['area' => 'object'])]
abstract class CollisionObject2D extends Node2D
{
    protected ?Shape2D $shape = null;

    public int $collisionLayer = 1;

    public int $collisionMask = 1;

    public function setShape(?Shape2D $shape): static
    {
        $this->shape = $shape;

        if (! is_null($shape)) {
            $this->width = (int) ceil($shape->width());
            $this->height = (int) ceil($shape->height());
        }

        return $this;
    }

    public function shape(): ?Shape2D
    {
        return $this->shape;
    }
}
