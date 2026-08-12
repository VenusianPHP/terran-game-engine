<?php

namespace ScrapyardIO\GameEngine\Physics;

/**
 * Pure static physics resolver — fully parallel\Runtime-safe.
 *
 * No PHP objects, no closures, no static properties. Every method works
 * exclusively on plain array data so the entire class can be called from
 * a parallel\Runtime worker without any serialisation concerns.
 *
 * Snapshot format expected by step():
 * @phpstan-type ExclusionZone array{x: int, y: int, w: int, h: int}
 * @phpstan-type BoundsSnapshot array{
 *     w: int,
 *     h: int,
 *     exclusion_zone: ExclusionZone|null,
 *     bodies: list<array{id: int, x: int, y: int, w: int, h: int, vx: float, vy: float, rem_x: float, rem_y: float}>
 * }
 *
 * The return value of step() is an array of the same BoundsSnapshot structures
 * with updated body positions, velocities and sub-pixel remainders.
 */
final class PhysicsResolver
{
    // -------------------------------------------------------------------------
    // Public entry point
    // -------------------------------------------------------------------------

    /**
     * Advance physics by $delta seconds for every bounds region in the snapshot
     * array.  Returns the snapshot array with all body states updated.
     *
     * @param  array<int, array<string, mixed>>  $bounds_snapshots
     * @return array<int, array<string, mixed>>
     */
    public static function step(array $bounds_snapshots, float $delta): array
    {
        foreach ($bounds_snapshots as $index => $snapshot) {
            $bounds_snapshots[$index] = self::stepBounds($snapshot, $delta);
        }

        return $bounds_snapshots;
    }

    // -------------------------------------------------------------------------
    // Per-bounds step
    // -------------------------------------------------------------------------

    /**
     * Run one physics step for a single PhysicsBounds2D region.
     *
     * Order of operations (mirrors Godot's physics pipeline):
     *   1. Integrate velocities → advance positions using sub-pixel accumulator.
     *   2. Clamp all bodies to the bounds rectangle.
     *   3. Resolve exclusion zone pushout (if present).
     *   4. Resolve pairwise AABB body–body collisions.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private static function stepBounds(array $snapshot, float $delta): array
    {
        $bounds_w       = (int) $snapshot['w'];
        $bounds_h       = (int) $snapshot['h'];
        $exclusion_zone = $snapshot['exclusion_zone'] ?? null;

        /** @var list<array<string, mixed>> $bodies */
        $bodies = $snapshot['bodies'] ?? [];

        // 1. Integrate.
        $bodies = self::integrate($bodies, $delta);

        // 2. Clamp to bounds rectangle.
        self::clampToBounds($bodies, $bounds_w, $bounds_h);

        // 3. Exclusion zone pushout.
        if (! is_null($exclusion_zone)) {
            self::resolveExclusionZone($bodies, $exclusion_zone);
        }

        // 4. Body–body AABB collision resolution.
        self::resolveShapeCollisions($bodies);

        $snapshot['bodies'] = $bodies;

