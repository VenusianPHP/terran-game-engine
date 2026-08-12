<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class LinkButton extends BaseButton
{
    public string $text = '';

    public int $underlineColor = 0xFF4EA1FF;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 64, 16));
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        if ($this->text === '') {
            return;
        }

        $ctx->printLocal(0, 0, $this->text, $this->underlineColor);
        $w = 6 * strlen($this->text);
        $ctx->drawLineLocal(0, 9, max(0, $w - 1), 9, $this->underlineColor);
    }
}
