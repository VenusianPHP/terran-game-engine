<?php

namespace ScrapyardIO\GameEngine\Resources;

class TileSet
{
    public function __construct(
        public int $tileWidth = 16,
        public int $tileHeight = 16,
    ) {}
}