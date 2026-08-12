<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class HSeparator extends Separator
{
    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 64, 2));
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $y = intdiv(max(1, $r->height), 2);
        $ctx->drawLineLocal(0, $y, max(0, $r->width - 1), $y, $this->color);
    }
}
