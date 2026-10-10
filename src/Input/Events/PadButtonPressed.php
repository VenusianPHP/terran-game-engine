<?php
declare(strict_types=1);
namespace Terran\Input\Events;

use Surface\Contracts\HumanInput\GamepadButton;

final readonly class PadButtonPressed extends InputEvent
{
    public function __construct(public string $pad, public GamepadButton $button, public ?int $seat = null) {}
    public function name(): string { return 'terran.input.pad.pressed'; }
}