        return $snapshot;
    }

    // -------------------------------------------------------------------------
    // Integration
    // -------------------------------------------------------------------------

    /**
     * Advance each body's position by velocity * delta using a sub-pixel
     * accumulator to prevent rounding drift.
     *
     * The accumulator (rem_x / rem_y) carries the fractional pixel from one
     * frame into the next, so the body moves by exactly vx*delta pixels over
     * time regardless of frame rate.
     *
     * @param  list<array<string, mixed>>  $bodies
     * @return list<array<string, mixed>>
     */
    private static function integrate(array $bodies, float $delta): array
    {
        foreach ($bodies as &$body) {
            $move_x        = (float) $body['vx'] * $delta + (float) $body['rem_x'];
            $move_y        = (float) $body['vy'] * $delta + (float) $body['rem_y'];

            $int_x         = (int) $move_x;
            $int_y         = (int) $move_y;

            $body['x']     = (int) $body['x'] + $int_x;
            $body['y']     = (int) $body['y'] + $int_y;
            $body['rem_x'] = $move_x - $int_x;
            $body['rem_y'] = $move_y - $int_y;
        }

        unset($body);

        return $bodies;
    }

    // -------------------------------------------------------------------------
    // Bounds clamping
    // -------------------------------------------------------------------------

    /**
     * Keep every body fully inside the [0, 0, w, h] rectangle.
     *
     * When a body hits a wall its velocity component in that direction is
     * negated (elastic bounce) and its sub-pixel remainder on that axis is
     * cleared.
     *
     * @param  list<array<string, mixed>>  $bodies
     */
    private static function clampToBounds(array &$bodies, int $w, int $h): void
    {
        foreach ($bodies as &$body) {
            $bw = (int) $body['w'];
            $bh = (int) $body['h'];

            // Left wall.
            if ((int) $body['x'] < 0) {
                $body['x']     = 0;
                $body['vx']    = abs((float) $body['vx']);
                $body['rem_x'] = 0.0;
            }

            // Right wall.
            if ((int) $body['x'] + $bw > $w) {
                $body['x']     = $w - $bw;
                $body['vx']    = -abs((float) $body['vx']);
                $body['rem_x'] = 0.0;
            }

            // Top wall.
            if ((int) $body['y'] < 0) {
                $body['y']     = 0;
                $body['vy']    = abs((float) $body['vy']);
                $body['rem_y'] = 0.0;
            }

            // Bottom wall.
            if ((int) $body['y'] + $bh > $h) {
                $body['y']     = $h - $bh;
                $body['vy']    = -abs((float) $body['vy']);
                $body['rem_y'] = 0.0;
            }
        }

        unset($body);
    }

    // -------------------------------------------------------------------------
    // Exclusion zone
    // -------------------------------------------------------------------------

    /**
     * Push any body that overlaps the exclusion zone out to the nearest edge.
     *
     * The exclusion zone is an axis-aligned rectangle (in bounds-local
     * coordinates) that bodies are not allowed to occupy.  When a body
     * overlaps it, the resolver finds the shallowest axis of penetration and
     * pushes the body out along that axis, negating the velocity component so
     * the body bounces back.
     *
     * @param  list<array<string, mixed>>  $bodies
     * @param  array{x: int, y: int, w: int, h: int}  $zone
     */
    private static function resolveExclusionZone(array &$bodies, array $zone): void
    {
        $zx = (int) $zone['x'];
        $zy = (int) $zone['y'];
        $zw = (int) $zone['w'];
        $zh = (int) $zone['h'];
        $zx2 = $zx + $zw;
        $zy2 = $zy + $zh;

        foreach ($bodies as &$body) {
            $bx  = (int) $body['x'];
            $by  = (int) $body['y'];
            $bx2 = $bx + (int) $body['w'];
            $by2 = $by + (int) $body['h'];

            // No overlap → skip.
            if ($bx >= $zx2 || $bx2 <= $zx || $by >= $zy2 || $by2 <= $zy) {
                continue;
            }

            // Compute penetration depth on each axis.
            $overlap_left   = $bx2 - $zx;  // body right  past zone left
            $overlap_right  = $zx2 - $bx;  // zone right  past body left
            $overlap_top    = $by2 - $zy;  // body bottom past zone top
            $overlap_bottom = $zy2 - $by;  // zone bottom past body top

            // Resolve on the shallowest-penetration axis.
            $min_h = min($overlap_left, $overlap_right);
            $min_v = min($overlap_top, $overlap_bottom);

            if ($min_h <= $min_v) {
                // Horizontal resolution.
                if ($overlap_left < $overlap_right) {
                    $body['x']  = $zx - (int) $body['w'];
                    $body['vx'] = -abs((float) $body['vx']);
                } else {
                    $body['x']  = $zx2;
                    $body['vx'] = abs((float) $body['vx']);
                }

                $body['rem_x'] = 0.0;
            } else {
                // Vertical resolution.
                if ($overlap_top < $overlap_bottom) {
                    $body['y']  = $zy - (int) $body['h'];
                    $body['vy'] = -abs((float) $body['vy']);
                } else {
                    $body['y']  = $zy2;
                    $body['vy'] = abs((float) $body['vy']);
                }

                $body['rem_y'] = 0.0;
            }
        }

        unset($body);
    }

    // -------------------------------------------------------------------------
    // Body–body AABB collision resolution
    // -------------------------------------------------------------------------

    /**
     * Resolve pairwise AABB collisions between bodies.
     *
     * For each overlapping pair the resolver computes the minimum translation
     * vector (MTV) on the shallowest axis and pushes the two bodies apart
     * symmetrically (half each).  Their velocity components on the resolution
     * axis are exchanged to simulate a perfectly elastic collision.
     *
     * Complexity: O(n²) — acceptable for the small body counts typical in
     * embedded / pixel-art game scenarios.
     *
     * @param  list<array<string, mixed>>  $bodies
     */
    private static function resolveShapeCollisions(array &$bodies): void
    {
        $count = count($bodies);

        for ($i = 0; $i < $count - 1; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = &$bodies[$i];
                $b = &$bodies[$j];

                $ax  = (int) $a['x'];
                $ay  = (int) $a['y'];
                $ax2 = $ax + (int) $a['w'];
                $ay2 = $ay + (int) $a['h'];

                $bx  = (int) $b['x'];
                $by  = (int) $b['y'];
                $bx2 = $bx + (int) $b['w'];
                $by2 = $by + (int) $b['h'];

                // SAT: separating axis check — skip non-overlapping pairs.
                if ($ax >= $bx2 || $ax2 <= $bx || $ay >= $by2 || $ay2 <= $by) {
                    continue;
                }

                // Penetration depths per axis.
                $overlap_x = min($ax2 - $bx, $bx2 - $ax);
                $overlap_y = min($ay2 - $by, $by2 - $ay);

                if ($overlap_x <= $overlap_y) {
                    // Resolve on the X axis.
                    $half     = (int) ($overlap_x / 2);
                    $leftward = $ax < $bx;

                    if ($leftward) {
                        $a['x'] = $ax - $half;
                        $b['x'] = $bx + $overlap_x - $half;
                    } else {
                        $a['x'] = $ax + $overlap_x - $half;
                        $b['x'] = $bx - $half;
                    }

                    // Exchange X velocity components.
                    $tmp_vx  = $a['vx'];
                    $a['vx'] = $b['vx'];
                    $b['vx'] = $tmp_vx;

                    $a['rem_x'] = 0.0;
                    $b['rem_x'] = 0.0;
                } else {
                    // Resolve on the Y axis.
                    $half    = (int) ($overlap_y / 2);
                    $upward  = $ay < $by;

                    if ($upward) {
                        $a['y'] = $ay - $half;
                        $b['y'] = $by + $overlap_y - $half;
                    } else {
                        $a['y'] = $ay + $overlap_y - $half;
                        $b['y'] = $by - $half;
                    }

                    // Exchange Y velocity components.
                    $tmp_vy  = $a['vy'];
                    $a['vy'] = $b['vy'];
                    $b['vy'] = $tmp_vy;

                    $a['rem_y'] = 0.0;
                    $b['rem_y'] = 0.0;
                }
            }
        }
    }
}
