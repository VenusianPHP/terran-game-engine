<?php
declare(strict_types=1);
namespace Terran\Scenes\ThreeD;

use Terran\Math\Basis;
use Terran\Math\Transform3D;
use Terran\Math\Vector3;
use Terran\Scene;

/**
 * Godot's Node3D: a local Transform3D composed from the nearest Scene3D
 * ancestors. Built and transformed now; the Viewport draws 3D from slice 6.
 */
class Scene3D extends Scene
{
    public Transform3D $transform;

    public bool $visible = true;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->transform = Transform3D::identity();
    }

    public function globalTransform(): Transform3D
    {
        $parent = $this->parent3d();

        return is_null($parent) ? $this->transform : $parent->globalTransform()->multiply($this->transform);
    }

    public function hidden(): bool { return ! $this->visible; }

    /** Move by $by in the parent's space. */
    public function translate(Vector3 $by): static
    {
        $this->transform = new Transform3D($this->transform->basis, $this->transform->origin->plus($by));

        return $this;
    }

    public function rotateX(float $radians): static { return $this->rotate(new Vector3(1.0, 0.0, 0.0), $radians); }

    public function rotateY(float $radians): static { return $this->rotate(new Vector3(0.0, 1.0, 0.0), $radians); }

    public function rotateZ(float $radians): static { return $this->rotate(new Vector3(0.0, 0.0, 1.0), $radians); }

    /** Turn so -Z faces $target; both vectors are global. */
    public function lookAt(Vector3 $target, Vector3 $up): static
    {
        $parent = $this->parent3d();
        if (is_null($parent)) {
            $this->transform = $this->transform->lookingAt($target, $up);

            return $this;
        }
        $into = $parent->globalTransform()->inverse();
        $this->transform = $this->transform->lookingAt($into->apply($target), $into->basis->times($up));

        return $this;
    }

    private function rotate(Vector3 $axis, float $radians): static
    {
        $this->transform = new Transform3D($this->transform->basis->rotated($axis, $radians), $this->transform->origin);

        return $this;
    }

    protected function parent3d(): ?self
    {
        for ($scene = $this->parent; ! is_null($scene); $scene = $scene->parent()) {
            if ($scene instanceof self) {
                return $scene;
            }
        }

        return null;
    }
}
