<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\UX\Components\Layout\Align as UxAlign;

class CenterContainer extends UxAlign implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;
}