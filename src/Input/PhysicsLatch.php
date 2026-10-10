<?php
declare(strict_types=1);
namespace Terran\Input;

/**
 * The action edges as the physics phase sees them, for every source and for
 * each seat. Each frame's capture ORs its edges in; GameLoop clears them after
 * the next physics step, so a tap reaches exactly one step, and edges wait
 * across frames that ran no step. Held state and strength are the frame's
 * snapshot.
 */
final class PhysicsLatch implements Actions
{
    /** @var array<int|string, array{pressed: array<string, true>, released: array<string, true>}> scope ('any' or a seat) → edges */
    private array $edges = [];

    public function __construct(private readonly InputMap $map) {}

    /** @param list<string> $pressed @param list<string> $released */
    public function latch(int|string $scope, array $pressed, array $released): void
    {
        foreach ($pressed as $action) {
            $this->edges[$scope]['pressed'][$action] = true;
        }
        foreach ($released as $action) {
            $this->edges[$scope]['released'][$action] = true;
        }
    }

    public function clear(): void
    {
        $this->edges = [];
    }

    /** Whether $scope latched $kind for $action since the last clear. */
    public function latched(int|string $scope, string $kind, string $action): bool
    {
        return isset($this->edges[$scope][$kind][$action]);
    }

    /** One seat's edges as physics sees them. */
    public function seat(int $seat): Actions
    {
        return new LatchView($this, $this->map->seat($seat), $seat);
    }

    public function isDown(string $action): bool { return $this->map->isDown($action); }

    public function isPressed(string $action): bool
    {
        $this->map->isDown($action);   // an unknown action throws the map's "No action" message

        return $this->latched('any', 'pressed', $action);
    }

    public function wasReleased(string $action): bool
    {
        $this->map->isDown($action);

        return $this->latched('any', 'released', $action);
    }

    public function strength(string $action): float { return $this->map->strength($action); }

    public function axis(string $negative, string $positive): float { return $this->map->axis($negative, $positive); }
}
