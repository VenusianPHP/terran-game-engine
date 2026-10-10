<?php
declare(strict_types=1);
namespace Terran\Input\Events;

final readonly class PadDisconnected extends InputEvent
{
    /** $name: the pad's name when Terran saw it listed or connect, null otherwise (HumanInput's disconnect mail carries only the id). $seat: the seat the pad keeps reserved, null when it held none. */
    public function __construct(public string $pad, public ?string $name, public ?int $seat = null) {}
    public function name(): string { return 'terran.input.pad.disconnected'; }
}
