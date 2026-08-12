<?php

namespace ScrapyardIO\GameEngine\Input\Events;

class InputEventKey extends InputEvent
{
    public function __construct(
        public readonly string $key,
        public readonly bool $pressed = true,
    ) {}
}
