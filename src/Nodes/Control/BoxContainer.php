<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

class BoxContainer extends Container
{
    public bool $vertical = false;

    public int $separation = 4;

    public function layout(Size $available): void
    {
        if ($this->rect()->width <= 0 || $this->rect()->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        $cursor = 0;

        foreach ($this->children() as $child) {
            if (! $child instanceof UIComponent || ! $child->isVisible()) {
                continue;
            }

            $size = $child->size();

            if ($this->vertical) {
                $child->setPosition(0, $cursor);
                $child->setSize($this->rect()->width, max(1, $size->height));
                $cursor += max(1, $size->height) + $this->separation;
            } else {
                $child->setPosition($cursor, 0);
                $child->setSize(max(1, $size->width), $this->rect()->height);
                $cursor += max(1, $size->width) + $this->separation;
            }

            $child->layout($child->size());
        }
    }
}
