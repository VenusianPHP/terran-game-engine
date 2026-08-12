<?php

use ScrapyardIO\GameEngine\Nodes\Node2D\Area2D;
use ScrapyardIO\GameEngine\Nodes\Node2D\CharacterBody2D;
use ScrapyardIO\GameEngine\Nodes\Node2D\PhysicsBounds2D;
use ScrapyardIO\GameEngine\Nodes\Node2D\StaticBody2D;
use ScrapyardIO\GameEngine\Physics\PhysicsServer;
use ScrapyardIO\GameEngine\Resources\Shapes\RectangleShape2D;

it('steps CharacterBody2D velocity inside PhysicsBounds2D', function (): void {
    $bounds = new PhysicsBounds2D('world');
    $bounds->width = 200;
    $bounds->height = 200;

    $body = new CharacterBody2D('hero');
    $body->id = 1;
    $body->setShape(new RectangleShape2D(16, 16));
    $body->setPosition(10, 10);
    $body->setVelocity(100, 0);

    $bounds->addChild($body);

    $server = new PhysicsServer;
    $bounds->submitPhysicsStep($server, 0.1);

    expect($body->positionX)->toBeGreaterThan(10.0)
        ->and($body->positionX)->toBeLessThanOrEqual(200.0);
});

it('keeps StaticBody2D velocity at zero across PhysicsServer step', function (): void {
    $bounds = new PhysicsBounds2D('world');
    $bounds->width = 200;
    $bounds->height = 200;

    $wall = new StaticBody2D('wall');
    $wall->id = 2;
    $wall->setShape(new RectangleShape2D(40, 40));
    $wall->setPosition(80, 80);
    $wall->vx = 50;
    $wall->vy = 50;

    $bounds->addChild($wall);

    $server = new PhysicsServer;
    $bounds->submitPhysicsStep($server, 0.1);

    expect($wall->positionX)->toBe(80.0)
        ->and($wall->positionY)->toBe(80.0)
        ->and($wall->vx)->toBe(0.0)
        ->and($wall->vy)->toBe(0.0);
});

it('emits Area2D body_entered under PhysicsBounds2D fixed step', function (): void {
    $bounds = new PhysicsBounds2D('world');
    $bounds->width = 200;
    $bounds->height = 200;

    $area = new Area2D('zone');
    $area->setShape(new RectangleShape2D(40, 40));
    $area->setPosition(50, 50);

    $body = new CharacterBody2D('hero');
    $body->id = 3;
    $body->setShape(new RectangleShape2D(16, 16));
    // Start outside the area; one step should carry the body into the volume.
    $body->setPosition(20, 55);
    $body->setVelocity(200, 0);

    $bounds->addChild($area);
    $bounds->addChild($body);

    $listener = new class
    {
        public mixed $body = null;

        public function onEnter(object $body): void
        {
            $this->body = $body;
        }
    };
    $area->connect('body_entered', $listener, 'onEnter');

    $server = new PhysicsServer;
    $bounds->submitPhysicsStep($server, 0.2);

    // 20 + 200*0.2 = 60 → overlaps area [50,90) x [50,90)
    expect($listener->body)->toBe($body)
        ->and($body->positionX)->toBeGreaterThan(50.0);
});