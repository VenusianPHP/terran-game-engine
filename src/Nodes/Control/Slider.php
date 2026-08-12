<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\UX\Components\Controls\Slider as UxSlider;

#[Signal('value_changed', ['value' => 'float'])]
class Slider extends UxSlider implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;
}