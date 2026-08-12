<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

class PanelContainer extends Container
{
    public int $panelColor = 0xFF2A2A2A;

    public function layout(Size $available): void
    {
        if ($this->rect()->width <= 0 || $this->rect()->height <= 0) {
            $this->setSize($available->width, $available->height);
        }

        foreach ($this->children() as $child) {
            if ($child instanceof UIComponent && $child->isVisible()) {
                $this->fitChild($child);
                $child->layout(new Size($this->rect()->width, $this->rect()->height));
            }
        }
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), $this->panelColor);
    }
}
