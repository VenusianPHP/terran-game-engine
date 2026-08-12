<?php

namespace ScrapyardIO\GameEngine;

use ScrapyardIO\GameEngine\Concurrency\FiberProcessScheduler;
use ScrapyardIO\GameEngine\Input\InputServer;
use ScrapyardIO\GameEngine\Nodes\Node as EngineNodeBase;
use ScrapyardIO\Tubes\Canvas\Canvas;
use ScrapyardIO\Tubes\Rendering\Renderer2D;
use ScrapyardIO\UX\Core\Node as UxNode;

/**
 * Top-level entry: Canvas + Renderer2D + SceneTree + InputServer.
 *
 * Godot-shaped clocks: variable render delta, fixed physics TPS accumulator.
 */
class Game
{
    protected SceneTree $sceneTree;

    protected InputServer $inputServer;

    protected FiberProcessScheduler $scheduler;

    protected bool $running = false;

    public function __construct(
        protected Canvas $canvas,
        protected Renderer2D $renderer,
        protected float $targetFps = 60.0,
        protected float $physicsTps = 60.0,
        ?InputServer $inputServer = null,
        ?SceneTree $sceneTree = null,
    ) {
        $this->sceneTree = $sceneTree ?? new SceneTree($renderer);
        $this->sceneTree->bindCanvas($canvas)->setRenderer($renderer);
        $this->inputServer = $inputServer ?? new InputServer;
        $this->scheduler = new FiberProcessScheduler;
    }

    public function sceneTree(): SceneTree
    {
        return $this->sceneTree;
    }

    public function input(): InputServer
    {
        return $this->inputServer;
    }

    public function canvas(): Canvas
    {
        return $this->canvas;
    }

    public function setRoot(UxNode $root): static
    {
        $this->sceneTree->setRoot($root);

        return $this;
    }

    public function stop(): void
    {
        $this->running = false;
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Run a single frame (tests / cooperative sketches).
     */
    public function tick(float $renderDelta): void
    {
        $this->inputServer->pump($this->sceneTree, $this);

        static $accumulator = 0.0;
        $physicsStep = 1.0 / max(0.0001, $this->physicsTps);
        $accumulator += min($renderDelta, 0.25);

        while ($accumulator >= $physicsStep) {
            $this->sceneTree->physicsStep($physicsStep);
            $accumulator -= $physicsStep;
        }

        $root = $this->sceneTree->root();

        if ($root instanceof EngineNodeBase || $root instanceof \ScrapyardIO\GameEngine\Contracts\EngineNode) {
            $this->scheduler->process($root, $renderDelta);
        } else {
            $this->sceneTree->process($renderDelta);
        }

        $this->sceneTree->drawFrame();
        $this->sceneTree->present();
    }

    public function run(): void
    {
        $this->running = true;
        $targetFrameTime = 1.0 / max(0.0001, $this->targetFps);
        $previousTime = microtime(true);

        while ($this->running) {
            $frameStart = microtime(true);
            $renderDelta = min($frameStart - $previousTime, 0.25);
            $previousTime = $frameStart;

            $this->tick($renderDelta);

            $elapsed = microtime(true) - $frameStart;
            $sleepUs = (int) (($targetFrameTime - $elapsed) * 1_000_000);

            if ($sleepUs > 0) {
                usleep($sleepUs);
            }
        }
    }
}
