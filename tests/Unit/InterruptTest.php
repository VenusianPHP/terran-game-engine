<?php
declare(strict_types=1);

use Terran\GameLoop;
use Terran\Interrupts\Interrupt;
use Terran\Interrupts\SceneInterrupt;
use Terran\Scene;
use Terran\TerranException;
use Terran\World;
use Voyager\Signals\SignalDispatcher;

#[Interrupt('hit', [Scene::class, 'int'])]
#[Interrupt('miss')]
class Paddle extends Scene {}

#[Interrupt('spin', ['float'])]
class SpinPaddle extends Paddle {}

class Scorer extends Scene
{
    public array $got = [];
    public function onHit(Scene $by, int $points): void { $this->got[] = [$by->name, $points]; }
}

function signalWorld(?SignalDispatcher &$signals = null): World
{
    [$registry, $waiter, $promises] = testLoop();
    $signals = new SignalDispatcher();

    return new World(new GameLoop($registry, $waiter, $promises), $signals);
}

it('reads declarations up the class chain', function () {
    expect(SpinPaddle::interrupts())->toBe(['spin' => ['float'], 'hit' => [Scene::class, 'int'], 'miss' => []]);
});

it('runs connections in order, closures and object methods alike', function () {
    $paddle = new Paddle('paddle');
    $scorer = new Scorer('scorer');
    $calls = [];
    $paddle->connect('hit', function (Scene $by, int $points) use (&$calls) { $calls[] = ['closure', $points]; });
    $paddle->connect('hit', $scorer, 'onHit');

    $paddle->emit('hit', $paddle, 3);

    expect($calls)->toBe([['closure', 3]])->and($scorer->got)->toBe([['paddle', 3]]);
});

it('dispatches a SceneInterrupt on the world\'s signals when live', function () {
    $world = signalWorld($signals);
    $heard = [];
    // A wildcard listener hears (name, payload); the payload of an object signal is [the signal].
    $signals->listen('terran.*', function (string $name, array $payload) use (&$heard) {
        [$signal] = $payload;
        $heard[] = [$name, $signal instanceof SceneInterrupt ? $signal->payload : null];
    });
    $root = new class('root') extends Scene {};
    $paddle = new Paddle('paddle');
    $root->add($paddle);
    $world->setRoot($root);

    $paddle->emit('miss');

    expect($heard)->toBe([['terran.root/paddle.miss', []]]);
});

it('names what it emits when asked for something else', function () {
    $paddle = new Paddle('paddle');
    expect(fn () => $paddle->emit('hti'))->toThrow(TerranException::class, "Paddle does not emit 'hti'. It emits: hit, miss.")
        ->and(fn () => $paddle->connect('hti', fn () => null))->toThrow(TerranException::class, "Paddle does not emit 'hti'. It emits: hit, miss.")
        ->and(fn () => (new Scorer('s'))->emit('x'))->toThrow(TerranException::class, "Scorer does not emit 'x'. It emits nothing; declare #[Interrupt('x')] on the class.");
});

it('checks the payload against the declaration', function () {
    $paddle = new Paddle('paddle');
    expect(fn () => $paddle->emit('hit', $paddle))->toThrow(TerranException::class, "Paddle's 'hit' carries 2 values (Terran\\Scene, int); emit() gave 1.")
        ->and(fn () => $paddle->emit('hit', 'nope', 1))->toThrow(TerranException::class, "Paddle's 'hit' takes Terran\\Scene as value 1; got string.")
        ->and(fn () => $paddle->emit('hit', $paddle, 1.5))->toThrow(TerranException::class, "Paddle's 'hit' takes int as value 2; got float.");
});

it('refuses an object target without the named method', function () {
    expect(fn () => (new Paddle('p'))->connect('hit', new Scorer('s'), 'onHti'))
        ->toThrow(TerranException::class, "Scorer has no method 'onHti'.");
});

it('disconnects one connection and keeps the rest', function () {
    $paddle = new Paddle('paddle');
    $calls = [];
    $first = function () use (&$calls) { $calls[] = 'first'; };
    $second = function () use (&$calls) { $calls[] = 'second'; };
    $paddle->connect('miss', $first)->connect('miss', $second)->disconnect('miss', $first);

    $paddle->emit('miss');

    expect($calls)->toBe(['second']);
});

it('drops a connection to a freed scene, and a freed scene loses its own', function () {
    $world = signalWorld();
    $root = new class('root') extends Scene {};
    $paddle = new Paddle('paddle');
    $scorer = new Scorer('scorer');
    $root->add($paddle)->add($scorer);
    $world->setRoot($root);
    $paddle->connect('hit', $scorer, 'onHit');

    $root->remove($scorer);
    $paddle->emit('hit', $paddle, 1);

    expect($scorer->got)->toBe([])->and($scorer->freed())->toBeTrue();

    $calls = [];
    $paddle->connect('miss', function () use (&$calls) { $calls[] = 'x'; });
    $root->remove($paddle);
    $paddle->emit('miss');
    expect($calls)->toBe([]);
});

it('keeps connections made before the scene went live', function () {
    $world = signalWorld();
    $root = new class('root') extends Scene {};
    $paddle = new Paddle('paddle');
    $calls = [];
    $paddle->connect('miss', function () use (&$calls) { $calls[] = 'x'; });
    $root->add($paddle);
    $world->setRoot($root);

    $paddle->emit('miss');

    expect($calls)->toBe(['x']);
});

it('drops a first-class callable bound to a freed scene', function () {
    $world = signalWorld();
    $root = new class('root') extends Scene {};
    $paddle = new Paddle('paddle');
    $scorer = new Scorer('scorer');
    $root->add($paddle)->add($scorer);
    $world->setRoot($root);
    $paddle->connect('hit', $scorer->onHit(...));

    $root->remove($scorer);
    $paddle->emit('hit', $paddle, 1);

    expect($scorer->got)->toBe([]);
});

it('disconnects a first-class callable made again from the same method', function () {
    $paddle = new Paddle('paddle');
    $scorer = new Scorer('scorer');
    $paddle->connect('hit', $scorer->onHit(...))->disconnect('hit', $scorer->onHit(...));

    $paddle->emit('hit', $paddle, 2);

    expect($scorer->got)->toBe([]);
});

it('keeps the right connections when a handler disconnects during emit', function () {
    $world = signalWorld();
    $root = new class('root') extends Scene {};
    $paddle = new Paddle('paddle');
    $gone = new Scorer('gone');
    $root->add($paddle)->add($gone);
    $world->setRoot($root);
    $calls = [];
    $self = null;
    $self = function () use (&$calls, &$self, $paddle) { $calls[] = 'self'; $paddle->disconnect('hit', $self); };
    $paddle->connect('hit', $self)->connect('hit', $gone, 'onHit')->connect('hit', function () use (&$calls) { $calls[] = 'last'; });
    $root->remove($gone);

    $paddle->emit('hit', $paddle, 1);
    $paddle->emit('hit', $paddle, 1);

    expect($calls)->toBe(['self', 'last', 'last']);
});

it('drops an array callable whose object is a freed scene', function () {
    $world = signalWorld();
    $root = new class('root') extends Scene {};
    $paddle = new Paddle('paddle');
    $scorer = new Scorer('scorer');
    $root->add($paddle)->add($scorer);
    $world->setRoot($root);
    $paddle->connect('hit', [$scorer, 'onHit']);

    $root->remove($scorer);
    $paddle->emit('hit', $paddle, 1);

    expect($scorer->got)->toBe([]);
});
