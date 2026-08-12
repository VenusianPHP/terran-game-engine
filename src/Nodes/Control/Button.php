<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\UX\Components\Controls\Button as UxButton;

#[Signal('pressed')]
class Button extends UxButton implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;
}