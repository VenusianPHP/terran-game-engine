<?php
declare(strict_types=1);
namespace Terran;

use Closure;
use Fiber;
use Voyager\Contracts\IOPools\MailHandler;
use Voyager\Contracts\IOPools\PromiseEngine;
use Voyager\Contracts\IOPools\Waiter;
use Voyager\IOPools\EventLoop;
use Voyager\IOPools\ResourceRegistry;

/**
 * The application's event loop with a physics phase. A repeating timer at
 * physics_hz calls advance(), which folds the time elapsed since the last
 * advance into whole fixed steps (at most max_steps; the rest is dropped) and
 * runs physicsProcess(step) on every attached Scene once per step, then the
 * after-step callbacks. turn() is untouched.
 *
 * Scene hooks run inside hooks(). A hook that waits on a promise outside a
 * fiber the loop owns would turn the loop inside a frame or a step, so until()
 * refuses it there.
 */
class GameLoop extends EventLoop
{
    public const string PHYSICS_TIMER = 'terran.physics';

    /** @var \SplObjectStorage<Scene, null> */
    protected \SplObjectStorage $scenes;

    protected readonly Closure $clock;

    protected int $born;

    protected ?int $last_advance = null;

    protected int $accumulated = 0;

    /** @var list<Closure(): void> */
    protected array $after_step = [];

    protected int $hook_depth = 0;

    protected bool $in_physics = false;

    /** @param (Closure(): int)|null $clock nanoseconds; hrtime by default */
    public function __construct(
        ResourceRegistry $registry,
        Waiter $waiter,
        PromiseEngine $promises,
        ?MailHandler $mail_handler = null,
        protected readonly float $physics_hz = 60.0,
        protected readonly int $max_steps = 8,
        ?Closure $clock = null,
    ) {
        if ($physics_hz <= 0) {
            throw new TerranException("physics_hz is a positive number of steps per second, got {$physics_hz}.");
        }
        if ($max_steps < 1) {
            throw new TerranException("max_physics_steps is at least 1, got {$max_steps}.");
        }

        parent::__construct($registry, $waiter, $promises, $mail_handler);
        $this->scenes = new \SplObjectStorage();
        $this->clock = $clock ?? static fn (): int => hrtime(true);
        $this->born = ($this->clock)();
    }

    public function physicsHz(): float { return $this->physics_hz; }

    public function step(): float { return 1 / $this->physics_hz; }

    public function stepNs(): int { return (int) round(1e9 / $this->physics_hz); }

    public function maxSteps(): int { return $this->max_steps; }

    public function attach(Scene $scene): void
    {
        if ($this->scenes->contains($scene)) {
            return;
        }

        $this->scenes->attach($scene);
        $scene->ready();
    }

    public function detach(Scene $scene): void
    {
        if (! $this->scenes->contains($scene)) {
            return;
        }

        $this->scenes->detach($scene);
        $scene->exit();
    }

    /** @return list<Scene> */
    public function attached(): array { return iterator_to_array($this->scenes, false); }

    /** Seconds since the loop was built. */
    public function now(): float { return (($this->clock)() - $this->born) / 1e9; }

    public function afterStep(Closure $callback): void
    {
        $this->after_step[] = $callback;
    }

    public function hooks(Closure $work): mixed
    {
        $this->hook_depth++;
        try {
            return $work();
        } finally {
            $this->hook_depth--;
        }
    }

    public function inHooks(): bool { return $this->hook_depth > 0; }

    public function inPhysics(): bool { return $this->in_physics; }

    /**
     * Fold the time since the last advance into whole steps and run them. The
     * first advance only sets the baseline.
     *
     * @return int the steps run
     */
    public function advance(?int $now_ns = null): int
    {
        $now = $now_ns ?? ($this->clock)();
        if (is_null($this->last_advance)) {
            $this->last_advance = $now;

            return 0;
        }

        $this->accumulated += max(0, $now - $this->last_advance);
        $this->last_advance = $now;

        $step_ns = $this->stepNs();
        $steps = intdiv($this->accumulated, $step_ns);
        if ($steps > $this->max_steps) {
            $steps = $this->max_steps;
            $this->accumulated %= $step_ns;
        } else {
            $this->accumulated -= $steps * $step_ns;
        }

        $step = $this->step();
        for ($i = 0; $i < $steps; $i++) {
            $this->hooks(function () use ($step): void {
                $this->in_physics = true;
                try {
                    foreach ($this->attached() as $scene) {
                        // a Scene detached earlier in this step has exited
                        if ($this->scenes->contains($scene)) {
                            $scene->physicsProcess($step);
                        }
                    }
                } finally {
                    $this->in_physics = false;
                }
            });
            foreach ($this->after_step as $callback) {
                $callback();
            }
        }

        return $steps;
    }

    public function run(): int
    {
        $this->last_advance = ($this->clock)();
        $this->accumulated = 0;
        $this->every($this->step(), fn () => $this->advance(), self::PHYSICS_TIMER);

        return parent::run();
    }

    public function until(Closure $assertion): void
    {
        if ($this->hook_depth > 0 && ! $assertion()) {
            $fiber = Fiber::getCurrent();
            if (is_null($fiber) || ! $this->fibers->owns($fiber)) {
                throw new TerranException('A Scene hook waited on a promise. A hook runs inside one frame or one physics step and cannot suspend it; start the work with $this->world()->async(...) and take the result in ->then().');
            }
        }

        parent::until($assertion);
    }
}
