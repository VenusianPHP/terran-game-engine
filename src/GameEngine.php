<?php
declare(strict_types=1);
namespace Terran;

use Surface\Contracts\Windows\Mail\QuitRequested;
use Surface\Contracts\Windows\Mail\WindowClosed;
use Surface\Drawing\DrawingManager;
use Terran\Input\InputReader;
use Terran\Targets\OwnedTarget;
use Terran\Targets\TargetOpener;
use Voyager\Config\Repository;
use Voyager\Contracts\Sketches\Attributes\Sketch as SketchName;
use Voyager\Contracts\Sketches\SketchLoopResult;
use Voyager\Sketches\Sketch;

/**
 * The sketch `php rocket game` runs. boot() opens the configured output targets,
 * gives each a Viewport on the engine Surface hands out for it, and roots the
 * main Scene; loop() is the render phase (process() down the tree, every
 * Viewport drawn and presented) and stops once an owned target is gone;
 * physics runs on the GameLoop's own timer.
 * An application that owns its window extends this and overrides openTargets()
 * and mainScene().
 */
#[SketchName('game')]
class GameEngine extends Sketch
{
    protected string $description = 'Runs the configured Terran game.';

    /** @var array<string, OwnedTarget> */
    protected array $owned = [];

    protected ?int $last_frame = null;

    protected bool $shut = false;

    public function __construct(
        protected readonly World $world,
        protected readonly TargetOpener $opener,
        protected readonly DrawingManager $drawing,
        protected readonly Repository $config,
        protected readonly ?InputReader $reader = null,
    ) {}

    public function refreshRate(): ?float
    {
        $hz = $this->config->get('terran.refresh_rate');

        return is_null($hz) ? parent::refreshRate() : (float) $hz;
    }

    public function boot(): void
    {
        $this->shut = false;
        $root = $this->mainScene();

        try {
            $opened = $this->openTargets();
            if ($opened === []) {
                throw new TerranException("No output targets. config('terran.targets') is empty and openTargets() returned none.");
            }

            foreach ($opened as $key => $owned) {
                $this->owned[$owned->name] = $owned;
                if ($owned->name !== $key) {
                    throw new TerranException("openTargets() returned the target '{$owned->name}' under the key '{$key}'; keys are target names.");
                }
                $engine = $owned->engine;
                $remint = fn ($target) => $this->drawing->renderer($engine, ['output' => $target]);
                $this->world->addViewport($key, $owned->target, $remint($owned->target), null, $remint, $owned->window);
            }

            $this->world->setRoot($root);
        } catch (\Throwable $e) {
            $this->shutdown();
            throw $e;
        }

        $this->last_frame = hrtime(true);
    }

    /** @param list<object> $mail */
    public function loop(array $mail = []): SketchLoopResult
    {
        foreach ($mail as $letter) {
            if ($letter instanceof QuitRequested) {
                return SketchLoopResult::STOP;
            }
            if ($letter instanceof WindowClosed && $this->ownsWindow($letter->window)) {
                return SketchLoopResult::STOP;
            }
        }

        // A window closed in this turn's pump is gone before its WindowClosed reaches the mail.
        foreach ($this->owned as $owned) {
            if (! $owned->alive()) {
                return SketchLoopResult::STOP;
            }
        }

        $now = hrtime(true);
        $delta = is_null($this->last_frame) ? 0.0 : ($now - $this->last_frame) / 1e9;
        $this->last_frame = $now;

        $events = is_null($this->reader) ? [] : $this->reader->read($mail, $this->world);
        $this->world->tick($events, $delta);
        $this->world->render();

        return SketchLoopResult::CONTINUE;
    }

    public function shutdown(): void
    {
        if ($this->shut) {
            return;
        }
        $this->shut = true;

        $this->world->destroy();
        foreach ($this->owned as $owned) {
            $owned->close();
        }
        $this->owned = [];
    }

    public function world(): World { return $this->world; }

    /** @return array<string, OwnedTarget> */
    public function targets(): array { return $this->owned; }

    /**
     * Every target this engine draws on, by name. The default reads
     * config('terran.targets'); override to open your own window and hand its
     * canvas back as a 'canvas' kind.
     *
     * @return array<string, OwnedTarget>
     */
    protected function openTargets(): array
    {
        $out = [];
        foreach ((array) $this->config->get('terran.targets', []) as $name => $entry) {
            $out[$name] = $this->opener->open((string) $name, is_array($entry) ? $entry : []);
        }

        return $out;
    }

    /** The root Scene. The default instantiates config('terran.main_scene') as 'main'. */
    protected function mainScene(): Scene
    {
        $class = $this->config->get('terran.main_scene') ?? throw new TerranException('No main scene. Set terran.main_scene to a Scene class, or override mainScene().');
        if (! is_string($class) || ! is_subclass_of($class, Scene::class)) {
            throw new TerranException('terran.main_scene is a Scene class; '.(is_string($class) ? $class : get_debug_type($class)).' is not.');
        }

        return new $class('main');
    }

    protected function ownsWindow(string $window): bool
    {
        foreach ($this->owned as $owned) {
            if ($owned->window === $window) {
                return true;
            }
        }

        return false;
    }
}
