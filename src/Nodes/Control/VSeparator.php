<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class VSeparator extends Separator
{
    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 2, 64));
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $x = intdiv(max(1, $r->width), 2);
        $ctx->drawLineLocal($x, 0, $x, max(0, $r->height - 1), $this->color);
    }
}
