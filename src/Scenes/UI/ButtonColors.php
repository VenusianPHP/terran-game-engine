<?php
declare(strict_types=1);
namespace Terran\Scenes\UI;

use Surface\NutsAndBolts\Color;

final readonly class ButtonColors
{
    public function __construct(
        public Color $normal,
        public Color $hover,
        public Color $pressed,
        public Color $disabled,
        public Color $text,
        public Color $disabledText,
        public Color $focus,
    ) {}

    public static function defaults(): self
    {
        return new self(
            Color::hex('#3A3A3A'),
            Color::hex('#4A4A4A'),
            Color::hex('#2A2A2A'),
            Color::hex('#303030'),
            Color::hex('#FFFFFF'),
            Color::hex('#808080'),
            Color::hex('#5AA0FF'),
        );
    }
}
