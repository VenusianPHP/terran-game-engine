<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\UX\Components\Controls\ListView as UxListView;

class ItemList extends UxListView implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;
}