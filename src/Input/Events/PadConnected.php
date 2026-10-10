<?php
declare(strict_types=1);
namespace Terran\Input\Events;

final readonly class PadConnected extends InputEvent
{
    /** $seat: the seat the pad holds after this frame's seating, null when unseated or seating is off. */
    public function __construct(public string $pad, public string $name, public ?int $seat = null) {}
    public function name(): string { return 'terran.input.pad.connected'; }
}
