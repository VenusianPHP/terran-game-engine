<?php
declare(strict_types=1);
namespace Terran\Scenes\UI;

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\NutsAndBolts\Color;

/** Fills its rect, then draws its border inside it. */
class Panel extends UIControl
{
    public Color $color;

    public ?Color $borderColor = null;

    public float $borderWidth = 0.0;

    public function __construct(string $name, ?Color $color = null)
    {
        parent::__construct($name);
        $this->color = $color ?? Color::hex('#2B2B2B');
    }

    public function draw(RenderingEngine $ge): void
    {
        $size = $this->size();
        $ge->fillRect(0.0, 0.0, $size->x, $size->y, $this->tint($this->color));
        if (! is_null($this->borderColor) && $this->borderWidth > 0.0) {
            $half = $this->borderWidth / 2;
            $ge->strokeRect($half, $half, $size->x - $this->borderWidth, $size->y - $this->borderWidth, $this->tint($this->borderColor), $this->borderWidth);
        }
    }
}
