<?php

namespace ScrapyardIO\GameEngine\Resources;

class OccluderPolygon2D
{
    /** @var list<array{0:float,1:float}> */
    public array $polygon = [];
    public bool $closed = true;
}