<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

class AspectRatioContainer extends Container
{
    public float $ratio = 1.0;

    public function layout(Size $available): void
    {
        if ($this->rect()->width <= 0 || $this->rect()->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        $w = $this->rect()->width;
        $h = $this->rect()->height;
        $targetW = $w;
        $targetH = (int) round($w / max(0.0001, $this->ratio));

        if ($targetH > $h) {
            $targetH = $h;
            $targetW = (int) round($h * $this->ratio);
        }

        foreach ($this->children() as $child) {
            if ($child instanceof UIComponent && $child->isVisible()) {
                $child->setSize($targetW, $targetH);
                $child->setPosition(
                    intdiv(max(0, $this->rect()->width - $targetW), 2),
                    intdiv(max(0, $this->rect()->height - $targetH), 2),
                );
                $child->layout(new Size($targetW, $targetH));
            }
        }
    }
}
