<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Resources\ImageTexture;
use ScrapyardIO\GameEngine\Resources\Texture;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class TextureButton extends BaseButton
{
    protected ?Texture $textureNormal = null;

    protected ?Texture $texturePressed = null;

    protected ?Texture $textureDisabled = null;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 32, 32));
    }

    public function setTextureNormal(?Texture $texture): static
    {
        $this->textureNormal = $texture;

        if (! is_null($texture)) {
            $this->setSize($texture->width(), $texture->height());
        }

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        $tex = $this->disabled
            ? ($this->textureDisabled ?? $this->textureNormal)
            : ($this->buttonPressed ? ($this->texturePressed ?? $this->textureNormal) : $this->textureNormal);

        $r = $this->rect();

        if (is_null($tex)) {
            parent::draw($ctx);

            return;
        }

        $color = $tex instanceof ImageTexture ? $tex->fillColor() : 0xFFFFFFFF;
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), $color);
    }
}
