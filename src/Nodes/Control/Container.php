<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Rect;

abstract class Container extends Control
{
    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 64, 64));
    }

    public function fitChild(UIComponent $child): static
    {
        $child->setPosition(0, 0);
        $child->setSize($this->rect()->width, $this->rect()->height);

        return $this;
    }
}
