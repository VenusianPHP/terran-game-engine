<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\Shapes\Shape2D;

class CollisionShape2D extends Node2D
{
    protected ?Shape2D $shape = null;
    public bool $disabled = false;

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