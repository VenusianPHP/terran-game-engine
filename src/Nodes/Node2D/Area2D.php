<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

/**
 * Detection volume — emits body/area signals on overlap (AABB).
 */
class Area2D extends CollisionObject2D
{
    /**
     * @var array<int, CollisionObject2D>
     */
    protected array $overlapping = [];

    public function monitorOverlaps(iterable $candidates): void
    {
        $seen = [];

        foreach ($candidates as $other) {
            if (! $other instanceof CollisionObject2D || $other === $this) {
                continue;
            }

            if (! $this->overlaps($other)) {
                continue;
            }

            $id = spl_object_id($other);
            $seen[$id] = $other;

            if (! isset($this->overlapping[$id])) {
                $this->overlapping[$id] = $other;

                if ($other instanceof Area2D) {
                    $this->emit('area_entered', ['area' => $other]);
                } else {
                    $this->emit('body_entered', ['body' => $other]);
                }
            }
        }

        foreach ($this->overlapping as $id => $other) {
            if (isset($seen[$id])) {
                continue;
            }

            unset($this->overlapping[$id]);

            if ($other instanceof Area2D) {
                $this->emit('area_exited', ['area' => $other]);
            } else {
                $this->emit('body_exited', ['body' => $other]);
            }
        }
    }

    public function overlaps(CollisionObject2D $other): bool
    {
        $ax = $this->globalX();
        $ay = $this->globalY();
        $bx = $other->globalX();
        $by = $other->globalY();

        return $ax < $bx + $other->width
            && $ax + $this->width > $bx
            && $ay < $by + $other->height
            && $ay + $this->height > $by;
    }
}
