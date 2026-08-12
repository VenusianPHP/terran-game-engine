<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Point;

class Line2D extends Node2D
{
    /** @var list<array{0:float,1:float}> */
    public array $points = [];
    public int $defaultColor = 0xFFFFFFFF;
    public float $lineWidth = 1.0;

    public function addPoint(float $x, float $y): static
    {
        $this->points[] = [$x, $y];

        return $this;
    }

    protected function onDraw(PaintContext $ctx): void
    {
        $ox = (int) round($this->globalX());
        $oy = (int) round($this->globalY());
        $local = $ctx->withOrigin(new Point($ox, $oy));

        for ($i = 0; $i < count($this->points) - 1; $i++) {
            [$x0, $y0] = $this->points[$i];
            [$x1, $y1] = $this->points[$i + 1];
            $local->drawLineLocal((int) $x0, (int) $y0, (int) $x1, (int) $y1, $this->defaultColor);
        }
    }
}