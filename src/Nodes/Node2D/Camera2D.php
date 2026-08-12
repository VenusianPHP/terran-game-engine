<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

class Camera2D extends Node2D
{
    public bool $enabled = true;
    public float $zoomX = 1.0;
    public float $zoomY = 1.0;
    public int $offsetX = 0;
    public int $offsetY = 0;

    public function getScreenCenter(): array
    {
        return [
            (int) round($this->globalX()) + $this->offsetX,
            (int) round($this->globalY()) + $this->offsetY,
        ];
    }
}