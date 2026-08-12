<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Geometry\Rect;

class CodeEdit extends TextEdit
{
    public bool $guttersDrawLineNumbers = true;

    /** @var array<string, bool> */
    protected array $breakpoints = [];

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 200, 120));
    }

    public function setLineAsBreakpoint(int $line, bool $breakpoint = true): static
    {
        if ($breakpoint) {
            $this->breakpoints[(string) $line] = true;
        } else {
            unset($this->breakpoints[(string) $line]);
        }

        return $this;
    }

    public function isLineBreakpointed(int $line): bool
    {
        return isset($this->breakpoints[(string) $line]);
    }
}
