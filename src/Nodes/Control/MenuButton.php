<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class MenuButton extends BaseButton
{
    public string $text = '';

    protected ?PopupMenu $popup = null;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 64, 24));
    }

    public function getPopup(): PopupMenu
    {
        if (is_null($this->popup)) {
            $this->popup = new PopupMenu($this->name().'_popup');
        }

        return $this->popup;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        parent::draw($ctx);

        if ($this->text !== '') {
            $ctx->printLocal(4, 4, $this->text, 0xFFFFFFFF);
        }
    }
}
