<?php

namespace ScrapyardIO\GameEngine;

use Fabricate\Contracts\Sketches\SketchRegistry;
use Fabricate\NutsAndBolts\AggregateServiceProvider;
use ScrapyardIO\GameEngine\Resources\ResourceStore;
use ScrapyardIO\GameEngine\Runner\Sketches\GameEngineSpriteDemo;

class GameEngineServiceProvider extends AggregateServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/config/game-engine.php',
            'game-engine',
        );

        $this->container->singleton(ResourceStore::class, function ($app) {
            $path = $app['config']->get('game-engine.resource_store.database');

            if (is_null($path) || $path === '') {
                $path = $app->storagePath('game-engine/resources.sqlite');
            }

            return new ResourceStore($path);
        });

        parent::register();
    }

    public function boot(): void
    {
        if ($this->container->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__).'/config/game-engine.php' => $this->container->configPath('game-engine.php'),
            ], 'game-engine-config');
        }

        $this->registerSketches();
    }

    protected function registerSketches(): void
    {
        $registry = $this->container->make(SketchRegistry::class);

        if (! $registry->has('game-engine-sprite-demo')) {
            $registry->registerConvention('game-engine-sprite-demo', GameEngineSpriteDemo::class);
        }
    }
}
