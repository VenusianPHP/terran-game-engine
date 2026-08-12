<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\UX\Components\Layout\Padding as UxPadding;

class MarginContainer extends UxPadding implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;
}