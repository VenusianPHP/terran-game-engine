<?php
declare(strict_types=1);
namespace Terran\Math;

use Terran\TerranException;

/** A 3×3 rotation and scale, as three column vectors: where +X, +Y and +Z go. */
final readonly class Basis
{
    public function __construct(public Vector3 $x, public Vector3 $y, public Vector3 $z) {}

    public static function identity(): self
    {
        return new self(new Vector3(1.0, 0.0, 0.0), new Vector3(0.0, 1.0, 0.0), new Vector3(0.0, 0.0, 1.0));
    }

    /** Euler angles in radians, applied Y, then X, then Z (Godot's YXZ order). */
    public static function fromEuler(Vector3 $radians): self
    {
        $x = self::axisAngle(new Vector3(1.0, 0.0, 0.0), $radians->x);
        $y = self::axisAngle(new Vector3(0.0, 1.0, 0.0), $radians->y);
        $z = self::axisAngle(new Vector3(0.0, 0.0, 1.0), $radians->z);

        return $y->multiply($x)->multiply($z);
    }

    /** A rotation about $axis (normalized here) by $radians, counter-clockwise looking down the axis. */
    public static function axisAngle(Vector3 $axis, float $radians): self
    {
        $a = $axis->normalized();
        if ($a->length() == 0.0) {
            throw new TerranException('A rotation axis has a direction; got the zero vector.');
        }
        $c = cos($radians);
        $s = sin($radians);
        $t = 1.0 - $c;

        return new self(
            new Vector3($t * $a->x * $a->x + $c, $t * $a->x * $a->y + $s * $a->z, $t * $a->x * $a->z - $s * $a->y),
            new Vector3($t * $a->x * $a->y - $s * $a->z, $t * $a->y * $a->y + $c, $t * $a->y * $a->z + $s * $a->x),
            new Vector3($t * $a->x * $a->z + $s * $a->y, $t * $a->y * $a->z - $s * $a->x, $t * $a->z * $a->z + $c),
        );
    }

    /** This basis turned about $axis in the parent's space. */
    public function rotated(Vector3 $axis, float $radians): self
    {
        return self::axisAngle($axis, $radians)->multiply($this);
    }

    /** A rotation whose -Z faces $direction and whose +Y leans towards $up. */
    public static function lookingAt(Vector3 $direction, Vector3 $up): self
    {
        if ($direction->length() == 0.0) {
            throw new TerranException('lookingAt() needs a direction; got the zero vector (the target is where the Scene already is).');
        }
        $z = $direction->normalized()->times(-1.0);
        $x = $up->cross($z);
        if ($x->length() < 1e-12) {
            throw new TerranException('lookingAt() direction is parallel to up; pass an up vector that is not along the direction.');
        }
        $x = $x->normalized();

        return new self($x, $z->cross($x), $z);
    }

    public function times(Vector3 $v): Vector3
    {
        return $this->x->times($v->x)->plus($this->y->times($v->y))->plus($this->z->times($v->z));
    }

    /** This basis after $first: maps through $first, then through this. */
    public function multiply(self $first): self
    {
        return new self($this->times($first->x), $this->times($first->y), $this->times($first->z));
    }

    public function determinant(): float
    {
        return $this->x->dot($this->y->cross($this->z));
    }

    public function inverse(): self
    {
        $det = $this->determinant();
        if (abs($det) < 1e-12) {
            throw new TerranException('This Basis is singular (it flattens space), so it has no inverse.');
        }
        $r0 = $this->y->cross($this->z)->times(1.0 / $det);
        $r1 = $this->z->cross($this->x)->times(1.0 / $det);
        $r2 = $this->x->cross($this->y)->times(1.0 / $det);

        return new self(new Vector3($r0->x, $r1->x, $r2->x), new Vector3($r0->y, $r1->y, $r2->y), new Vector3($r0->z, $r1->z, $r2->z));
    }
}
