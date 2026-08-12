<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Physics\PhysicsServer;

class PhysicsBounds2D extends CollisionObject2D
{
    /**
     * @var array{x: int, y: int, w: int, h: int}|null
     */
    public ?array $exclusionZone = null;

    /**
     * @return array{
     *     w: int,
     *     h: int,
     *     exclusion_zone: array{x: int, y: int, w: int, h: int}|null,
     *     bodies: list<array<string, mixed>>,
     * }
     */
    public function exportSnapshot(): array
    {
        $bodies = [];

        foreach ($this->children() as $child) {
            if ($child instanceof PhysicsObject2D) {
                $bodies[] = $child->exportState();
            }
        }

        return [
            'w' => $this->width,
            'h' => $this->height,
            'exclusion_zone' => $this->exclusionZone,
            'bodies' => $bodies,
        ];
    }

    /**
     * @param  array{
     *     w: int,
     *     h: int,
     *     exclusion_zone: array{x: int, y: int, w: int, h: int}|null,
     *     bodies: list<array<string, mixed>>,
     * }  $snapshot
     */
    public function applySnapshot(array $snapshot): void
    {
        $stateById = [];

        foreach ($snapshot['bodies'] as $state) {
            $stateById[(int) $state['id']] = $state;
        }

        foreach ($this->children() as $child) {
            if (! $child instanceof PhysicsObject2D) {
                continue;
            }

            if (isset($stateById[$child->id])) {
                $child->importState($stateById[$child->id]);
            }
        }
    }

    public function submitPhysicsStep(PhysicsServer $server, float $delta): void
    {
        $result = $server->submitStep([$this->exportSnapshot()], $delta);

        if (is_object($result) && method_exists($result, 'value')) {
            $result = $result->value();
        }

        if (is_array($result) && isset($result[0])) {
            $this->applySnapshot($result[0]);
        }

        $this->monitorAreaOverlaps();
    }

    /**
     * After bodies move, Area2D volumes emit enter/exit against sibling collision objects.
     */
    protected function monitorAreaOverlaps(): void
    {
        $candidates = [];

        foreach ($this->children() as $child) {
            if ($child instanceof CollisionObject2D) {
                $candidates[] = $child;
            }
        }

        foreach ($candidates as $child) {
            if ($child instanceof Area2D) {
                $child->monitorOverlaps($candidates);
            }
        }
    }
}
