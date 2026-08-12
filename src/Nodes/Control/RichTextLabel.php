<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Rect;

class RichTextLabel extends Control
{
    public string $bbcodeText = '';

    public bool $bbcodeEnabled = true;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 120, 24));
    }

    public function setText(string $text): static
    {
        $this->bbcodeText = $text;

        return $this;
    }

    public function text(): string
    {
        return $this->bbcodeText;
    }

    public function getParsedText(): string
    {
        if (! $this->bbcodeEnabled) {
            return $this->bbcodeText;
        }

        return preg_replace('/\[[^\]]+\]/', '', $this->bbcodeText) ?? $this->bbcodeText;
    }

    protected function draw(PaintContext $ctx): void
    {
        $plain = $this->getParsedText();
        if ($plain === '') {
            return;
        }

        $ctx->printLocal(0, 0, $plain, 0xFFFFFFFF);
    }
}
