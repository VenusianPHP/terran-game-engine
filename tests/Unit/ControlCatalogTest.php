<?php

use ScrapyardIO\GameEngine\Nodes\Control as C;
use ScrapyardIO\GameEngine\Nodes\Control\Control;

$classes = array_values(array_filter(
    array_map(
        static fn (string $file): string => 'ScrapyardIO\\GameEngine\\Nodes\\Control\\'.basename($file, '.php'),
        glob(__DIR__.'/../../src/Nodes/Control/*.php') ?: [],
    ),
    static fn (string $class): bool => class_exists($class),
));

it('autoloads Control catalog classes', function (string $class): void {
    $ref = new ReflectionClass($class);

    if ($ref->isAbstract()) {
        expect($ref->isAbstract())->toBeTrue();

        return;
    }

    $instance = $ref->newInstance();
    expect($instance)->toBeInstanceOf(\ScrapyardIO\UX\Core\UIComponent::class)
        ->and($instance)->toBeInstanceOf(\ScrapyardIO\GameEngine\Contracts\EngineNode::class);
})->with($classes);

it('emits pressed from BaseButton subclass', function (): void {
    $btn = new C\LinkButton('ok');
    $hits = 0;
    $listener = new class
    {
        public int $hits = 0;

        public function onPressed(): void
        {
            $this->hits++;
        }
    };
    $btn->connect('pressed', $listener, 'onPressed');
    $btn->setPressed(true);

    expect($listener->hits)->toBe(1);
});

it('emits value_changed from Range subclass', function (): void {
    $bar = new C\SpinBox('spin');
    $listener = new class
    {
        public float $value = -1;

        public function onValue(float $value): void
        {
            $this->value = $value;
        }
    };
    $bar->connect('value_changed', $listener, 'onValue');
    $bar->setValue(42);

    expect($listener->value)->toBe(42.0);
});

it('positions children in BoxContainer layout', function (): void {
    $box = new C\BoxContainer('row');
    $box->vertical = false;
    $box->separation = 4;
    $box->setSize(200, 40);

    $a = new C\ColorRect('a');
    $a->setSize(20, 20);
    $b = new C\ColorRect('b');
    $b->setSize(30, 20);

    $box->addChild($a);
    $box->addChild($b);
    $box->layout($box->size());

    expect($a->rect()->x)->toBe(0)
        ->and($a->rect()->y)->toBe(0)
        ->and($b->rect()->x)->toBe(24)
        ->and($b->rect()->y)->toBe(0);
});
