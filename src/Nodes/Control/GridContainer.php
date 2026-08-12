<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

class GridContainer extends Container
{
    public int $columns = 2;

    public int $hSeparation = 4;

    public int $vSeparation = 4;

    public function layout(Size $available): void
    {
        if ($this->rect()->width <= 0 || $this->rect()->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        $cols = max(1, $this->columns);
        $index = 0;
        $cellW = max(1, intdiv($this->rect()->width - ($this->hSeparation * ($cols - 1)), $cols));
        $rowH = 16;

        foreach ($this->children() as $child) {
            if (! $child instanceof UIComponent || ! $child->isVisible()) {
                continue;
            }

            $col = $index % $cols;
            $row = intdiv($index, $cols);
            $child->setPosition($col * ($cellW + $this->hSeparation), $row * ($rowH + $this->vSeparation));
            $child->setSize($cellW, $rowH);
            $child->layout(new Size($cellW, $rowH));
            $index++;
        }
    }
}
