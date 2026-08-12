<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Size;

#[Signal('tab_changed', ['tab' => 'int'])]
class TabContainer extends Container
{
    protected int $currentTab = 0;

    public function setCurrentTab(int $index): static
    {
        $kids = array_values(array_filter(
            $this->children(),
            static fn ($c): bool => $c instanceof UIComponent,
        ));

        if ($index < 0 || $index >= count($kids)) {
            return $this;
        }

        $this->currentTab = $index;

        foreach ($kids as $i => $child) {
            $child->setVisible($i === $index);
        }

        $this->emit('tab_changed', ['tab' => $index]);

        return $this;
    }

    public function getCurrentTab(): int
    {
        return $this->currentTab;
    }

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
}
