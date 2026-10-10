<?php
declare(strict_types=1);

use Terran\GameLoop;
use Terran\TerranException;
use Tests\Fixtures\RecordingScene;

function gameLoop(float $hz = 60.0): GameLoop
{
    [$registry, $waiter, $promises] = testLoop();

    return new GameLoop($registry, $waiter, $promises, null, $hz);
}

it('knows its physics rate and fixed step', function () {
    $loop = gameLoop(50.0);
    expect($loop->physicsHz())->toBe(50.0)->and($loop->step())->toBe(1 / 50);
});

it('refuses a physics rate that is not positive', function () {
    expect(fn () => gameLoop(0.0))->toThrow(TerranException::class, 'physics_hz is a positive number of steps per second, got 0.');
});

it('readies a scene on attach, once, and steps it at the fixed step while running', function () {
    $loop = gameLoop(100.0);
    $scene = new RecordingScene('s');
    $loop->attach($scene);
    $loop->attach($scene);

    $loop->at(0.055, fn () => $loop->stop());
    $loop->run();

    $steps = array_filter($scene->calls, fn ($c) => $c[0] === 'physicsProcess');
    expect($scene->hooks()[0])->toBe('ready')
        ->and(count(array_filter($scene->hooks(), fn ($h) => $h === 'ready')))->toBe(1)
        ->and(count($steps))->toBeGreaterThanOrEqual(4)->toBeLessThanOrEqual(6)
        ->and(array_unique(array_column($steps, 1)))->toBe([1 / 100]);
});

it('exits a scene on detach, once, and ignores a scene it never had', function () {
    $loop = gameLoop();
    $scene = new RecordingScene('s');
    $stranger = new RecordingScene('x');
    $loop->attach($scene);
    $loop->detach($scene);
    $loop->detach($scene);
    $loop->detach($stranger);

    expect($scene->hooks())->toBe(['ready', 'exit'])
        ->and($stranger->hooks())->toBe([])
        ->and($loop->attached())->toBe([]);
});

it('tells wall time from construction', function () {
    $loop = gameLoop();
    $a = $loop->now();
    usleep(2_000);
    expect($loop->now())->toBeGreaterThan($a);
});

/** A GameLoop on a clock the test moves. */
function clockedLoop(float $hz = 60.0, int $max = 8, ?int &$now = null): GameLoop
{
    $now = 0;
    [$registry, $waiter, $promises] = testLoop();

    return new GameLoop($registry, $waiter, $promises, null, $hz, $max, function () use (&$now): int { return $now; });
}

it('folds jittered elapsed time into whole steps and keeps the remainder', function () {
    $now = 0;
    $loop = clockedLoop(60.0, 8, $now);
    $body = new class('body') extends \Terran\Scene {
        public float $x = 0.0;
        public int $steps = 0;
        public function physicsProcess(float $delta): void { $this->x += 120.0 * $delta; $this->steps++; }
    };
    $loop->attach($body);

    mt_srand(2610);
    $loop->advance(0);
    $total = 0;
    for ($i = 0; $i < 1000; $i++) {
        $total += 16_666_667 + mt_rand(-6_000_000, 6_000_000);
        $loop->advance($total);
    }

    $expected_steps = intdiv($total, $loop->stepNs());
    expect($body->steps)->toBe($expected_steps)
        ->and($body->x)->toEqualWithDelta(120.0 * $expected_steps / 60, 1e-9);
});

it('caps the steps one advance runs and drops the backlog', function () {
    $now = 0;
    $loop = clockedLoop(60.0, 8, $now);
    $scene = new RecordingScene('s');
    $loop->attach($scene);

    $loop->advance(0);
    expect($loop->advance(1_000_000_000))->toBe(8)
        ->and($loop->advance(1_000_000_000 + $loop->stepNs()))->toBe(1);
});

it('runs zero steps on the first advance, which only sets the baseline', function () {
    $loop = clockedLoop();
    expect($loop->advance(5_000_000_000))->toBe(0);
});

it('calls after-step callbacks once per step, in order, and marks the physics phase', function () {
    $now = 0;
    $loop = clockedLoop(100.0, 8, $now);
    $seen = [];
    $probe = new class('probe') extends \Terran\Scene {
        public array $phases = [];
        public ?GameLoop $loop = null;
        public function physicsProcess(float $delta): void { $this->phases[] = $this->loop->inPhysics(); }
    };
    $probe->loop = $loop;
    $loop->attach($probe);
    $loop->afterStep(function () use (&$seen, $loop) { $seen[] = ['a', $loop->inPhysics()]; });
    $loop->afterStep(function () use (&$seen) { $seen[] = ['b', null]; });

    $loop->advance(0);
    $loop->advance(2 * $loop->stepNs());

    expect($seen)->toBe([['a', false], ['b', null], ['a', false], ['b', null]])
        ->and($probe->phases)->toBe([true, true])
        ->and($loop->inHooks())->toBeFalse();
});

it('refuses a wait inside a hook pass, and allows one inside a loop fiber', function () {
    $loop = gameLoop();
    $promise = $loop->promise();

    expect(fn () => $loop->hooks(fn () => $loop->await($promise)))
        ->toThrow(TerranException::class, 'A Scene hook waited on a promise.');

    $task = $loop->hooks(fn () => $loop->async(fn () => $loop->await($promise)));
    expect($task->settled())->toBeFalse();
    $promise->resolve(7);
    $loop->await($promise);   // settle the promise on the loop before waiting on the task that awaits it
    $loop->until(fn () => $task->settled());
    expect($task->wait())->toBe(7);
});

it('refuses a step cap below one', function () {
    [$registry, $waiter, $promises] = testLoop();
    expect(fn () => new GameLoop($registry, $waiter, $promises, null, 60.0, 0))
        ->toThrow(TerranException::class, 'max_physics_steps is at least 1, got 0.');
});

it('skips physics for a scene detached earlier in the same step', function () {
    $loop = clockedLoop();
    $b = new RecordingScene('b');
    $a = new class('a') extends \Terran\Scene {
        public ?GameLoop $loop = null;
        public ?\Terran\Scene $victim = null;
        public function physicsProcess(float $delta): void { $this->loop->detach($this->victim); }
    };
    $a->loop = $loop;
    $a->victim = $b;
    $loop->attach($a);
    $loop->attach($b);

    $loop->advance(0);
    $loop->advance($loop->stepNs());

    expect($b->hooks())->toBe(['ready', 'exit']);
});
