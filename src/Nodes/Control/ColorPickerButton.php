<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Signals\Signal;

#[Signal('color_changed', ['color' => 'int'])]
class ColorPickerButton extends BaseButton
{
    protected int $color = 0xFFFFFFFF;

    public function setPickColor(int $color): static
    {
        $this->color = $color;
        $this->emit('color_changed', ['color' => $this->color]);

        return $this;
    }

    public function pickColor(): int
    {
        return $this->color;
    }
}
