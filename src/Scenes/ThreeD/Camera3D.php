<?php
declare(strict_types=1);
namespace Terran\Scenes\ThreeD;

use Terran\Math\Projection;
use Terran\Math\Transform3D;

/** The 3D view of one viewport. projection() for an aspect, view() = the inverse of its global transform. */
class Camera3D extends Scene3D
{
    /** Vertical, in degrees. */
    public float $fov = 70.0;

    public float $near = 0.05;

    public float $far = 1000.0;

    public CameraProjection $projectionKind = CameraProjection::PERSPECTIVE;

    /** Orthographic visible height. */
    public float $size = 10.0;

    /** Setting it binds this camera to its viewport (when live) or releases it; makeCurrent() is the same as setting true. */
    public bool $current = false {
        set(bool $value) {
            $this->current = $value;
            if ($value) {
                $this->world()?->bindCamera($this);
            } else {
                $this->world()?->releaseCamera($this);
            }
        }
    }

    public function __construct(string $name, public ?string $viewport = null)
    {
        parent::__construct($name);
    }

    public function makeCurrent(): static
    {
        $this->current = true;

        return $this;
    }

    public function current(): bool { return $this->current; }

    public function projection(float $aspect): Projection
    {
        return $this->projectionKind === CameraProjection::PERSPECTIVE
            ? Projection::perspective($this->fov, $aspect, $this->near, $this->far)
            : Projection::orthographic($this->size, $aspect, $this->near, $this->far);
    }

    public function view(): Transform3D { return $this->globalTransform()->inverse(); }
}
