<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class SpinBox extends Range
{
    public string $prefix = '';

    public string $suffix = '';

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 80, 24));
    }

    public function getLineEditText(): string
    {
        return $this->prefix.$this->value.$this->suffix;
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), 0xFF1A1A1A);
        $ctx->printLocal(4, 4, $this->getLineEditText(), 0xFFFFFFFF);
    }
}
