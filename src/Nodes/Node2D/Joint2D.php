<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

abstract class Joint2D extends Node2D
{
    protected ?PhysicsBody2D $nodeA = null;
    protected ?PhysicsBody2D $nodeB = null;

    public function setNodeA(?PhysicsBody2D $body): static
    {
        $this->nodeA = $body;

        return $this;
    }

    public function setNodeB(?PhysicsBody2D $body): static
    {
        $this->nodeB = $body;

        return $this;
    }
}