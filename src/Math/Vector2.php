<?php
declare(strict_types=1);
namespace Terran\Math;

/** A 2D point or offset. Immutable: every operation returns a new one. */
final readonly class Vector2
{
    public function __construct(public float $x = 0.0, public float $y = 0.0) {}

    public static function zero(): self { return new self(0.0, 0.0); }

    public static function one(): self { return new self(1.0, 1.0); }

    public function plus(self $other): self { return new self($this->x + $other->x, $this->y + $other->y); }

    public function minus(self $other): self { return new self($this->x - $other->x, $this->y - $other->y); }

    public function times(float $factor): self { return new self($this->x * $factor, $this->y * $factor); }

    public function length(): float { return sqrt($this->x * $this->x + $this->y * $this->y); }

    /** Unit length; the zero vector stays zero. */
    public function normalized(): self
    {
        $length = $this->length();

        return $length == 0.0 ? self::zero() : new self($this->x / $length, $this->y / $length);
    }

    /** Turns +x towards +y (clockwise on a surface whose y runs down), as Affine::rotation(). */
    public function rotated(float $radians): self
    {
        $cos = cos($radians);
        $sin = sin($radians);

        return new self($this->x * $cos - $this->y * $sin, $this->x * $sin + $this->y * $cos);
    }

    public function lerp(self $to, float $t): self
    {
        return new self($this->x + ($to->x - $this->x) * $t, $this->y + ($to->y - $this->y) * $t);
    }
}
