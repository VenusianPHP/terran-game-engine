<?php

use ScrapyardIO\GameEngine\Nodes\Node;
use ScrapyardIO\GameEngine\SceneTree;
use ScrapyardIO\GameEngine\Signals\Signal;
use ScrapyardIO\Tubes\Rendering\SoftRenderer2D;
use ScrapyardIO\UX\Core\PaintContext;

#[Signal('ping')]
class SignalProbeNode extends Node
{
    public array $log = [];

    protected function enterTree($tree): void
    {
        $this->log[] = 'enter';
    }

    public function ready(): void
    {
        $this->log[] = 'ready';
    }

    public function process(float $dt): void
    {
        $this->log[] = 'process';
    }

    protected function physicsProcess(float $delta): void
    {
        $this->log[] = 'physics';
    }

    protected function exitTree(): void
    {
        $this->log[] = 'exit';
    }
}

it('runs enter then ready then process/physics then exit', function (): void {
    $tree = new SceneTree(new SoftRenderer2D);
    $node = new SignalProbeNode('probe');
    $tree->setRoot($node);

    expect($node->isInsideTree())->toBeTrue()
        ->and($node->log)->toBe(['enter', 'ready']);

    $tree->process(0.016);
    $tree->physicsStep(0.016);

    expect($node->log)->toContain('process')->toContain('physics');

    $tree->setRoot(new Node('replacement'));

    expect($node->isInsideTree())->toBeFalse()
        ->and($node->log)->toContain('exit');
});

it('connects and emits signals', function (): void {
    $tree = new SceneTree(new SoftRenderer2D);
    $a = new SignalProbeNode('a');
    $b = new class extends Node
    {
        public int $hits = 0;

        public function onPing(): void
        {
            $this->hits++;
        }
    };

    $tree->setRoot($a);
    $a->addChild($b);
    $a->connect('ping', $b, 'onPing');
    $a->emit('ping');

    expect($b->hits)->toBe(1);
});
