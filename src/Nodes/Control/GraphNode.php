<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class GraphNode extends GraphElement
{
    public string $title = '';

    /** @var array<int, string> */
    protected array $slots = [];

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 140, 90));
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setSlot(int $index, string $name): static
    {
        $this->slots[$index] = $name;

        return $this;
    }

    /** @return array<int, string> */
    public function slots(): array
    {
        return $this->slots;
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), $this->selected ? 0xFF3A5A8A : 0xFF2A2A2A);

        if ($this->title !== '') {
            $ctx->printLocal(4, 4, $this->title, 0xFFFFFFFF);
        }
    }
}
