<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Geometry\Rect;

class GraphElement extends Control
{
    public bool $selected = false;

    public bool $draggable = true;

    public float $positionOffsetX = 0.0;

    public float $positionOffsetY = 0.0;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 120, 80));
    }
}
