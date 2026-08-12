<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

class PathFollow2D extends Node2D
{
    public float $progress = 0.0;
    public float $progressRatio = 0.0;
    public bool $rotates = true;

    public function process(float $dt): void
    {
        $parent = $this->parent();

        if (! $parent instanceof Path2D || is_null($parent->curve())) {
            return;
        }

        $point = $parent->curve()->sample($this->progressRatio);
        $this->positionX = $point[0];
        $this->positionY = $point[1];
    }
}