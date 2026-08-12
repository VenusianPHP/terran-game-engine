<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Enums\Axis;

class HSlider extends Slider
{
    public function __construct(float $value = 0.0)
    {
        parent::__construct($value, Axis::HORIZONTAL);
    }
}
