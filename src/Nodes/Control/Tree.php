<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class Tree extends Control
{
    /** @var list<array{text: string, children: list<array>}> */
    protected array $roots = [];

    protected ?string $selectedPath = null;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 160, 120));
    }

    public function clear(): static
    {
        $this->roots = [];
        $this->selectedPath = null;

        return $this;
    }

    public function createItem(?string $parentPath = null, string $text = ''): string
    {
        if (is_null($parentPath)) {
            $index = count($this->roots);
            $this->roots[] = ['text' => $text, 'children' => []];

            return (string) $index;
        }

        $parts = explode('/', $parentPath);
        $node = &$this->roots;

        foreach ($parts as $part) {
            $i = (int) $part;

            if (! isset($node[$i])) {
                return $parentPath;
            }

            $node = &$node[$i]['children'];
        }

        $index = count($node);
        $node[] = ['text' => $text, 'children' => []];

        return $parentPath.'/'.$index;
    }

    public function select(string $path): static
    {
        $this->selectedPath = $path;

        return $this;
    }

    public function getSelected(): ?string
    {
        return $this->selectedPath;
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), 0xFF1A1A1A);
        $y = 4;

        foreach ($this->roots as $root) {
            $ctx->printLocal(4, $y, (string) $root['text'], 0xFFFFFFFF);
            $y += 10;
        }
    }
}
