<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

#[Signal('value_changed', ['value' => 'float'])]
#[Signal('changed')]
abstract class Range extends Control
{
    protected float $minValue = 0.0;

    protected float $maxValue = 100.0;

    protected float $value = 0.0;

    protected float $step = 1.0;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 100, 16));
    }

    public function setValue(float $value): static
    {
        $clamped = max($this->minValue, min($this->maxValue, $value));

        if ($this->step > 0) {
            $clamped = round($clamped / $this->step) * $this->step;
        }

        if ($clamped === $this->value) {
            return $this;
        }

        $this->value = $clamped;
        $this->emit('value_changed', ['value' => $this->value]);

        return $this;
    }

    public function setValueNoSignal(float $value): static
    {
        $this->value = max($this->minValue, min($this->maxValue, $value));

        return $this;
    }

    public function value(): float
    {
        return $this->value;
    }

    public function setMin(float $min): static
    {
        $this->minValue = $min;
        $this->emit('changed');

        return $this;
    }

    public function setMax(float $max): static
    {
        $this->maxValue = $max;
        $this->emit('changed');

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $ratio = ($this->maxValue > $this->minValue)
            ? ($this->value - $this->minValue) / ($this->maxValue - $this->minValue)
            : 0.0;
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), 0xFF333333);
        $ctx->fillRectLocal(0, 0, max(1, (int) round($r->width * $ratio)), max(1, $r->height), 0xFF66CCAA);
    }
}
