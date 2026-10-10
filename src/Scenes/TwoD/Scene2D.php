<?php
declare(strict_types=1);
namespace Terran\Scenes\TwoD;

use Surface\NutsAndBolts\Affine;
use Terran\Math\Vector2;
use Terran\Scene;
use Terran\Scenes\CanvasItem;

/**
 * Godot's Node2D: a position, rotation and scale composed from the nearest
 * Scene2D ancestors. The Viewport's 2D pass draws it in local space through
 * view × globalTransform(). Concrete, so it also serves as a group.
 */
class Scene2D extends Scene
{
    use CanvasItem;

    public Vector2 $position;

    /** Radians; turns +x towards +y. */
    public float $rotation = 0.0;

    public Vector2 $scale;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->position = Vector2::zero();
        $this->scale = Vector2::one();
    }

    public function transform(): Affine
    {
        return Affine::translation($this->position->x, $this->position->y)
            ->multiply(Affine::rotation($this->rotation))
            ->multiply(Affine::scaling($this->scale->x, $this->scale->y));
    }

    public function globalTransform(): Affine
    {
        $parent = $this->canvasParent();

        return is_null($parent) ? $this->transform() : $parent->globalTransform()->multiply($this->transform());
    }

    public function globalPosition(): Vector2
    {
        [$x, $y] = $this->globalTransform()->apply(0.0, 0.0);

        return new Vector2($x, $y);
    }

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
