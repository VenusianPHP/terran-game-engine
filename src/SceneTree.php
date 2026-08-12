<?php

namespace ScrapyardIO\GameEngine;

use ScrapyardIO\GameEngine\Contracts\EngineNode;
use ScrapyardIO\GameEngine\Input\Events\InputEvent;
use ScrapyardIO\GameEngine\Nodes\Node2D\PhysicsBounds2D;
use ScrapyardIO\GameEngine\Physics\PhysicsServer;
use ScrapyardIO\Tubes\Canvas\Canvas;
use ScrapyardIO\Tubes\Canvas\OSWindow;
use ScrapyardIO\Tubes\Rendering\Renderer2D;
use ScrapyardIO\UX\Core\Node as UxNode;
use ScrapyardIO\UX\Core\Scene;

/**
 * Owns the engine root and drives input → physics → process → paint → present.
 */
class SceneTree extends Scene
{
    protected PhysicsServer $physicsServer;

    public function __construct(
        protected ?Renderer2D $renderer = null,
        ?PhysicsServer $physicsServer = null,
    ) {
        parent::__construct();
        $this->physicsServer = $physicsServer ?? new PhysicsServer;
    }

    public function setRenderer(Renderer2D $renderer): static
    {
        $this->renderer = $renderer;

        return $this;
    }

    public function renderer(): ?Renderer2D
    {
        return $this->renderer;
    }

    public function physicsServer(): PhysicsServer
    {
        return $this->physicsServer;
    }

    public function setRoot(UxNode $root): static
    {
        $current = $this->root();

        if ($current instanceof EngineNode && $current->isInsideTree()) {
            $current->exitTreeInternal();
        } elseif (! is_null($current) && $current->isMounted()) {
            $current->unmount();
        }

        // Bypass parent setRoot mount — engine owns enter/ready order.
        $ref = new \ReflectionClass(Scene::class);
        $prop = $ref->getProperty('root');
        $prop->setAccessible(true);
        $prop->setValue($this, $root);
        $this->markNeedsLayout();

        if ($root instanceof EngineNode) {
            $root->enterTreeInternal($this);
        } else {
            $root->mount();
        }

        return $this;
    }

    public function dispatchInput(InputEvent $event): void
    {
        $root = $this->root();

        if ($root instanceof EngineNode) {
            $root->inputInternal($event);

            return;
        }
    }

    public function process(float $dt): void
    {
        $root = $this->root();

        if ($root instanceof EngineNode) {
            $root->processInternal($dt);

            return;
        }

        parent::process($dt);
    }

    public function physicsStep(float $physicsDelta): void
    {
        $root = $this->root();

        if (! $root instanceof EngineNode) {
            return;
        }

        if ($root instanceof PhysicsBounds2D) {
            $root->submitPhysicsStep($this->physicsServer, $physicsDelta);
        } else {
            $this->walkPhysicsBounds($root, $physicsDelta);
        }

        $root->physicsProcessInternal($physicsDelta);
    }

    protected function walkPhysicsBounds(EngineNode $node, float $delta): void
    {
        if ($node instanceof PhysicsBounds2D) {
            $node->submitPhysicsStep($this->physicsServer, $delta);
        }

        if (! method_exists($node, 'children')) {
            return;
        }

        foreach ($node->children() as $child) {
            if ($child instanceof EngineNode) {
                $this->walkPhysicsBounds($child, $delta);
            }
        }
    }

    public function drawFrame(): void
    {
        $canvas = $this->canvas();
        $renderer = $this->renderer;

        if (is_null($canvas) || is_null($renderer)) {
            return;
        }

        $framebuffer = $canvas->framebuffer();
        $renderer->setFramebuffer($framebuffer);
        $this->paint($renderer);
        $renderer->unsetFramebuffer();
    }

    public function present(): void
    {
        $canvas = $this->canvas();

        if (is_null($canvas)) {
            return;
        }

        $canvas->present();

        if ($canvas instanceof OSWindow) {
            $canvas->pollEvents();
        }
    }

    public function bindCanvas(Canvas $canvas): static
    {
        return $this->attach($canvas);
    }
}
