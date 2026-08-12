<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\UX\Geometry\Rect;

#[Signal('id_pressed', ['id' => 'int'])]
class PopupMenu extends Control
{
    /** @var list<array{id: int, text: string, separator: bool}> */
    protected array $items = [];

    protected bool $visiblePopup = false;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 120, 80));
        $this->setVisible(false);
    }

    public function addItem(string $label, int $id = -1): static
    {
        $this->items[] = [
            'id' => $id < 0 ? count($this->items) : $id,
            'text' => $label,
            'separator' => false,
        ];

        return $this;
    }

    public function addSeparator(): static
    {
        $this->items[] = ['id' => -1, 'text' => '', 'separator' => true];

        return $this;
    }

    public function popup(): static
    {
        $this->visiblePopup = true;
        $this->setVisible(true);

        return $this;
    }

    public function hidePopup(): static
    {
        $this->visiblePopup = false;
        $this->setVisible(false);

        return $this;
    }

    public function itemCount(): int
    {
        return count($this->items);
    }

    public function activateItem(int $index): static
    {
        if (! isset($this->items[$index]) || $this->items[$index]['separator']) {
            return $this;
        }

        $this->emit('id_pressed', ['id' => $this->items[$index]['id']]);
        $this->hidePopup();

        return $this;
    }
}
