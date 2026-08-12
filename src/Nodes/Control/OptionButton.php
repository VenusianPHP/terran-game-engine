<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

#[Signal('item_selected', ['index' => 'int'])]
class OptionButton extends BaseButton
{
    /** @var list<string> */
    protected array $items = [];

    protected int $selected = -1;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 96, 24));
    }

    public function addItem(string $label): static
    {
        $this->items[] = $label;

        if ($this->selected < 0) {
            $this->selected = 0;
        }

        return $this;
    }

    public function select(int $index): static
    {
        if ($index < 0 || $index >= count($this->items)) {
            return $this;
        }

        $this->selected = $index;
        $this->emit('item_selected', ['index' => $index]);

        return $this;
    }

    public function getSelected(): int
    {
        return $this->selected;
    }

    public function getItemText(int $index): string
    {
        return $this->items[$index] ?? '';
    }

    protected function draw(PaintContext $ctx): void
    {
        parent::draw($ctx);
        $text = $this->getItemText($this->selected);

        if ($text !== '') {
            $ctx->printLocal(4, 4, $text, 0xFFFFFFFF);
        }
    }
}
