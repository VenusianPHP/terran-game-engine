<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

class CollisionPolygon2D extends Node2D
{
    /** @var list<array{0:float,1:float}> */
    public array $polygon = [];
    public bool $disabled = false;
}