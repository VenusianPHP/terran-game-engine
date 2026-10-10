<?php
declare(strict_types=1);
namespace Terran\Scenes\UI;

use Surface\Contracts\Drawing\RenderingEngine;
use Terran\Math\Vector2;
use Terran\Scene;
use Terran\Scenes\CanvasItem;
use Terran\TerranException;

/**
 * Godot's Control: laid out in Viewport pixels from anchors (fractions of the
 * parent rect: the nearest UIControl ancestor's, else the viewport's) and
 * margins (pixel offsets from those lines), recomputed every frame top down,
 * per viewport. draw() runs with (0, 0) at the rect's top-left. Input comes by
 * hit test (mouse) or focus (everything else); see World::deliver().
 */
class UIControl extends Scene
{
    use CanvasItem;

    /** @var array{float, float, float, float} [left, top, right, bottom] fractions */
    public array $anchor = [0.0, 0.0, 0.0, 0.0];

    /** @var array{float, float, float, float} [left, top, right, bottom] pixel offsets */
    public array $margin = [0.0, 0.0, 0.0, 0.0];

    public Vector2 $minSize;

    public MouseFilter $mouseFilter = MouseFilter::STOP;

    public bool $focusable = false;

    /** Inside a VBox or HBox: share the space left over. */
    public bool $expand = false;

    /** @var array<string, array{float, float, float, float}> viewport → [x, y, w, h] */
    private array $rects = [];

    private ?string $first_viewport = null;

    private ?string $drawing = null;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->minSize = Vector2::zero();
    }

    public function setAnchor(Anchor $preset): static
    {
        $this->anchor = $preset->fractions();

        return $this;
    }

    public function setMargin(float $left, float $top, float $right, float $bottom): static
    {
        $this->margin = [$left, $top, $right, $bottom];

        return $this;
    }

    /** @param array{float, float, float, float} $parent */
    public function layout(array $parent, string $viewport, RenderingEngine $ge): void
    {
        [$px, $py, $pw, $ph] = $parent;
        [$al, $at, $ar, $ab] = $this->anchor;
        [$ml, $mt, $mr, $mb] = $this->margin;
        $x0 = $px + $pw * $al + $ml;
        $y0 = $py + $ph * $at + $mt;
        $min = $this->effectiveMinSize($ge);
        $this->place([$x0, $y0, max($px + $pw * $ar + $mr - $x0, $min->x), max($py + $ph * $ab + $mb - $y0, $min->y)], $viewport, $ge);
    }

    /**
     * Take this rect as given (a box placing its child), then lay out the descendants.
     *
     * @param array{float, float, float, float} $rect
     */
    public function place(array $rect, string $viewport, RenderingEngine $ge): void
    {
        $this->rects[$viewport] = $rect;
        $this->first_viewport ??= $viewport;
        $this->layoutChildren($rect, $viewport, $ge);
    }

    /** @param array{float, float, float, float} $rect */
    protected function layoutChildren(array $rect, string $viewport, RenderingEngine $ge): void
    {
        foreach (self::nearestControls($this) as $child) {
            $child->layout($rect, $viewport, $ge);
        }
    }

    /** @return list<UIControl> the UIControls under $scene with no UIControl between */
    public static function nearestControls(Scene $scene): array
    {
        $out = [];
        foreach ($scene->children() as $child) {
            if ($child instanceof self) {
                $out[] = $child;
            } else {
                array_push($out, ...self::nearestControls($child));
            }
        }

        return $out;
    }

    public function effectiveMinSize(RenderingEngine $ge): Vector2
    {
        $content = $this->contentMinSize($ge);

        return new Vector2(max($this->minSize->x, $content->x), max($this->minSize->y, $content->y));
    }

    /** What the content needs; Label and boxes override. */
    protected function contentMinSize(RenderingEngine $ge): Vector2
    {
        return Vector2::zero();
    }

    /**
     * The rect from the last layout in $viewport: null = the viewport drawing now, else the first laid out.
     *
     * @return array{float, float, float, float}
     */
    public function rect(?string $viewport = null): array
    {
        $key = $viewport ?? $this->drawing ?? $this->first_viewport;

        return is_null($key) ? [0.0, 0.0, 0.0, 0.0] : ($this->rects[$key] ?? [0.0, 0.0, 0.0, 0.0]);
    }

    public function size(?string $viewport = null): Vector2
    {
        [, , $w, $h] = $this->rect($viewport);

        return new Vector2($w, $h);
    }

    public function hitTest(float $x, float $y, ?string $viewport = null): bool
    {
        $key = $viewport ?? $this->first_viewport;
        if (! $this->visibleInTree() || is_null($key) || ! isset($this->rects[$key])) {
            return false;
        }
        [$rx, $ry, $rw, $rh] = $this->rects[$key];

        return $x >= $rx && $x < $rx + $rw && $y >= $ry && $y < $ry + $rh;
    }

    /** The Viewport draws through this, so size() and rect() answer for that viewport. */
    public function drawIn(RenderingEngine $ge, string $viewport): void
    {
        $this->drawing = $viewport;
        try {
            $this->draw($ge);
        } finally {
            $this->drawing = null;
        }
    }

    public function grabFocus(): static
    {
        $world = $this->world() ?? throw new TerranException("'{$this->name}' is not in a live tree; focus is taken once it is.");
        $world->focus($this);

        return $this;
    }

    public function releaseFocus(): static
    {
        if ($this->hasFocus()) {
            $this->world()->focus(null);
        }

        return $this;
    }

    public function hasFocus(): bool { return ! is_null($this->world()) && $this->world()->focused() === $this; }

    /** The pointer moved onto this control (topmost under it). */
    public function mouseEntered(): void {}

    /** The pointer moved off this control. */
    public function mouseExited(): void {}

    protected function canvasParent(): ?self
    {
        for ($scene = $this->parent; ! is_null($scene); $scene = $scene->parent()) {
            if ($scene instanceof self) {
                return $scene;
            }
        }

        return null;
    }
}
