<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\UX\Components\Layout\Row as UxRow;

class HBoxContainer extends UxRow implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;
}