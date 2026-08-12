<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

class SubViewportContainer extends Container
{
    public bool $stretch = true;

    public function layout(Size $available): void
    {
        if ($this->rect()->width <= 0 || $this->rect()->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        foreach ($this->children() as $child) {
            if ($child instanceof UIComponent && $child->isVisible()) {
                if ($this->stretch) {
                    $this->fitChild($child);
                }

                $child->layout($child->size());
            }
        }
    }
}
