<?php

namespace ScrapyardIO\GameEngine\Concurrency;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\UX\Core\Node as UxNode;

/**
 * Cooperative process pass.
 *
 * A new Fiber every frame without Fiber::suspend() retained ~168 bytes/frame
 * (Metal Tetriminos soak, debug-641660). Call processInternal directly until
 * a real yield exists.
 */
class FiberProcessScheduler
{
    public function process(UxNode|EngineNode $root, float $delta): void
    {
        if ($root instanceof EngineNode) {
            $root->processInternal($delta);

            return;
        }

        $root->process($delta);
    }
}
