<?php
declare(strict_types=1);

use Surface\NutsAndBolts\Color;
use Terran\Math\Vector2;
use Terran\Scenes\TwoD\Camera2D;
use Terran\Scenes\TwoD\Scene2D;
use Terran\TerranException;
use Tests\Fixtures\Block2D;

it('places a Scene2D by its position', function () {
    [$world, $target] = canvasWorld();
    $root = new Scene2D('root');
    $block = new Block2D('b');
    $block->position = new Vector2(3.0, 3.0);
    $root->add($block);
    $world->setRoot($root);
    $world->render();

    expect(hexAt($target->framebuffer(), 3, 3))->toBe('FF0000FF')
        ->and(hexAt($target->framebuffer(), 2, 2))->toBe('000000FF');
});

it('turns +x towards +y by rotation', function () {
    [$world, $target] = canvasWorld();
    $root = new Scene2D('root');
    $block = new Block2D('b', 2.0, 1.0);
    $block->position = new Vector2(4.0, 4.0);
    $block->rotation = M_PI / 2;
    $root->add($block);
    $world->setRoot($root);
    $world->render();

    expect(hexAt($target->framebuffer(), 3, 4))->toBe('FF0000FF')
        ->and(hexAt($target->framebuffer(), 3, 5))->toBe('FF0000FF')
        ->and(hexAt($target->framebuffer(), 4, 4))->toBe('000000FF');
});

it('composes the parent chain', function () {
    [$world, $target] = canvasWorld();
    $root = new Scene2D('root');
    $root->position = new Vector2(2.0, 2.0);
    $root->scale = new Vector2(2.0, 2.0);
    $block = new Block2D('b');
    $block->position = new Vector2(1.0, 0.0);
    $root->add($block);
    $world->setRoot($root);
    $world->render();

    expect($block->globalPosition())->toEqual(new Vector2(4.0, 2.0))
        ->and(hexAt($target->framebuffer(), 4, 2))->toBe('FF0000FF')
        ->and(hexAt($target->framebuffer(), 5, 3))->toBe('FF0000FF')
        ->and(hexAt($target->framebuffer(), 3, 2))->toBe('000000FF');
});

it('draws by effective z, then tree order', function () {
    [$world, $target] = canvasWorld();
    $root = new Scene2D('root');
    $red = new Block2D('red', 1.0, 1.0, '#FF0000');
    $red->zIndex = 1;
    $blue = new Block2D('blue', 1.0, 1.0, '#0000FF');
    $root->add($red)->add($blue);
    $world->setRoot($root);
    $world->render();

    expect(hexAt($target->framebuffer(), 0, 0))->toBe('FF0000FF')
        ->and($red->effectiveZ())->toBe(1);
});

it('hides a subtree under an invisible Scene2D and multiplies modulate down the chain', function () {
    [$world, $target] = canvasWorld();
    $root = new Scene2D('root');
    $hidden = new Scene2D('hidden');
    $hidden->visible = false;
    $block = new Block2D('b');
    $hidden->add($block);
    $root->add($hidden);
    $root->modulate = 0.5;
    $faded = new Scene2D('faded');
    $faded->modulate = 0.5;
    $root->add($faded);
    $world->setRoot($root);
    $world->render();

    expect(hexAt($target->framebuffer(), 0, 0))->toBe('000000FF')
        ->and($block->visibleInTree())->toBeFalse()
        ->and($faded->effectiveModulate())->toBe(0.25);
});

it('draws through the current camera, and maps pixels back to world space', function () {
    [$world, $target] = canvasWorld(16, 16);
    $root = new Scene2D('root');
    $camera = new Camera2D('cam');
    $camera->position = new Vector2(10.0, 10.0);
    $camera->zoom = new Vector2(2.0, 2.0);
    $camera->makeCurrent();
    $block = new Block2D('b');
    $block->position = new Vector2(10.0, 10.0);
    $root->add($camera)->add($block);
    $world->setRoot($root);
    $world->render();
    $viewport = $world->viewport('main');

    expect($viewport->camera2d())->toBe($camera)
        ->and(hexAt($target->framebuffer(), 8, 8))->toBe('FF0000FF')
        ->and(hexAt($target->framebuffer(), 9, 9))->toBe('FF0000FF')
        ->and(hexAt($target->framebuffer(), 7, 7))->toBe('000000FF')
        ->and($viewport->toWorld(8.0, 8.0))->toBe([10.0, 10.0]);
});

it('keeps the camera centred when the window resizes', function () {
    [$world, $target] = canvasWorld(16, 16);
    $root = new Scene2D('root');
    $camera = (new Camera2D('cam'))->makeCurrent();
    $camera->position = new Vector2(10.0, 10.0);
    $block = new Block2D('b');
    $block->position = new Vector2(10.0, 10.0);
    $root->add($camera)->add($block);
    $world->setRoot($root);
    $target->resize(32, 32);
    $world->render();

    expect(hexAt($target->framebuffer(), 16, 16))->toBe('FF0000FF')
        ->and(hexAt($target->framebuffer(), 8, 8))->toBe('000000FF');
});

it('unbinds a removed camera, and a second current camera replaces the first', function () {
    [$world, $target] = canvasWorld(16, 16);
    $root = new Scene2D('root');
    $first = (new Camera2D('first'))->makeCurrent();
    $first->position = new Vector2(50.0, 50.0);
    $second = new Camera2D('second');
    $block = new Block2D('b');
    $block->position = new Vector2(10.0, 10.0);
    $root->add($first)->add($second)->add($block);
    $world->setRoot($root);
    $second->makeCurrent();
    $replaced = [$world->viewport('main')->camera2d(), $first->current()];
    $root->remove($second);
    $world->render();

    expect($replaced)->toBe([$second, false])
        ->and($world->viewport('main')->camera2d())->toBeNull()
        ->and(hexAt($target->framebuffer(), 10, 10))->toBe('FF0000FF');
});

it('binds a current camera when its viewport arrives after the root', function () {
    [$registry, $waiter, $promises] = testLoop();
    $world = new \Terran\World(new \Terran\GameLoop($registry, $waiter, $promises), null, null, [], null, assets());
    $root = new Scene2D('root');
    $camera = (new Camera2D('cam'))->makeCurrent();
    $root->add($camera);
    $world->setRoot($root);
    $target = fakeTarget();
    $world->addViewport('main', $target, drawing()->renderer('velvet', ['output' => $target]), Color::hex('#000000'));

    expect($world->viewport('main')->camera2d())->toBe($camera);
});

it('says which viewports exist when a current camera names one that does not', function () {
    [$world] = canvasWorld();
    $root = new Scene2D('root');
    $camera = (new Camera2D('cam', 'hud'))->makeCurrent();
    $root->add($camera);
    $world->setRoot($root);

    expect(fn () => $world->render())->toThrow(TerranException::class, "Camera2D 'cam' names viewport 'hud', which this World does not have. Viewports: main.");
});

it('refuses a zero zoom', function () {
    $camera = new Camera2D('cam');
    $camera->zoom = new Vector2(0.0, 1.0);

    expect(fn () => $camera->view(16, 16))->toThrow(TerranException::class, 'zoom');
});

it('binds a live camera when current is set true, and releases it when set false', function () {
    [$world] = canvasWorld(8, 8);
    $root = new Scene2D('root');
    $camera = new Camera2D('cam');
    $root->add($camera);
    $world->setRoot($root);
    $camera->current = true;
    $bound = $world->viewport('main')->camera2d();
    $camera->current = false;

    expect($bound)->toBe($camera)->and($world->viewport('main')->camera2d())->toBeNull();
});
