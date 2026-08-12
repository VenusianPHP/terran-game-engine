<?php

namespace ScrapyardIO\GameEngine\Nodes;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Nodes\Concerns\InteractsWithSceneTree;
use ScrapyardIO\GameEngine\Signals\HasSignals;
use ScrapyardIO\UX\Core\Node as UxNode;

/**
 * Engine tree node — extends UX Node with Godot lifecycle + signals.
 */
class Node extends UxNode implements EngineNode
{
    use HasSignals;
    use InteractsWithSceneTree;
}
