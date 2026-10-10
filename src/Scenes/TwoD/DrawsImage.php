<?php
declare(strict_types=1);
namespace Terran\Scenes\TwoD;

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\Contracts\Framebuffers\Framebuffer;
use Terran\Math\Vector2;

/** How Sprite2D and AnimatedSprite2D place one framebuffer: centred or from the top-left, offset, flipped in place. The using class sets $offset in its constructor. */
trait DrawsImage
{
    public bool $centered = true;

    public Vector2 $offset;

    public bool $flipH = false;

    public bool $flipV = false;

    protected function drawImage(RenderingEngine $ge, ?Framebuffer $image): void
    {
        if (is_null($image)) {
            return;
        }
        [$w, $h] = [$image->viewportWidth(), $image->viewportHeight()];
        $x = $this->offset->x - ($this->centered ? $w / 2 : 0.0);
        $y = $this->offset->y - ($this->centered ? $h / 2 : 0.0);
        if ($this->flipH || $this->flipV) {
            [$cx, $cy] = [$x + $w / 2, $y + $h / 2];
            $ge->translate($cx, $cy);
            $ge->scale($this->flipH ? -1.0 : 1.0, $this->flipV ? -1.0 : 1.0);
            $ge->translate(-$cx, -$cy);
        }
        $ge->image($image, $x, $y, null, null, $this->effectiveModulate());
    }
}
