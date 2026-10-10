<?php
declare(strict_types=1);
namespace Terran\Input\Events;

final readonly class MouseMoved extends InputEvent
{
    /** $dx, $dy are HumanInput's summed motion this frame, in window coordinates. */
    public function __construct(public ?string $viewport, public float $x, public float $y, public float $dx, public float $dy) {}
    public function name(): string { return 'terran.input.mouse.moved'; }
}
