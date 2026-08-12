<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Point;

class Polygon2D extends Node2D
{
    /** @var list<array{0:float,1:float}> */
    public array $polygon = [];
    public int $color = 0xFFFFFFFF;

    protected function onDraw(PaintContext $ctx): void
    {
        if (count($this->polygon) < 3) {
            return;
        }

        $xs = array_column($this->polygon, 0);
        $ys = array_column($this->polygon, 1);
        $minX = (int) floor(min($xs));
        $minY = (int) floor(min($ys));
        $maxX = (int) ceil(max($xs));
        $maxY = (int) ceil(max($ys));

        $ctx->withOrigin(new Point((int) round($this->globalX()), (int) round($this->globalY())))
            ->fillRectLocal($minX, $minY, max(1, $maxX - $minX), max(1, $maxY - $minY), $this->color);
    }
}