<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

#[Signal('text_changed')]
class TextEdit extends Control
{
    public string $text = '';

    public bool $editable = true;

    /** @var list<string> */
    protected array $lines = [''];

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 160, 80));
    }

    public function setText(string $text): static
    {
        $this->text = $text;
        $this->lines = $text === '' ? [''] : explode("\n", $text);
        $this->emit('text_changed');

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getLineCount(): int
    {
        return count($this->lines);
    }

    public function getLine(int $line): string
    {
        return $this->lines[$line] ?? '';
    }

    protected function draw(PaintContext $ctx): void
    {
        $r = $this->rect();
        $ctx->fillRectLocal(0, 0, max(1, $r->width), max(1, $r->height), 0xFF121212);
        $y = 4;

        foreach ($this->lines as $line) {
            $ctx->printLocal(4, $y, $line, 0xFFFFFFFF);
            $y += 10;

            if ($y > $r->height) {
                break;
            }
        }
    }
}
