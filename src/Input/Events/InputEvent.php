<?php

namespace ScrapyardIO\GameEngine\Input\Events;

class InputEvent
{
    protected bool $handled = false;

    public function consume(): void
    {
        $this->handled = true;
    }

    public function isHandled(): bool
    {
        return $this->handled;
    }
}
