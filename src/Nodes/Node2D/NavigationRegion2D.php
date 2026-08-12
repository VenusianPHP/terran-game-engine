<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\NavigationPolygon;

class NavigationRegion2D extends Node2D
{
    protected ?NavigationPolygon $navigationPolygon = null;
    public bool $enabled = true;

    public function setNavigationPolygon(?NavigationPolygon $polygon): static
    {
        $this->navigationPolygon = $polygon;

        return $this;
    }
}