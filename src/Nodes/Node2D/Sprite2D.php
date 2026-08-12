<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;
use ScrapyardIO\GameEngine\Resources\ImageTexture;
use ScrapyardIO\GameEngine\Resources\Texture;
use ScrapyardIO\UX\Core\PaintContext;

class Sprite2D extends Node2D
{
    protected ?Texture $texture = null;

    public function setTexture(?Texture $texture): static
    {
        $this->texture = $texture;

        if (! is_null($texture)) {
            $this->width = $texture->width();
            $this->height = $texture->height();
        }

        return $this;
    }

    public function texture(): ?Texture
    {
        return $this->texture;
    }

    protected function onDraw(PaintContext $ctx): void
    {
        if (is_null($this->texture)) {
            return;
        }

        $color = 0xFFFFFFFF;

        if ($this->texture instanceof ImageTexture) {
            $color = $this->texture->fillColor();
        }

        $origin = new \ScrapyardIO\UX\Geometry\Point(
            (int) round($this->globalX()),
            (int) round($this->globalY()),
        );

        $ctx->withOrigin($origin)->fillRectLocal(0, 0, $this->width, $this->height, $color);
    }
}
