<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Geometry\Rect;

class VFlowContainer extends FlowContainer
{
    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect);
        $this->vertical = true;
    }
}
