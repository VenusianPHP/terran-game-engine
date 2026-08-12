<?php

namespace ScrapyardIO\GameEngine\Resources\Shapes;

class WorldBoundaryShape2D extends Shape2D
{
    public function __construct(
        public float $distance = 0.0,
        public float $normalX = 0.0,
        public float $normalY = -1.0,
    ) {}

    public function width(): float
    {
        return PHP_FLOAT_MAX;
    }

    public function height(): float
    {
        return 1.0;
    }
}
