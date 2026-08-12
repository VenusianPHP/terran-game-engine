<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

class FlowContainer extends Container
{
    public bool $vertical = false;

    public int $hSeparation = 4;

    public int $vSeparation = 4;

    public function layout(Size $available): void
    {
        if ($this->rect()->width <= 0 || $this->rect()->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        $x = 0;
        $y = 0;
        $lineExtent = 0;

        foreach ($this->children() as $child) {
            if (! $child instanceof UIComponent || ! $child->isVisible()) {
                continue;
            }

            $size = $child->size();
            $w = max(1, $size->width);
            $h = max(1, $size->height);

            if (! $this->vertical && $x > 0 && ($x + $w) > $this->rect()->width) {
                $x = 0;
                $y += $lineExtent + $this->vSeparation;
                $lineExtent = 0;
            } elseif ($this->vertical && $y > 0 && ($y + $h) > $this->rect()->height) {
                $y = 0;
                $x += $lineExtent + $this->hSeparation;
                $lineExtent = 0;
            }

            $child->setPosition($x, $y);
            $child->layout($size);

            if ($this->vertical) {
                $y += $h + $this->vSeparation;
                $lineExtent = max($lineExtent, $w);
            } else {
                $x += $w + $this->hSeparation;
                $lineExtent = max($lineExtent, $h);
            }
        }
    }
}
