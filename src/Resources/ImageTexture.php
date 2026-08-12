<?php

namespace ScrapyardIO\GameEngine\Resources;

/**
 * CPU texture — solid color or loaded RGBA blob (via ResourceStore).
 */
class ImageTexture extends Texture
{
    public function __construct(
        protected int $width,
        protected int $height,
        protected string $rgba = '',
        protected int $fillColor = 0xFFFFFFFF,
    ) {
        if ($this->rgba === '') {
            $this->rgba = $this->solidRgba($fillColor);
        }
    }

    public static function solid(int $width, int $height, int $rgba = 0xFFFFFFFF): self
    {
        return new self($width, $height, '', $rgba);
    }

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }

    public function rgba(): string
    {
        return $this->rgba;
    }

    public function fillColor(): int
    {
        return $this->fillColor;
    }

    protected function solidRgba(int $color): string
    {
        $r = ($color >> 24) & 0xFF;
        $g = ($color >> 16) & 0xFF;
        $b = ($color >> 8) & 0xFF;
        $a = $color & 0xFF;
        $pixel = pack('C4', $r, $g, $b, $a);

        return str_repeat($pixel, max(0, $this->width * $this->height));
    }
}
