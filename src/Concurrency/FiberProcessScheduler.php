<?php

namespace ScrapyardIO\GameEngine\Concurrency;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\UX\Core\Node as UxNode;

/**
 * Cooperative process pass — Fiber when available, sync otherwise.
 */
class FiberProcessScheduler
{
    public function process(UxNode|EngineNode $root, float $delta): void
    {
        if ($root instanceof EngineNode) {
            if (class_exists(\Fiber::class)) {
                $fiber = new \Fiber(function () use ($root, $delta): void {
                    $root->processInternal($delta);
                });
                $fiber->start();

                while (! $fiber->isTerminated()) {
                    $fiber->resume();
                }

                return;
            }

            $root->processInternal($delta);

            return;
        }

        $root->process($delta);
    }
}
