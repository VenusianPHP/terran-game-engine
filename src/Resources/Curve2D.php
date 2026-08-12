<?php

namespace ScrapyardIO\GameEngine\Resources;

class Curve2D
{
    /** @var list<array{0:float,1:float}> */
    protected array $points = [];

    public function addPoint(float $x, float $y): static
    {
        $this->points[] = [$x, $y];

        return $this;
    }

    /** @return array{0:float,1:float} */
    public function sample(float $ratio): array
    {
        if ($this->points === []) {
            return [0.0, 0.0];
        }

        $ratio = max(0.0, min(1.0, $ratio));
        $index = (int) floor($ratio * (count($this->points) - 1));

        return $this->points[$index];
    }

    public function pointCount(): int
    {
        return count($this->points);
    }
}