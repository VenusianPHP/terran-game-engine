<?php
declare(strict_types=1);
namespace Terran;

use Surface\Drawing\DrawingManager;
use Terran\Autoload\AutoloadRegistry;
use Terran\Input\InputMap;
use Terran\Input\InputReader;
use Terran\Input\Seats;
use Terran\Targets\TargetOpener;
use Voyager\Contracts\Core\FrameworkCore;
use Voyager\IOPools\LoopWaiter;
use Voyager\IOPools\ResourceRegistry;
use Voyager\NutsAndBolts\ServiceProvider;
use Voyager\Sketches\SketchRegistry;

/**
 * Binds the engine's pieces and makes the GameLoop the application's event
 * loop. IOPools binds 'event-loop' in its boot(); package providers boot after
 * the framework's, so this provider's boot() re-registers the same name with a
 * GameLoop built from the same collaborators. If something resolved the loop
 * before then, swapping it would split the application across two loops, so
 * the provider refuses instead.
 */
class TerranServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/terran.php', 'terran');

        $this->app->registerSingleton('terran.targets', fn (FrameworkCore $app) => new TargetOpener($app, config('terran.stager')));
        $this->app->alias('terran.targets', TargetOpener::class);

        $this->app->registerSingleton('terran.seats', fn () => new Seats(
            (int) config('terran.input.seats', 4),
            config('terran.input.keyboard_seat', 1),
            (bool) config('terran.input.seat_on_connect', true),
        ));
        $this->app->alias('terran.seats', Seats::class);

        $this->app->registerSingleton('terran.actions', fn (FrameworkCore $app) => new InputMap((array) config('terran.input.actions', []), $app->make(Seats::class)));
        $this->app->alias('terran.actions', InputMap::class);

        $this->app->registerSingleton('terran.input', fn (FrameworkCore $app) => new InputReader(
            $app->make('human-input'),
            $app->make(InputMap::class),
            (array) config('terran.input.events', ['keyboard', 'mouse']),
            $app->make(Seats::class),
        ));
        $this->app->alias('terran.input', InputReader::class);

        $this->app->registerSingleton('terran.world', function (FrameworkCore $app): World {
            $loop = $app->get('event-loop');
            if (! $loop instanceof GameLoop) {
                throw new TerranException("Terran's World needs the GameLoop as 'event-loop'; found ".get_debug_type($loop).'. Is TerranServiceProvider registered?');
            }

            return new World(
                $loop,
                $app->make('signals'),
                new AutoloadRegistry($app),
                (array) config('terran.autoloads', []),
                $app->make(InputMap::class),
            );
        });
        $this->app->alias('terran.world', World::class);

        $this->app->bind(GameEngine::class, fn (FrameworkCore $app) => new GameEngine(
            $app->make(World::class),
            $app->make(TargetOpener::class),
            $app->make('drawing'),
            $app->make('config'),
            $app->make(InputReader::class),
        ));
    }

    public function boot(): void
    {
        $this->publishes([dirname(__DIR__).'/config/terran.php' => $this->app->configPath('terran.php')], 'terran-config');

        $this->bindGameLoop();

        $this->callAfterResolving('sketches.registry', function (SketchRegistry $registry): void {
            if (! $registry->has(SketchRegistry::nameFor(GameEngine::class))) {
                $registry->register(GameEngine::class);
            }
        });
    }

    protected function bindGameLoop(): void
    {
        if ($this->app->isResolved('event-loop')) {
            throw new TerranException("'event-loop' was resolved before Terran booted, so it cannot become the GameLoop. Register TerranServiceProvider before anything that uses the loop at boot.");
        }

        $this->app->registerSingleton('event-loop', function (FrameworkCore $app): GameLoop {
            /** @var ResourceRegistry $registry */
            $registry = $app->get('loop-resource-registry');
            $waiter = new LoopWaiter(
                $registry,
                $app->get('waiter-backend-mgr')->driver(),
                (int) config('io-pools.event_loop.pace_ms', 16) * 1_000_000,
            );

            return new GameLoop(
                $registry,
                $waiter,
                $app->get('promise-engine-mgr')->driver(),
                $app->get('mail-handler-mgr')->driver(),
                (float) config('terran.physics_hz', 60),
                (int) config('terran.max_physics_steps', 8),
            );
        });
    }
}
