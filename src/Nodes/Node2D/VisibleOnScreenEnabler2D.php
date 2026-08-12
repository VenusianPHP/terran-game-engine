<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class VisibleOnScreenEnabler2D extends VisibleOnScreenNotifier2D
{
    public function updateVisibility(int $viewW, int $viewH): void
    {
        parent::updateVisibility($viewW, $viewH);
        $this->setVisible($this->isOnScreen());
    }
}