<?php
declare(strict_types=1);
namespace Terran\Scenes\TwoD;

use Surface\NutsAndBolts\Affine;
use Terran\Math\Vector2;
use Terran\TerranException;

/**
 * The 2D view of one viewport: its global position plus offset is the view
 * centre, zoom scales about it. Follow = make it a child of what it follows.
 */
class Camera2D extends Scene2D
{
    public Vector2 $zoom;

    public Vector2 $offset;

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

    /** @param ?string $viewport the viewport this camera drives; null = the World's first */
    public function __construct(string $name, public ?string $viewport = null)
    {
        parent::__construct($name);
        $this->zoom = Vector2::one();
        $this->offset = Vector2::zero();
    }

    public function makeCurrent(): static
    {
        $this->current = true;

        return $this;
    }

    public function current(): bool { return $this->current; }

    public function view(int $width, int $height): Affine
    {
        if ($this->zoom->x == 0.0 || $this->zoom->y == 0.0) {
            throw new TerranException("Camera2D '{$this->name}' zoom is non-zero on both axes; got {$this->zoom->x}×{$this->zoom->y}.");
        }
        $centre = $this->globalPosition()->plus($this->offset);

        return Affine::translation($width / 2, $height / 2)
            ->multiply(Affine::scaling($this->zoom->x, $this->zoom->y))
            ->multiply(Affine::translation(-$centre->x, -$centre->y));
    }
}
