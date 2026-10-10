<?php
declare(strict_types=1);
namespace Terran\Input\Events;

final readonly class ActionReleased extends InputEvent
{
    /** $seat: the seat whose input released it; null for input no seat owns, or with seating off. */
    public function __construct(public string $action, public ?int $seat = null) {}
    public function name(): string { return 'terran.input.action.released'; }
}
