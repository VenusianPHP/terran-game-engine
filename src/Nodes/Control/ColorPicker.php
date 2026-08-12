<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

#[Signal('color_changed', ['color' => 'int'])]
class ColorPicker extends Control
{
    protected int $color = 0xFFFFFFFF;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 120, 120));
    }

    public function setColor(int $color): static
    {
        if ($color === $this->color) {
            return $this;
        }

        $this->color = $color;
        $this->emit('color_changed', ['color' => $this->color]);

        return $this;
    }

    public function color(): int
    {
        return $this->color;
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), $this->color);
    }
}
