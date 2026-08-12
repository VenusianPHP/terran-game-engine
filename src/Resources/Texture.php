<?php

namespace ScrapyardIO\GameEngine\Resources;

abstract class Texture
{
    abstract public function width(): int;

    abstract public function height(): int;

    /**
     * Raw RGBA8888 bytes (width * height * 4) or empty when procedural.
     */
    abstract public function rgba(): string;
}
