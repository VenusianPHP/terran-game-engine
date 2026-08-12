<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\TileSet;

/** @deprecated Prefer TileMapLayer children in Godot 4.5 — kept for API parity. */
class TileMap extends Node2D
{
    protected ?TileSet $tileSet = null;

    public function setTileSet(?TileSet $tileSet): static
    {
        $this->tileSet = $tileSet;

        return $this;
    }

    public function tileSet(): ?TileSet
    {
        return $this->tileSet;
    }
}