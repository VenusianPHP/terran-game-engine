<?php

namespace ScrapyardIO\GameEngine\Nodes\Concerns;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Input\Events\InputEvent;
use ScrapyardIO\GameEngine\SceneTree;
use ScrapyardIO\UX\Core\Node as UxNode;

/**
 * Godot enter_tree / ready / process / physics_process / input / exit_tree on UX trees.
 *
 * @mixin UxNode
 */
trait InteractsWithSceneTree
{
    protected ?SceneTree $sceneTree = null;

    protected bool $insideTree = false;

    protected bool $engineReadyCalled = false;

    /**
     * Godot-style: adding a child to a node already in the tree enters that child.
     * Suppresses UX mount/ready so enterTreeInternal owns ready order.
     */
    public function addChild(UxNode $child): static
    {
        if ($this->insideTree && ! is_null($this->sceneTree) && $child instanceof EngineNode) {
            $wasMounted = $this->mounted;
            $this->mounted = false;
            parent::addChild($child);
            $this->mounted = $wasMounted;
            $child->enterTreeInternal($this->sceneTree);

            return $this;
        }

        return parent::addChild($child);
    }

    /**
     * Godot-style: removing a child that is in the tree exits it first.
     */
    public function removeChild(UxNode $child): static
    {
        if ($child instanceof EngineNode && $child->isInsideTree()) {
            $child->exitTreeInternal();
        }

        return parent::removeChild($child);
    }

    public function clearChildren(): static
    {
        foreach ($this->children() as $child) {
            if ($child instanceof EngineNode && $child->isInsideTree()) {
                $child->exitTreeInternal();
            }
        }

        return parent::clearChildren();
    }

    public function isInsideTree(): bool
    {
        return $this->insideTree;
    }

    public function getTree(): ?SceneTree
    {
        return $this->sceneTree;
    }

    public function enterTreeInternal(SceneTree $tree): void
    {
        $this->sceneTree = $tree;
        $this->insideTree = true;

        if (method_exists($this, 'mount') && ! $this->isMounted()) {
            // Mark mounted without double-ready from UX path; engine owns ready order.
            $this->mounted = true;
        }

        $this->enterTree($tree);

        foreach ($this->children() as $child) {
            if ($child instanceof \ScrapyardIO\GameEngine\Contracts\EngineNode) {
                $child->enterTreeInternal($tree);
            } elseif ($child instanceof UxNode) {
                $child->mount();
            }
        }

        if (! $this->engineReadyCalled) {
            $this->engineReadyCalled = true;
            $this->ready();
        }
    }

    public function exitTreeInternal(): void
    {
        foreach (array_reverse($this->children()) as $child) {
            if ($child instanceof \ScrapyardIO\GameEngine\Contracts\EngineNode) {
                $child->exitTreeInternal();
            } elseif ($child instanceof UxNode && $child->isMounted()) {
                $child->unmount();
            }
        }

        if (method_exists($this, 'cleanupSignalConnectionsOnExit')) {
            $this->cleanupSignalConnectionsOnExit();
        }

        $this->exitTree();

        $this->sceneTree = null;
        $this->insideTree = false;
        $this->engineReadyCalled = false;

        if (property_exists($this, 'mounted')) {
            $this->mounted = false;
        }
    }

    public function processInternal(float $delta): void
    {
        if (method_exists($this, 'isVisible') && ! $this->isVisible()) {
            return;
        }

        $this->process($delta);

        foreach ($this->children() as $child) {
            if ($child instanceof \ScrapyardIO\GameEngine\Contracts\EngineNode) {
                $child->processInternal($delta);
            } elseif ($child instanceof UxNode) {
                $child->process($delta);
            }
        }
    }

    public function physicsProcessInternal(float $delta): void
    {
        $this->physicsProcess($delta);

        foreach ($this->children() as $child) {
            if ($child instanceof \ScrapyardIO\GameEngine\Contracts\EngineNode) {
                $child->physicsProcessInternal($delta);
            }
        }
    }

    public function inputInternal(InputEvent $event): void
    {
        if ($event->isHandled()) {
            return;
        }

        $this->input($event);

        foreach ($this->children() as $child) {
            if ($event->isHandled()) {
                return;
            }

            if ($child instanceof \ScrapyardIO\GameEngine\Contracts\EngineNode) {
                $child->inputInternal($event);
            }
        }
    }

    protected function enterTree(SceneTree $tree): void
    {
        //
    }

    protected function exitTree(): void
    {
        //
    }

    public function ready(): void
    {
        //
    }

    public function process(float $dt): void
    {
        // Engine overrides replace UX recursive default when using processInternal.
    }

    protected function physicsProcess(float $delta): void
    {
        //
    }

    protected function input(InputEvent $event): void
    {
        //
    }
}
