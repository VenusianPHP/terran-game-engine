<?php
declare(strict_types=1);

use Surface\Contracts\Windows\Mail\QuitRequested;
use Surface\Contracts\Windows\Mail\WindowClosed;
use Terran\GameEngine;
use Terran\GameLoop;
use Terran\Scene;
use Terran\Targets\OwnedTarget;
use Terran\Targets\TargetOpener;
use Terran\TerranException;
use Terran\World;
use Tests\Fixtures\FakeTarget;
use Tests\Fixtures\RecordingScene;
use Voyager\Config\Repository;
use Voyager\Contracts\Sketches\SketchLoopResult;
use Voyager\Sketches\SketchRegistry;
use Voyager\Vessel\ControlPanel;

/** An opener whose only kind is 'fake': a FakeTarget, remembered so the test can inspect it. */
function fakeOpener(array &$targets, array &$closed): TargetOpener
{
    $opener = new TargetOpener(new ControlPanel());

    return $opener->extend('fake', function (string $name, array $config) use (&$targets, &$closed): OwnedTarget {
        $targets[$name] = new FakeTarget($config['width'] ?? 16, $config['height'] ?? 8);

        return new OwnedTarget($name, $targets[$name], $config['engine'] ?? null, $config['window'] ?? null, function () use ($name, &$closed): void { $closed[] = $name; }, $config['alive'] ?? null);
    });
}

function engine(array $terran, array &$targets = [], array &$closed = []): GameEngine
{
    [$registry, $waiter, $promises] = testLoop();

    return new GameEngine(new World(new GameLoop($registry, $waiter, $promises)), fakeOpener($targets, $closed), drawing(), new Repository(['terran' => $terran]));
}

it('is the sketch named game', function () {
    expect(SketchRegistry::nameFor(GameEngine::class))->toBe('game');
});

it('opens every configured target, gives each a viewport on its engine, and roots the main scene', function () {
    $targets = [];
    $engine = engine([
        'main_scene' => RecordingScene::class,
        'targets' => [
            'main' => ['kind' => 'fake', 'width' => 32, 'height' => 16, 'engine' => 'velvet'],
            'side' => ['kind' => 'fake'],
        ],
    ], $targets);

    $engine->boot();

    expect(array_keys($engine->targets()))->toBe(['main', 'side'])
        ->and(array_keys($engine->world()->viewports()))->toBe(['main', 'side'])
        ->and($engine->world()->viewports()['main']->size())->toBe([32, 16])
        ->and($engine->world()->viewports()['main']->engine()->name())->toBe('velvet')
        ->and($engine->world()->root())->toBeInstanceOf(RecordingScene::class)
        ->and($engine->world()->root()->name)->toBe('main')
        ->and($engine->world()->root()->hooks())->toBe(['ready']);
});

it('says how to set a main scene when none is configured, before opening anything', function () {
    $targets = [];
    $engine = engine(['targets' => ['main' => ['kind' => 'fake']]], $targets);
    expect(fn () => $engine->boot())->toThrow(TerranException::class, "No main scene. Set terran.main_scene to a Scene class, or override mainScene().")
        ->and($targets)->toBe([]);
});

it('closes what it opened when a later target fails, and lets the drawing error through', function () {
    $targets = [];
    $closed = [];
    $engine = engine(['main_scene' => RecordingScene::class, 'targets' => [
        'first' => ['kind' => 'fake', 'engine' => 'velvet'],
        'second' => ['kind' => 'fake', 'engine' => 'hologram'],
    ]], $targets, $closed);

    expect(fn () => $engine->boot())->toThrow(\Surface\Contracts\Drawing\DrawingException::class, "No rendering engine named 'hologram'")
        ->and($closed)->toBe(['first', 'second'])
        ->and($engine->targets())->toBe([])
        ->and($engine->world()->root())->toBeNull();
});

it('refuses a main scene that is not a Scene', function () {
    $engine = engine(['main_scene' => stdClass::class, 'targets' => ['main' => ['kind' => 'fake']]]);
    expect(fn () => $engine->boot())->toThrow(TerranException::class, "terran.main_scene is a Scene class; stdClass is not.");
});

it('refuses to boot with no targets', function () {
    $engine = engine(['main_scene' => RecordingScene::class, 'targets' => []]);
    expect(fn () => $engine->boot())->toThrow(TerranException::class, "No output targets. config('terran.targets') is empty and openTargets() returned none.");
});

it('refuses two targets under one name from openTargets()', function () {
    [$registry, $waiter, $promises] = testLoop();
    $t = [];
    $c = [];
    $engine = new class(new World(new GameLoop($registry, $waiter, $promises)), fakeOpener($t, $c), drawing(), new Repository(['terran' => ['main_scene' => RecordingScene::class]])) extends GameEngine {
        protected function openTargets(): array
        {
            $one = $this->opener->open('main', ['kind' => 'fake']);
            $two = $this->opener->open('main', ['kind' => 'fake']);
            return [$one->name => $one, 'other' => $two];
        }
    };
    expect(fn () => $engine->boot())->toThrow(TerranException::class, "openTargets() returned the target 'main' under the key 'other'; keys are target names.");
});

