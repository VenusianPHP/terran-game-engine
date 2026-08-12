<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\UX\Components\Controls\Toggle as UxToggle;

#[Signal('toggled', ['pressed' => 'bool'])]
class CheckButton extends UxToggle implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;
}