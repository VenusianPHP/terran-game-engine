<?php
declare(strict_types=1);
namespace Terran\Input;

/** One seat as the physics phase sees it: that seat's latched edges over that seat's snapshot. */
final class LatchView implements Actions
{
    public function __construct(private readonly PhysicsLatch $latch, private readonly Actions $snapshot, private readonly int $seat) {}

    public function isDown(string $action): bool { return $this->snapshot->isDown($action); }

    public function isPressed(string $action): bool
    {
        $this->snapshot->isDown($action);

        return $this->latch->latched($this->seat, 'pressed', $action);
    }

    public function wasReleased(string $action): bool
    {
        $this->snapshot->isDown($action);

        return $this->latch->latched($this->seat, 'released', $action);
    }

    public function strength(string $action): float { return $this->snapshot->strength($action); }

    public function axis(string $negative, string $positive): float { return $this->snapshot->axis($negative, $positive); }
}
