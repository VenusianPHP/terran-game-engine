<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

#[Signal('text_changed', ['text' => 'string'])]
#[Signal('text_submitted', ['text' => 'string'])]
class LineEdit extends Control
{
    public string $text = '';

    public string $placeholderText = '';

    public bool $editable = true;

    public int $caretColumn = 0;

    public bool $secret = false;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 120, 24));
    }

    public function setText(string $text): static
    {
        $this->text = $text;
        $this->caretColumn = min($this->caretColumn, strlen($text));
        $this->emit('text_changed', ['text' => $text]);

        return $this;
    }

    public function insertTextAtCaret(string $chunk): static
    {
        if (! $this->editable) {
            return $this;
        }

        $before = substr($this->text, 0, $this->caretColumn);
        $after = substr($this->text, $this->caretColumn);
        $this->text = $before.$chunk.$after;
        $this->caretColumn += strlen($chunk);
        $this->emit('text_changed', ['text' => $this->text]);

        return $this;
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), 0xFF1A1A1A);
        $display = $this->text === ''
            ? $this->placeholderText
            : ($this->secret ? str_repeat('*', strlen($this->text)) : $this->text);

        if ($display !== '') {
            $ctx->printLocal(4, 4, $display, $this->text === '' ? 0xFF666666 : 0xFFFFFFFF);
        }
    }
}
