<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\UX\Components\ScrollView as UxScrollView;

class ScrollContainer extends UxScrollView implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;
}