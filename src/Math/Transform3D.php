<?php
declare(strict_types=1);
namespace Terran\Math;

/** A Basis and an origin: rotate and scale, then move. */
final readonly class Transform3D
{
    public function __construct(public Basis $basis, public Vector3 $origin) {}

    public static function identity(): self { return new self(Basis::identity(), Vector3::zero()); }

    /** This transform after $first. */
    public function multiply(self $first): self
    {
        return new self($this->basis->multiply($first->basis), $this->apply($first->origin));
    }

    public function apply(Vector3 $point): Vector3 { return $this->basis->times($point)->plus($this->origin); }

    public function inverse(): self
    {
        $basis = $this->basis->inverse();

        return new self($basis, $basis->times($this->origin)->times(-1.0));
    }

    /** Same origin, turned so -Z faces $target. */
    public function lookingAt(Vector3 $target, Vector3 $up): self
    {
        return new self(Basis::lookingAt($target->minus($this->origin), $up), $this->origin);
    }
}
