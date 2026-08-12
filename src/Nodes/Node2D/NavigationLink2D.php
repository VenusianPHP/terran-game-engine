<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

class NavigationLink2D extends Node2D
{
    public float $startX = 0.0;
    public float $startY = 0.0;
    public float $endX = 0.0;
    public float $endY = 0.0;
    public bool $enabled = true;
}