it('ticks and renders each frame, and continues on unrelated mail', function () {
    $targets = [];
    $engine = engine(['main_scene' => RecordingScene::class, 'targets' => ['main' => ['kind' => 'fake']]], $targets);
    $engine->boot();

    $result = $engine->loop([new WindowClosed('somebody-elses-window')]);

    expect($result)->toBe(SketchLoopResult::CONTINUE)
        ->and($targets['main']->presented)->toBe(1)
        ->and($engine->world()->root()->hooks())->toBe(['ready', 'process', 'draw']);
});

it('stops when an owned window closes, or on QuitRequested', function () {
    $targets = [];
    $owned = engine(['main_scene' => RecordingScene::class, 'targets' => ['main' => ['kind' => 'fake', 'window' => 'main']]], $targets);
    $owned->boot();
    expect($owned->loop([new WindowClosed('main')]))->toBe(SketchLoopResult::STOP);

    $any = engine(['main_scene' => RecordingScene::class, 'targets' => ['main' => ['kind' => 'fake']]]);
    $any->boot();
    expect($any->loop([new QuitRequested()]))->toBe(SketchLoopResult::STOP);
});

it('stops without drawing once an owned target is gone, before its close reaches the mail', function () {
    $targets = [];
    $open = true;
    $engine = engine(['main_scene' => RecordingScene::class, 'targets' => ['main' => ['kind' => 'fake', 'window' => 'main', 'alive' => function () use (&$open): bool { return $open; }]]], $targets);
    $engine->boot();

    expect($engine->loop())->toBe(SketchLoopResult::CONTINUE);
    $open = false;

    expect($engine->loop())->toBe(SketchLoopResult::STOP)
        ->and($targets['main']->presented)->toBe(1)
        ->and($engine->world()->root()->hooks())->toBe(['ready', 'process', 'draw']);
});

it('shuts down: exits the tree, then closes every owned target, once', function () {
    $targets = [];
    $closed = [];
    $engine = engine(['main_scene' => RecordingScene::class, 'targets' => ['a' => ['kind' => 'fake'], 'b' => ['kind' => 'fake']]], $targets, $closed);
    $engine->boot();
    $root = $engine->world()->root();

    $engine->shutdown();
    $engine->shutdown();

    expect($root->hooks())->toBe(['ready', 'exit'])->and($closed)->toBe(['a', 'b'])->and($engine->targets())->toBe([]);
});

it('takes its refresh rate from config when set', function () {
    expect(engine(['refresh_rate' => 30.0])->refreshRate())->toBe(30.0)
        ->and(engine([])->refreshRate())->toBeNull();
});

it('lets a subclass pick its own targets and main scene', function () {
    [$registry, $waiter, $promises] = testLoop();
    $t = [];
    $c = [];
    $engine = new class(new World(new GameLoop($registry, $waiter, $promises)), fakeOpener($t, $c), drawing(), new Repository(['terran' => []])) extends GameEngine {
        protected function openTargets(): array
        {
            $hud = $this->opener->open('hud', ['kind' => 'fake', 'engine' => 'velvet']);
            return [$hud->name => $hud];
        }
        protected function mainScene(): Scene { return new RecordingScene('mine'); }
    };
    $engine->boot();
    expect(array_keys($engine->targets()))->toBe(['hud'])->and($engine->world()->root()->name)->toBe('mine');
});

it('reads input once a frame and hands the events to the tree before process()', function () {
    [$registry, $waiter, $promises] = testLoop();
    $map = new \Terran\Input\InputMap(['jump' => ['key:space']]);
    $world = new World(new GameLoop($registry, $waiter, $promises), null, null, [], $map);
    $human = new \Tests\Fixtures\FakeHumanInput(inputFrame());
    $t = [];
    $c = [];
    $engine = new GameEngine($world, fakeOpener($t, $c), drawing(), new Repository(['terran' => ['main_scene' => RecordingScene::class, 'targets' => ['main' => ['kind' => 'fake']]]]), new \Terran\Input\InputReader($human, $map));
    $engine->boot();
    $human->kb->update(\Surface\Contracts\HumanInput\Key::SPACE, true);

    $engine->loop([]);

    expect($human->reads['keyboard'])->toBe(1)->and($world->actions()->isPressed('jump'))->toBeTrue();
});

it('re-mints a viewport after its target resizes', function () {
    $targets = [];
    $engine = engine(['main_scene' => RecordingScene::class, 'targets' => ['main' => ['kind' => 'fake', 'width' => 8, 'height' => 8]]], $targets);
    $engine->boot();
    $targets['main']->resize(20, 10);

    $engine->loop([]);

    expect($engine->world()->viewports()['main']->engine()->width())->toBe(20);
});
