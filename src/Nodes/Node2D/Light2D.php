<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

abstract class Light2D extends Node2D
{
    public bool $enabled = true;
    public int $color = 0xFFFFFFFF;
    public float $energy = 1.0;
}