<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Geometry\Rect;

class GraphEdit extends Control
{
    public float $scrollOffsetX = 0.0;

    public float $scrollOffsetY = 0.0;

    public float $zoom = 1.0;

    /** @var list<array{from: string, fromPort: int, to: string, toPort: int}> */
    protected array $connections = [];

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 320, 240));
    }

    public function connectNode(string $from, int $fromPort, string $to, int $toPort): static
    {
        $this->connections[] = compact('from', 'fromPort', 'to', 'toPort');

        return $this;
    }

    public function disconnectNode(string $from, int $fromPort, string $to, int $toPort): static
    {
        $this->connections = array_values(array_filter(
            $this->connections,
            static fn (array $c): bool => ! (
                $c['from'] === $from
                && $c['fromPort'] === $fromPort
                && $c['to'] === $to
                && $c['toPort'] === $toPort
            ),
        ));

        return $this;
    }

    /** @return list<array{from: string, fromPort: int, to: string, toPort: int}> */
    public function getConnectionList(): array
    {
        return $this->connections;
    }
}
