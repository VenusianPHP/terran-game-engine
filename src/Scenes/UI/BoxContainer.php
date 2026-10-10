<?php
declare(strict_types=1);
namespace Terran\Scenes\UI;

use Surface\Contracts\Drawing\RenderingEngine;
use Terran\Math\Vector2;

/**
 * Lays its visible direct UIControl children along one axis at their minimum
 * sizes, separation apart; children with expand share the space left; every
 * child fills the cross axis. A child's own anchors and margins are ignored.
 */
abstract class BoxContainer extends UIControl
{
    public float $separation = 4.0;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->mouseFilter = MouseFilter::IGNORE;
    }

    abstract protected function vertical(): bool;

    /** @return list<UIControl> */
    protected function laid(): array
    {
        return array_values(array_filter($this->children(), fn ($child): bool => $child instanceof UIControl && $child->visible));
    }

    protected function contentMinSize(RenderingEngine $ge): Vector2
    {
        $kids = $this->laid();
        [$along, $across] = [0.0, 0.0];
        foreach ($kids as $child) {
            $min = $child->effectiveMinSize($ge);
            $along += $this->vertical() ? $min->y : $min->x;
            $across = max($across, $this->vertical() ? $min->x : $min->y);
        }
        $along += $this->separation * max(0, count($kids) - 1);

        return $this->vertical() ? new Vector2($across, $along) : new Vector2($along, $across);
    }

    /** @param array{float, float, float, float} $rect */
    protected function layoutChildren(array $rect, string $viewport, RenderingEngine $ge): void
    {
        [$x, $y, $w, $h] = $rect;
        $kids = $this->laid();
        $mins = array_map(fn (UIControl $c): float => $this->vertical() ? $c->effectiveMinSize($ge)->y : $c->effectiveMinSize($ge)->x, $kids);
        $length = $this->vertical() ? $h : $w;
        $left = max(0.0, $length - array_sum($mins) - $this->separation * max(0, count($kids) - 1));
        $expanding = count(array_filter($kids, fn (UIControl $c): bool => $c->expand));
        $cursor = $this->vertical() ? $y : $x;
        foreach ($kids as $i => $child) {
            $extent = $mins[$i] + ($child->expand && $expanding > 0 ? $left / $expanding : 0.0);
            $child->place($this->vertical() ? [$x, $cursor, $w, $extent] : [$cursor, $y, $extent, $h], $viewport, $ge);
            $cursor += $extent + $this->separation;
        }
    }
}
