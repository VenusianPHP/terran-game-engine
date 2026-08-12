<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Geometry\Rect;

class MenuBar extends Control
{
    /** @var list<MenuButton> */
    protected array $menus = [];

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 200, 24));
    }

    public function addMenu(MenuButton $menu): static
    {
        $this->menus[] = $menu;
        $this->addChild($menu);

        return $this;
    }

    /** @return list<MenuButton> */
    public function menus(): array
    {
        return $this->menus;
    }
}
