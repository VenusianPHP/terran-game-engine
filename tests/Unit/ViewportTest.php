<?php
declare(strict_types=1);

use Surface\NutsAndBolts\Color;
use Terran\Viewport;
use Tests\Fixtures\RecordingScene;

it('renders the tree depth first into the target and presents once', function () {
    $target = fakeTarget(8, 8);
    $viewport = new Viewport('main', $target, drawing()->renderer('velvet', ['output' => $target]), Color::hex('#000000'));
    $root = new RecordingScene('root');
    $child = new RecordingScene('child');
    $child->draws = true;
    $root->add($child);

    $viewport->render($root);

    expect($viewport->size())->toBe([8, 8])
        ->and($root->hooks())->toBe(['draw'])
        ->and($child->hooks())->toBe(['draw'])
        ->and($target->presented)->toBe(1)
        ->and($target->framebuffer()->getPixel(1, 1))->toBe(0xFFFFFFFF)
        ->and($target->framebuffer()->getPixel(0, 0))->toBe(0x000000FF);
});

it('drops the frame and does not present when a draw throws', function () {
    $target = fakeTarget();
    $viewport = new Viewport('main', $target, drawing()->renderer('velvet', ['output' => $target]), Color::hex('#000000'));
    $root = new class('boom') extends \Terran\Scene {
        public function draw(\Surface\Contracts\Drawing\RenderingEngine $ge): void { throw new RuntimeException('boom'); }
    };

    expect(fn () => $viewport->render($root))->toThrow(RuntimeException::class, 'boom')
        ->and($target->presented)->toBe(0);
});

it('re-mints its engine when the target changes size, before drawing', function () {
    $target = fakeTarget(8, 8);
    $remint = fn ($t) => drawing()->renderer('velvet', ['output' => $t]);
    $viewport = new Viewport('main', $target, $remint($target), Color::hex('#000000'), $remint);
    $child = new RecordingScene('c');
    $child->draws = true;

    $target->resize(16, 4);
    $viewport->render($child);

    expect([$viewport->engine()->width(), $viewport->engine()->height()])->toBe([16, 4])
        ->and($target->framebuffer()->getPixel(1, 1))->toBe(0xFFFFFFFF)
        ->and($target->presented)->toBe(1);
});

it('skips a frame on a zero-sized target without presenting', function () {
    $target = fakeTarget(8, 8);
    $viewport = new Viewport('main', $target, drawing()->renderer('velvet', ['output' => $target]), Color::hex('#000000'));
    $target->resize(0, 0);
    $scene = new RecordingScene('s');

    $viewport->render($scene);

    expect($target->presented)->toBe(0)->and($scene->hooks())->toBe([]);
});

it('names the fix when a resized target has no way to re-mint', function () {
    $target = fakeTarget(8, 8);
    $viewport = new Viewport('main', $target, drawing()->renderer('velvet', ['output' => $target]), Color::hex('#000000'));
    $target->resize(16, 8);

    expect(fn () => $viewport->render(new RecordingScene('s')))
        ->toThrow(\Terran\TerranException::class, "Viewport 'main' changed size from 8x8 to 16x8 and has no remint closure to make an engine for it; pass one to World::addViewport().");
});

it('maps window coordinates to pixels one to one on a target that is not a staged window', function () {
    $target = fakeTarget(8, 8);
    $viewport = new Viewport('main', $target, drawing()->renderer('velvet', ['output' => $target]), Color::hex('#000000'), null, 'w');
    expect($viewport->toPixels(3.0, 5.0))->toBe([3.0, 5.0])->and($viewport->window())->toBe('w');
});

it('scales window points to pixels on a staged window', function () {
    $window = Mockery::mock(\Surface\Contracts\Windows\StagedWindow::class);
    $window->allows('pixelSize')->andReturn([200, 100]);
    $window->allows('size')->andReturn([100, 50]);
    $viewport = new Viewport('main', $window, Mockery::mock(\Surface\Contracts\Drawing\RenderingEngine::class), Color::hex('#000000'));

    expect($viewport->toPixels(10.0, 20.0))->toBe([20.0, 40.0]);
    Mockery::close();
});
