<?php
declare(strict_types=1);
namespace Terran\Input\Events;

final readonly class MouseWheel extends InputEvent
{
    /** Lines; $dy > 0 is the wheel rolled away from the user (HumanInput's sign). */
    public function __construct(public ?string $viewport, public float $dx, public float $dy) {}
    public function name(): string { return 'terran.input.mouse.wheel'; }
}
