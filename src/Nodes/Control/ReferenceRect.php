<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class ReferenceRect extends Control
{
    public int $borderColor = 0xFFFF0000;

    public int $borderWidth = 1;

    public bool $editorOnly = true;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 32, 32));
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->editorOnly) {
            return;
        }

        $r = $this->rect();
        $ctx->drawRectLocal(0, 0, max(1, $r->width), max(1, $r->height), $this->borderColor);
    }
}
