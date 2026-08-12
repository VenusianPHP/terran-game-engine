<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\TileSet;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Point;

class TileMapLayer extends Node2D
{
    protected ?TileSet $tileSet = null;
    /** @var array<string, int> */
    protected array $cells = [];

    public function setTileSet(?TileSet $tileSet): static
    {
        $this->tileSet = $tileSet;

        return $this;
    }

    public function setCell(int $x, int $y, int $sourceId = 0): static
    {
        $this->cells["{$x},{$y}"] = $sourceId;

        return $this;
    }

    public function getCell(int $x, int $y): int
    {
        return $this->cells["{$x},{$y}"] ?? -1;
    }

    protected function onDraw(PaintContext $ctx): void
    {
        if (is_null($this->tileSet)) {
            return;
        }

        $tw = $this->tileSet->tileWidth;
        $th = $this->tileSet->tileHeight;
        $local = $ctx->withOrigin(new Point((int) round($this->globalX()), (int) round($this->globalY())));

        foreach ($this->cells as $key => $sourceId) {
            [$cx, $cy] = array_map('intval', explode(',', $key));
            $local->fillRectLocal($cx * $tw, $cy * $th, $tw, $th, 0xFF888888);
        }

        unset($sourceId);
    }
}