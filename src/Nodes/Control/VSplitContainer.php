<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Geometry\Rect;

class VSplitContainer extends SplitContainer
{
    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect);
        $this->vertical = true;
    }
}
