<?php

namespace ScrapyardIO\GameEngine\Input\Events;

class InputEventMouseButton extends InputEvent
{
    public function __construct(
        public readonly string $button,
        public readonly bool $pressed = true,
        public readonly int $x = 0,
        public readonly int $y = 0,
    ) {}
}
