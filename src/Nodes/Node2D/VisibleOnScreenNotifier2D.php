<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Signals\Signal;

#[Signal('screen_entered')]
#[Signal('screen_exited')]
class VisibleOnScreenNotifier2D extends Node2D
{
    protected bool $onScreen = false;

    public function updateVisibility(int $viewW, int $viewH): void
    {
        $rect = $this->worldRect();
        $visible = ! is_null($rect)
            && $rect->x < $viewW
            && $rect->right() > 0
            && $rect->y < $viewH
            && $rect->bottom() > 0;

        if ($visible && ! $this->onScreen) {
            $this->onScreen = true;
            $this->emit('screen_entered');
        } elseif (! $visible && $this->onScreen) {
            $this->onScreen = false;
            $this->emit('screen_exited');
        }
    }

    public function isOnScreen(): bool
    {
        return $this->onScreen;
    }
}