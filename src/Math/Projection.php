<?php
declare(strict_types=1);
namespace Terran\Math;

use Terran\TerranException;

/** A 4×4 projection, column-major (m[column * 4 + row]), OpenGL clip conventions: NDC −1..1 on every axis. */
final readonly class Projection
{
    /** @var list<float> */
    public array $m;

    /** @param list<int|float> $m */
    public function __construct(array $m)
    {
        if (count($m) !== 16) {
            throw new TerranException('A Projection takes 16 numbers, column-major; got '.count($m).'.');
        }
        $this->m = array_map(fn ($v): float => (float) $v, array_values($m));
    }

    public static function perspective(float $fov_y, float $aspect, float $near, float $far): self
    {
        if ($fov_y <= 0.0 || $fov_y >= 180.0) {
            throw new TerranException("A perspective fov is between 0 and 180 degrees; got {$fov_y}.");
        }
        self::checkFrustum($aspect, $near, $far);
        $f = 1.0 / tan(deg2rad($fov_y) / 2.0);

        return new self([
            $f / $aspect, 0, 0, 0,
            0, $f, 0, 0,
            0, 0, ($far + $near) / ($near - $far), -1,
            0, 0, 2 * $far * $near / ($near - $far), 0,
        ]);
    }

    /** $size is the visible height in world units. */
    public static function orthographic(float $size, float $aspect, float $near, float $far): self
    {
        if ($size <= 0.0) {
            throw new TerranException("An orthographic size is a positive height; got {$size}.");
        }
        self::checkFrustum($aspect, $near, $far);
        $width = $size * $aspect;

        return new self([
            2 / $width, 0, 0, 0,
            0, 2 / $size, 0, 0,
            0, 0, -2 / ($far - $near), 0,
            0, 0, -($far + $near) / ($far - $near), 1,
        ]);
    }

    /** This projection after $first. */
    public function multiply(self $first): self
    {
        $out = [];
        for ($col = 0; $col < 4; $col++) {
            for ($row = 0; $row < 4; $row++) {
                $sum = 0.0;
                for ($k = 0; $k < 4; $k++) {
                    $sum += $this->m[$k * 4 + $row] * $first->m[$col * 4 + $k];
                }
                $out[] = $sum;
            }
        }

        return new self($out);
    }

    /** A camera-space point to normalized device coordinates. */
    public function project(Vector3 $p): Vector3
    {
        $m = $this->m;
        $w = $m[3] * $p->x + $m[7] * $p->y + $m[11] * $p->z + $m[15];
        if (abs($w) < 1e-12) {
            throw new TerranException('This point lies on the camera plane (w = 0); it has no projection.');
        }

        return new Vector3(
            ($m[0] * $p->x + $m[4] * $p->y + $m[8] * $p->z + $m[12]) / $w,
            ($m[1] * $p->x + $m[5] * $p->y + $m[9] * $p->z + $m[13]) / $w,
            ($m[2] * $p->x + $m[6] * $p->y + $m[10] * $p->z + $m[14]) / $w,
        );
    }

    /** @return list<float> */
    public function toArray(): array { return $this->m; }

    private static function checkFrustum(float $aspect, float $near, float $far): void
    {
        if ($aspect <= 0.0) {
            throw new TerranException("A projection aspect is width / height, a positive number; got {$aspect}.");
        }
        if ($near <= 0.0 || $far <= $near) {
            throw new TerranException("A projection takes 0 < near < far; got near {$near}, far {$far}.");
        }
    }
}
