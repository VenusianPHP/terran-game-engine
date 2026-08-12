<?php

use ScrapyardIO\GameEngine\Input\Events\InputEventAction;
use ScrapyardIO\GameEngine\Input\Events\InputEventKey;
use ScrapyardIO\GameEngine\Input\InputServer;
use ScrapyardIO\GameEngine\Nodes\Node;
use ScrapyardIO\GameEngine\SceneTree;
use ScrapyardIO\Tubes\HumanInput\EngineInput;
use ScrapyardIO\Tubes\HumanInput\Keyboard;
use ScrapyardIO\Tubes\Inputs\InputHandler;
use ScrapyardIO\Tubes\Rendering\SoftRenderer2D;

final class FakeInputHandler extends InputHandler
{
    public function __construct(Keyboard $keyboard)
    {
        $this->keyboard = $keyboard;
    }

    public function poll(): static
    {
        return $this;
    }
}

it('maps keys to actions via EngineInput handler', function (): void {
    $tree = new SceneTree(new SoftRenderer2D);
    $root = new class extends Node
    {
        public array $actions = [];

        protected function input($event): void
        {
            if ($event instanceof InputEventAction && $event->pressed) {
                $this->actions[] = $event->action;
            }
        }
    };
    $tree->setRoot($root);

    $keyboard = new Keyboard(['ArrowRight' => true]);
    $server = new InputServer;
    $server->map()->actionAddKey('ui_right', 'ArrowRight');
    $server->bindEngineInput(new EngineInput(new FakeInputHandler($keyboard)));
    $server->pump($tree);

    expect($server->isActionPressed('ui_right'))->toBeTrue()
        ->and($root->actions)->toContain('ui_right');
});

it('accepts injected key events without gfx brands', function (): void {
    $tree = new SceneTree(new SoftRenderer2D);
    $tree->setRoot(new Node('root'));
    $server = new InputServer;
    $server->map()->actionAddKey('jump', 'Space');
    $server->inject($tree, new InputEventKey('Space', true));

    expect($server->isActionPressed('jump'))->toBeTrue();
});
