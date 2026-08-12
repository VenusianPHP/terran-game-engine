<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

class NavigationObstacle2D extends Node2D
{
    /** @var list<array{0:float,1:float}> */
    public array $vertices = [];
    public bool $avoidanceEnabled = true;
}