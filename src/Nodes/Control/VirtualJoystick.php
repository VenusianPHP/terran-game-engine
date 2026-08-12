<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class VirtualJoystick extends Control
{
    public float $outputX = 0.0;

    public float $outputY = 0.0;

    public float $deadzone = 0.2;

    protected bool $pressed = false;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 96, 96));
    }

    public function setOutput(float $x, float $y): static
    {
        $mag = sqrt(($x * $x) + ($y * $y));

        if ($mag < $this->deadzone) {
            $this->outputX = 0.0;
            $this->outputY = 0.0;

            return $this;
        }

        if ($mag > 1.0) {
            $x /= $mag;
            $y /= $mag;
        }

        $this->outputX = $x;
        $this->outputY = $y;

        return $this;
    }

    public function setPressed(bool $pressed): static
    {
        $this->pressed = $pressed;

        if (! $pressed) {
            $this->outputX = 0.0;
            $this->outputY = 0.0;
        }

        return $this;
    }

    public function isPressed(): bool
    {
        return $this->pressed;
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $cx = intdiv($r->width, 2);
        $cy = intdiv($r->height, 2);
        $radius = min($cx, $cy) - 2;
        $ctx->drawRectLocal(0, 0, max(1, $r->width), max(1, $r->height), 0xFF555555);
        $knobX = (int) round($cx + ($this->outputX * $radius * 0.6));
        $knobY = (int) round($cy + ($this->outputY * $radius * 0.6));
        $ctx->fillRectLocal(max(0, $knobX - 4), max(0, $knobY - 4), 8, 8, 0xFFAAAAAA);
    }
}
