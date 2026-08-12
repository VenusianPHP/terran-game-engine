<?php

namespace ScrapyardIO\GameEngine\Resources\Shapes;

class RectangleShape2D extends Shape2D
{
    public function __construct(
        public float $sizeX = 16.0,
        public float $sizeY = 16.0,
    ) {}

    public function width(): float
    {
        return $this->sizeX;
    }

    public function height(): float
    {
        return $this->sizeY;
    }
}
