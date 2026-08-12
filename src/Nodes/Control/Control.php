<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Core\UIComponent;
use ScrapyardIO\UX\Geometry\Rect;

/**
 * Godot Control base — extends UX UIComponent + engine lifecycle/signals.
 */
class Control extends UIComponent implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect);
    }

    protected function draw(PaintContext $ctx): void
    {
        //
    }
}
