<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\UX\Components\Text\Label as UxLabel;
use ScrapyardIO\UX\Support\Color;

class Label extends UxLabel implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;

    public static function of(string $text, ?Color $ink = null): static
    {
        return new static($text, $ink);
    }
}