<?php
declare(strict_types=1);
namespace Terran\Input\Events;

use Surface\Contracts\HumanInput\MouseButton;

final readonly class MouseButtonReleased extends InputEvent
{
    public function __construct(public MouseButton $button, public ?string $viewport, public float $x, public float $y) {}
    public function name(): string { return 'terran.input.mouse.released'; }
}
