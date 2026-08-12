<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\Curve2D;

class Path2D extends Node2D
{
    protected ?Curve2D $curve = null;

    public function setCurve(?Curve2D $curve): static
    {
        $this->curve = $curve;

        return $this;
    }

    public function curve(): ?Curve2D
    {
        return $this->curve;
    }
}