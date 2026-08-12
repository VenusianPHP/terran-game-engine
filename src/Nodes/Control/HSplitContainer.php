<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Geometry\Rect;

class HSplitContainer extends SplitContainer
{
    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect);
        $this->vertical = false;
    }
}
