<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Signals\Signal;

#[Signal('pressed')]
#[Signal('released')]
class TouchScreenButton extends Node2D
{
    public string $action = '';
    protected bool $pressed = false;

    public function setPressed(bool $pressed): static
    {
        if ($pressed === $this->pressed) {
            return $this;
        }

        $this->pressed = $pressed;
        $this->emit($pressed ? 'pressed' : 'released');

        return $this;
    }

    public function isPressed(): bool
    {
        return $this->pressed;
    }
}