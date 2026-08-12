<?php

namespace ScrapyardIO\GameEngine\Physics;

/**
 * Plain-array DTO that represents the physics state of one body.
 *
 * All fields are primitive scalars so the array is safe to pass across a
 * parallel\Runtime thread boundary without serialisation.
 *
 * Keys:
 *  - id       int    — stable identifier matching PhysicsObject2D::$id
 *  - x        int    — integer pixel position (left edge of AABB)
 *  - y        int    — integer pixel position (top edge of AABB)
 *  - w        int    — AABB width in pixels
 *  - h        int    — AABB height in pixels
 *  - vx       float  — velocity in pixels per second on the X axis
 *  - vy       float  — velocity in pixels per second on the Y axis
 *  - rem_x    float  — sub-pixel X accumulator (carry from previous steps)
 *  - rem_y    float  — sub-pixel Y accumulator (carry from previous steps)
 *
 * Usage example:
 *   $state = PhysicsBodyState::make($id, $x, $y, $w, $h, $vx, $vy);
 *
 * @phpstan-type BodyState array{
 *     id: int,
 *     x: int,
 *     y: int,
 *     w: int,
 *     h: int,
 *     vx: float,
 *     vy: float,
 *     rem_x: float,
 *     rem_y: float,
 * }
 */
final class PhysicsBodyState
{
    /**
     * Build a PhysicsBodyState plain array.
     *
     * @return array{id: int, x: int, y: int, w: int, h: int, vx: float, vy: float, rem_x: float, rem_y: float}
     */
    public static function make(
        int $id,
        int $x,
        int $y,
        int $w,
        int $h,
        float $vx    = 0.0,
        float $vy    = 0.0,
        float $rem_x = 0.0,
        float $rem_y = 0.0,
    ): array {
        return [
            'id'    => $id,
            'x'     => $x,
            'y'     => $y,
            'w'     => $w,
            'h'     => $h,
            'vx'    => $vx,
            'vy'    => $vy,
            'rem_x' => $rem_x,
            'rem_y' => $rem_y,
        ];
    }
}
