<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

class SplitContainer extends Container
{
    public bool $vertical = false;

    public float $splitOffset = 0.5;

    public int $separatorThickness = 4;

    public function layout(Size $available): void
    {
        if ($this->rect()->width <= 0 || $this->rect()->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        $kids = [];

        foreach ($this->children() as $child) {
            if ($child instanceof UIComponent && $child->isVisible()) {
                $kids[] = $child;
            }
        }

        if ($kids === []) {
            return;
        }

        $ratio = max(0.05, min(0.95, $this->splitOffset));

        if (count($kids) === 1) {
            $this->fitChild($kids[0]);
            $kids[0]->layout(new Size($this->rect()->width, $this->rect()->height));

            return;
        }

        if ($this->vertical) {
            $first = (int) round(($this->rect()->height - $this->separatorThickness) * $ratio);
            $second = max(0, $this->rect()->height - $this->separatorThickness - $first);
            $kids[0]->setPosition(0, 0);
            $kids[0]->setSize($this->rect()->width, $first);
            $kids[0]->layout(new Size($this->rect()->width, $first));
            $kids[1]->setPosition(0, $first + $this->separatorThickness);
            $kids[1]->setSize($this->rect()->width, $second);
            $kids[1]->layout(new Size($this->rect()->width, $second));
        } else {
            $first = (int) round(($this->rect()->width - $this->separatorThickness) * $ratio);
            $second = max(0, $this->rect()->width - $this->separatorThickness - $first);
            $kids[0]->setPosition(0, 0);
            $kids[0]->setSize($first, $this->rect()->height);
            $kids[0]->layout(new Size($first, $this->rect()->height));
            $kids[1]->setPosition($first + $this->separatorThickness, 0);
            $kids[1]->setSize($second, $this->rect()->height);
            $kids[1]->layout(new Size($second, $this->rect()->height));
        }
    }
}
