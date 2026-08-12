<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\UIComponent;

class FoldableContainer extends Container
{
    public string $title = '';

    public bool $folded = false;

    public function setFolded(bool $folded): static
    {
        $this->folded = $folded;

        foreach ($this->children() as $child) {
            if ($child instanceof UIComponent) {
                $child->setVisible(! $folded);
            }
        }

        return $this;
    }

    public function isFolded(): bool
    {
        return $this->folded;
    }
}
