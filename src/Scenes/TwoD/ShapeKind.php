<?php
declare(strict_types=1);
namespace Terran\Scenes\TwoD;

enum ShapeKind
{
    case Rect;
    case Circle;
    case Polygon;
    case Line;
}
