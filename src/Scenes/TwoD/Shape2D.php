<?php
declare(strict_types=1);
namespace Terran\Scenes\TwoD;

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\NutsAndBolts\Color;
use Terran\Math\Vector2;
use Terran\TerranException;

/**
 * A rect or circle centred on the origin, a polygon or a line, filled then
 * stroked. Built through rect(), circle(), polygon() or line(), which return
 * static so a subclass builds the same way.
 */
class Shape2D extends Scene2D
{
    public ?Color $fill = null;

    public ?Color $stroke = null;

    public float $strokeWidth = 1.0;

    /** @param array<string, mixed> $params */
    final protected function __construct(string $name, private readonly ShapeKind $kind, private readonly array $params)
    {
        parent::__construct($name);
    }

    public static function rect(string $name, float $width, float $height): static
    {
        self::positive($name, ['width' => $width, 'height' => $height]);

        return new static($name, ShapeKind::Rect, ['w' => $width, 'h' => $height]);
    }

    public static function circle(string $name, float $radius): static
    {
        self::positive($name, ['radius' => $radius]);

        return new static($name, ShapeKind::Circle, ['r' => $radius]);
    }

    /** @param list<Vector2> $points */
    public static function polygon(string $name, array $points): static
    {
        if (count($points) < 3) {
            throw new TerranException("Shape2D '{$name}' polygon needs at least 3 points; got ".count($points).'.');
        }

        return new static($name, ShapeKind::Polygon, ['points' => array_map(fn (Vector2 $p): array => [$p->x, $p->y], array_values($points))]);
    }

    public static function line(string $name, Vector2 $from, Vector2 $to): static
    {
        return new static($name, ShapeKind::Line, ['from' => $from, 'to' => $to]);
    }

    public function kind(): ShapeKind { return $this->kind; }

    public function draw(RenderingEngine $ge): void
    {
        $fill = is_null($this->fill) ? null : $this->tint($this->fill);
        $stroke = is_null($this->stroke) ? null : $this->tint($this->stroke);
        $p = $this->params;
        match ($this->kind) {
            ShapeKind::Rect => $this->rectangle($ge, $p['w'], $p['h'], $fill, $stroke),
            ShapeKind::Circle => $this->disc($ge, $p['r'], $fill, $stroke),
            ShapeKind::Polygon => $this->outline($ge, $p['points'], $fill, $stroke),
            ShapeKind::Line => is_null($stroke ?? $fill) ? null : $ge->line($p['from']->x, $p['from']->y, $p['to']->x, $p['to']->y, $stroke ?? $fill, $this->strokeWidth),
        };
    }

    private function rectangle(RenderingEngine $ge, float $w, float $h, ?Color $fill, ?Color $stroke): void
    {
        if (! is_null($fill)) {
            $ge->fillRect(-$w / 2, -$h / 2, $w, $h, $fill);
        }
        if (! is_null($stroke)) {
            $ge->strokeRect(-$w / 2, -$h / 2, $w, $h, $stroke, $this->strokeWidth);
        }
    }

    private function disc(RenderingEngine $ge, float $r, ?Color $fill, ?Color $stroke): void
    {
        if (! is_null($fill)) {
            $ge->fillEllipse(0.0, 0.0, $r, $r, $fill);
        }
        if (! is_null($stroke)) {
            $ge->strokeEllipse(0.0, 0.0, $r, $r, $stroke, $this->strokeWidth);
        }
    }

    /** @param list<array{float, float}> $points */
    private function outline(RenderingEngine $ge, array $points, ?Color $fill, ?Color $stroke): void
    {
        if (! is_null($fill)) {
            $ge->fillPolygon($points, $fill);
        }
        if (! is_null($stroke)) {
            $ge->polyline($points, $stroke, $this->strokeWidth, true);
        }
    }

    /** @param array<string, float> $sizes */
    private static function positive(string $name, array $sizes): void
    {
        foreach ($sizes as $what => $size) {
            if ($size <= 0.0) {
                throw new TerranException("Shape2D '{$name}' {$what} is positive; got {$size}.");
            }
        }
    }
}
