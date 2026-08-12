<?php

namespace ScrapyardIO\GameEngine\Contracts;

use ScrapyardIO\GameEngine\Input\Events\InputEvent;
use ScrapyardIO\GameEngine\SceneTree;

/**
 * Godot-like lifecycle surface shared by engine Node / CanvasItem / Control roots.
 */
interface EngineNode
{
    public function enterTreeInternal(SceneTree $tree): void;

    public function exitTreeInternal(): void;

    public function processInternal(float $delta): void;

    public function physicsProcessInternal(float $delta): void;

    public function inputInternal(InputEvent $event): void;

    public function isInsideTree(): bool;

    public function getTree(): ?SceneTree;
}
