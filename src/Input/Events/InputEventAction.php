<?php

namespace ScrapyardIO\GameEngine\Input\Events;

class InputEventAction extends InputEvent
{
    public function __construct(
        public readonly string $action,
        public readonly bool $pressed = true,
        public readonly float $strength = 1.0,
    ) {}
}
