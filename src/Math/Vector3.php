<?php
declare(strict_types=1);
namespace Terran\Math;

/** A 3D point or direction, right-handed, +Y up, -Z forward (Godot's). Immutable. */
final readonly class Vector3
{
    public function __construct(public float $x = 0.0, public float $y = 0.0, public float $z = 0.0) {}

    public static function zero(): self { return new self(0.0, 0.0, 0.0); }

    public function plus(self $o): self { return new self($this->x + $o->x, $this->y + $o->y, $this->z + $o->z); }

    public function minus(self $o): self { return new self($this->x - $o->x, $this->y - $o->y, $this->z - $o->z); }

    public function times(float $factor): self { return new self($this->x * $factor, $this->y * $factor, $this->z * $factor); }

    public function dot(self $o): float { return $this->x * $o->x + $this->y * $o->y + $this->z * $o->z; }

    public function cross(self $o): self
    {
        return new self(
            $this->y * $o->z - $this->z * $o->y,
            $this->z * $o->x - $this->x * $o->z,
            $this->x * $o->y - $this->y * $o->x,
        );
    }

    public function length(): float { return sqrt($this->dot($this)); }

    /** Unit length; the zero vector stays zero. */
    public function normalized(): self
    {
        $length = $this->length();

        return $length == 0.0 ? self::zero() : $this->times(1.0 / $length);
    }

    public function lerp(self $to, float $t): self { return $this->plus($to->minus($this)->times($t)); }
}
