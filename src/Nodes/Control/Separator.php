<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Geometry\Rect;

class Separator extends Control
{
    public int $color = 0xFF888888;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 32, 2));
    }
}
