<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

#[Signal('tab_changed', ['tab' => 'int'])]
class TabBar extends Control
{
    /** @var list<string> */
    protected array $tabs = [];

    protected int $current = 0;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 160, 24));
    }

    public function addTab(string $title): static
    {
        $this->tabs[] = $title;

        return $this;
    }

    public function setCurrentTab(int $index): static
    {
        if ($index < 0 || $index >= count($this->tabs)) {
            return $this;
        }

        $this->current = $index;
        $this->emit('tab_changed', ['tab' => $index]);

        return $this;
    }

    public function getCurrentTab(): int
    {
        return $this->current;
    }

    public function getTabCount(): int
    {
        return count($this->tabs);
    }

    protected function draw(PaintContext $ctx): void
    {
        $x = 0;

        foreach ($this->tabs as $i => $title) {
            $w = (6 * strlen($title)) + 8;
            $bg = $i === $this->current ? 0xFF4EA1FF : 0xFF333333;
            $ctx->fillRectLocal($x, 0, $w, max(12, $this->rect()->height), $bg);
            $ctx->printLocal($x + 4, 4, $title, 0xFFFFFFFF);
            $x += $w + 2;
        }
    }
}
