<?php

namespace ScrapyardIO\GameEngine\Resources;

class TileSetAtlasSource
{
    public function __construct(
        public ?Texture $texture = null,
        public int $textureRegionSizeX = 16,
        public int $textureRegionSizeY = 16,
    ) {}
}