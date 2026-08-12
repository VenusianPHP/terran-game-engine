<?php

namespace ScrapyardIO\GameEngine\Resources\Shapes;

class SegmentShape2D extends Shape2D
{
    public function __construct(
        public float $aX = 0.0,
        public float $aY = 0.0,
        public float $bX = 16.0,
        public float $bY = 0.0,
    ) {}

    public function width(): float
    {
        return abs($this->bX - $this->aX);
    }

    public function height(): float
    {
        return abs($this->bY - $this->aY);
    }
}
