<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\UX\Components\Indicators\ProgressBar as UxProgressBar;

class ProgressBar extends UxProgressBar implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;
}