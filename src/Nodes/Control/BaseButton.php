<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

#[Signal('pressed')]
#[Signal('button_down')]
#[Signal('button_up')]
#[Signal('toggled', ['pressed' => 'bool'])]
abstract class BaseButton extends Control
{
    protected bool $buttonPressed = false;

    protected bool $disabled = false;

    protected bool $toggleMode = false;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 32, 32));
    }

    public function setPressed(bool $pressed): static
    {
        if ($this->disabled) {
            return $this;
        }

        $was = $this->buttonPressed;
        $this->buttonPressed = $pressed;

        if ($pressed && ! $was) {
            $this->emit('button_down');
            $this->emit('pressed');
        }

        if (! $pressed && $was) {
            $this->emit('button_up');
        }

        if ($this->toggleMode && $pressed !== $was) {
            $this->emit('toggled', ['pressed' => $pressed]);
        }

        return $this;
    }

    public function isPressed(): bool
    {
        return $this->buttonPressed;
    }

    public function setDisabled(bool $disabled): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function setToggleMode(bool $toggle): static
    {
        $this->toggleMode = $toggle;

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $color = $this->buttonPressed ? 0xFF6666AA : 0xFF444444;
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), $color);
    }
}
