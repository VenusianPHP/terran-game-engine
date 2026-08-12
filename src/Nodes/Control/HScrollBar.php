<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class HScrollBar extends ScrollBar
{
    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 100, 12));
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), 0xFF222222);
        $ratio = ($this->maxValue > $this->minValue)
            ? ($this->value - $this->minValue) / ($this->maxValue - $this->minValue)
            : 0.0;
        $thumb = max(8, (int) round($r->width * max(0.1, $this->page > 0 ? $this->page / max(1.0, $this->maxValue) : 0.2)));
        $x = (int) round(max(0, $r->width - $thumb) * $ratio);
        $ctx->fillRectLocal($x, 0, $thumb, max(1, $r->height), 0xFF888888);
    }
}
