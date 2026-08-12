<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Geometry\Rect;

class GraphFrame extends GraphElement
{
    public string $title = '';

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 160, 100));
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }
}
