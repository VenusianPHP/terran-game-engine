<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Resources\ImageTexture;
use ScrapyardIO\GameEngine\Resources\Texture;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class TextureRect extends Control
{
    protected ?Texture $texture = null;

    public bool $expandMode = true;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 32, 32));
    }

    public function setTexture(?Texture $texture): static
    {
        $this->texture = $texture;

        if (! is_null($texture) && $this->expandMode) {
            $this->setSize($texture->width(), $texture->height());
        }

        return $this;
    }

    public function texture(): ?Texture
    {
        return $this->texture;
    }

    protected function draw(PaintContext $ctx): void
    {
        if (is_null($this->texture)) {
            return;
        }

        $r = $this->rect();
        $color = $this->texture instanceof ImageTexture ? $this->texture->fillColor() : 0xFFFFFFFF;
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), $color);
    }
}
