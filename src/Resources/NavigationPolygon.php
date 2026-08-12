<?php

namespace ScrapyardIO\GameEngine\Resources;

class NavigationPolygon
{
    /** @var list<array{0:float,1:float}> */
    public array $vertices = [];
    /** @var list<list<int>> */
    public array $polygons = [];
}