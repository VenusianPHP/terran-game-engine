<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\OccluderPolygon2D;

class LightOccluder2D extends Node2D
{
    protected ?OccluderPolygon2D $occluder = null;

    public function setOccluderPolygon(?OccluderPolygon2D $occluder): static
    {
        $this->occluder = $occluder;

        return $this;
    }
}