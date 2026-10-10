<?php
declare(strict_types=1);
namespace Terran\Scenes\UI;

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\NutsAndBolts\Color;
use Terran\TerranException;

/** A bar filled left to right by (value − min) / (max − min); value is clamped to min..max when set. */
class ProgressBar extends UIControl
{
    public float $min = 0.0;

    public float $max = 100.0;

    public float $value = 0.0 {
        set(float $value) {
            $this->value = max($this->min, min($this->max, $value));
        }
    }

    public Color $fillColor;

    public Color $backColor;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->fillColor = Color::hex('#4CAF50');
        $this->backColor = Color::hex('#222222');
        $this->mouseFilter = MouseFilter::IGNORE;
    }

    public function ratio(): float
    {
        if ($this->max <= $this->min) {
            throw new TerranException("ProgressBar '{$this->name}' needs max above min; got min {$this->min}, max {$this->max}.");
        }

        return max(0.0, min(1.0, ($this->value - $this->min) / ($this->max - $this->min)));
    }

    public function draw(RenderingEngine $ge): void
    {
        $size = $this->size();
        $ge->fillRect(0.0, 0.0, $size->x, $size->y, $this->tint($this->backColor));
        $ge->fillRect(0.0, 0.0, $size->x * $this->ratio(), $size->y, $this->tint($this->fillColor));
    }
}
