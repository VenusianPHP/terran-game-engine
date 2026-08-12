<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Resources\Texture;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class TextureProgressBar extends Range
{
    protected ?Texture $textureUnder = null;

    protected ?Texture $textureProgress = null;

    protected ?Texture $textureOver = null;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 100, 16));
    }

    public function setTextureProgress(?Texture $texture): static
    {
        $this->textureProgress = $texture;

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $ratio = ($this->maxValue > $this->minValue)
            ? ($this->value - $this->minValue) / ($this->maxValue - $this->minValue)
            : 0.0;
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), 0xFF333333);
        $fill = max(1, (int) round($r->width * $ratio));
        $ctx->fillRectLocal(0, 0, $fill, max(1, $r->height), 0xFF4EA1FF);
    }
}
