<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

class RemoteTransform2D extends Node2D
{
    protected ?Node2D $remote = null;
    public bool $updatePosition = true;
    public bool $updateRotation = true;
    public bool $updateScale = true;

    public function setRemote(?Node2D $remote): static
    {
        $this->remote = $remote;

        return $this;
    }

    public function process(float $dt): void
    {
        if (is_null($this->remote)) {
            return;
        }

        if ($this->updatePosition) {
            $this->remote->positionX = $this->globalX();
            $this->remote->positionY = $this->globalY();
        }

        if ($this->updateRotation) {
            $this->remote->rotation = $this->rotation;
        }

        if ($this->updateScale) {
            $this->remote->scaleX = $this->scaleX;
            $this->remote->scaleY = $this->scaleY;
        }
    }
}