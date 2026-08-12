<?php

namespace ScrapyardIO\GameEngine\Resources\Shapes;

class CircleShape2D extends Shape2D
{
    public function __construct(
        public float $radius = 8.0,
    ) {}

    public function width(): float
    {
        return $this->radius * 2;
    }

    public function height(): float
    {
        return $this->radius * 2;
    }
}
