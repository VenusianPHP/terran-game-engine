<?php
declare(strict_types=1);

use Terran\Autoload\Autoload;
use Terran\Autoload\AutoloadRegistry;
use Terran\GameLoop;
use Terran\Scene;
use Terran\TerranException;
use Terran\World;
use Tests\Fixtures\RecordingScene;
use Voyager\Vessel\ControlPanel;

final class ScoreKeeper { public int $score = 0; }
final class Settings { public string $mode = 'easy'; }
class Hud extends RecordingScene {}

#[Autoload(ScoreKeeper::class)]
#[Autoload(Hud::class, as: 'hud')]
class Level extends Scene {}

class BossLevel extends Level {}

function autoloadWorld(array $world_wide = []): World
{
    [$registry, $waiter, $promises] = testLoop();

    return new World(new GameLoop($registry, $waiter, $promises), null, new AutoloadRegistry(new ControlPanel()), $world_wide);
}

it('inherits declarations from parent classes', function () {
    expect(BossLevel::declaredAutoloads())->toBe([ScoreKeeper::class => null, Hud::class => 'hud']);
});

it('resolves a service once and shares it between scenes', function () {
    $world = autoloadWorld();
    $root = new class('root') extends Scene {};
    $a = new Level('a');
    $b = new BossLevel('b');
    $root->add($a)->add($b);
    $world->setRoot($root);

    $a->autoload(ScoreKeeper::class)->score = 5;

    expect($b->autoload(ScoreKeeper::class)->score)->toBe(5);
});

it('adds a Scene autoload as a child of the root, readied before the scenes that declared it', function () {
    $world = autoloadWorld();
    $root = new RecordingScene('root');
    $level = new Level('level');
    $root->add($level);
    $world->setRoot($root);

    $hud = $root->find('hud');
    expect($hud)->toBeInstanceOf(Hud::class)
        ->and($level->autoload(Hud::class))->toBe($hud)
        ->and($hud->hooks())->toBe(['ready'])
        ->and(array_map(fn ($s) => $s->name, $world->loop()->attached()))->toBe(['hud', 'root', 'level']);
});

it('resolves world-wide autoloads for every scene', function () {
    $world = autoloadWorld([Settings::class => null]);
    $root = new class('root') extends Scene {};
    $world->setRoot($root);

    expect($root->autoload(Settings::class)->mode)->toBe('easy');
});

it('refuses a class the scene does not declare, and a scene that is not live', function () {
    $world = autoloadWorld();
    $root = new class('root') extends Scene {};
    $world->setRoot($root);
    $loose = new Level('loose');

    expect(fn () => $root->autoload(ScoreKeeper::class))->toThrow(TerranException::class, "'root' does not declare #[Autoload(ScoreKeeper::class)] and terran.autoloads does not list it.")
        ->and(fn () => $loose->autoload(ScoreKeeper::class))->toThrow(TerranException::class, "'loose' is not in a live tree; autoloads resolve once it is.");
});

it('needs a registry when a live scene declares autoloads', function () {
    [$registry, $waiter, $promises] = testLoop();
    $world = new World(new GameLoop($registry, $waiter, $promises));
    expect(fn () => $world->setRoot(new Level('level')))->toThrow(TerranException::class, "'level' declares autoloads but this World has no AutoloadRegistry.");
});

it('forgets services when the world is destroyed', function () {
    $world = autoloadWorld();
    $root = new Level('root');
    $world->setRoot($root);
    $root->autoload(ScoreKeeper::class);

    $world->destroy();

    expect($world->autoloads()->all())->toBe([]);
});

it('builds a new scene autoload after the old one was removed', function () {
    $world = autoloadWorld();
    $root = new RecordingScene('root');
    $root->add(new Level('one'));
    $world->setRoot($root);
    $old = $root->find('hud');
    $root->remove($old);

    $root->add(new Level('two'));

    expect($root->find('hud'))->not->toBe($old)
        ->and($root->find('hud')->freed())->toBeFalse()
        ->and($root->find('two')->autoload(Hud::class))->toBe($root->find('hud'))
        ->and($old->hooks())->toBe(['ready', 'exit']);
});

#[Autoload(Hud::class, as: 'overlay')]
class OverlayLevel extends Scene {}

it('refuses one scene autoload under two aliases', function () {
    $world = autoloadWorld();
    $root = new class('root') extends Scene {};
    $root->add(new Level('level'))->add(new OverlayLevel('other'));

    expect(fn () => $world->setRoot($root))->toThrow(TerranException::class, "Hud autoloads once per World and is already 'hud'; OverlayLevel asks for it as 'overlay'. Give every #[Autoload(Hud::class)] the same alias.");
});
