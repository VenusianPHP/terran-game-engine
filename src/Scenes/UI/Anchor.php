<?php
declare(strict_types=1);
namespace Terran\Scenes\UI;

/** Anchor presets: fractions [left, top, right, bottom] of the parent rect. */
enum Anchor
{
    case TOP_LEFT;
    case TOP_RIGHT;
    case BOTTOM_LEFT;
    case BOTTOM_RIGHT;
    case CENTER;
    case TOP_WIDE;
    case BOTTOM_WIDE;
    case LEFT_WIDE;
    case RIGHT_WIDE;
    case FULL_RECT;

    /** @return array{float, float, float, float} */
    public function fractions(): array
    {
        return match ($this) {
            self::TOP_LEFT => [0.0, 0.0, 0.0, 0.0],
            self::TOP_RIGHT => [1.0, 0.0, 1.0, 0.0],
            self::BOTTOM_LEFT => [0.0, 1.0, 0.0, 1.0],
            self::BOTTOM_RIGHT => [1.0, 1.0, 1.0, 1.0],
            self::CENTER => [0.5, 0.5, 0.5, 0.5],
            self::TOP_WIDE => [0.0, 0.0, 1.0, 0.0],
            self::BOTTOM_WIDE => [0.0, 1.0, 1.0, 1.0],
            self::LEFT_WIDE => [0.0, 0.0, 0.0, 1.0],
            self::RIGHT_WIDE => [1.0, 0.0, 1.0, 1.0],
            self::FULL_RECT => [0.0, 0.0, 1.0, 1.0],
        };
    }
}
