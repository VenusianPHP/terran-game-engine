<?php
declare(strict_types=1);
namespace Terran\Console;

use Symfony\Component\Console\Input\InputOption;
use Terran\Scenes\ScenePrimitives;
use Voyager\Console\GeneratorCommand;
use Voyager\Filesystem\Filesystem;

/**
 * make:scene (a plain Scene) or make:scene:<slug> (a class extending that
 * registered primitive), written to app/Scenes. stubs/scene/<slug>.stub
 * (stubs/scene/scene.stub for make:scene) in the application overrides the stub.
 */
final class ScenePrimitiveMakeCommand extends GeneratorCommand
{
    protected ?string $type = 'Scene';

    public function __construct(Filesystem $files, private readonly ScenePrimitives $primitives, private readonly ?string $slug = null)
    {
        $this->name = is_null($slug) ? 'make:scene' : "make:scene:{$slug}";
        $this->description = is_null($slug)
            ? 'Create a new Scene class'
            : 'Create a new Scene class extending '.$primitives->get($slug)['class'];
        parent::__construct($files);
    }

    /** @return list<self> make:scene, then one per registered primitive */
    public static function all(Filesystem $files, ScenePrimitives $primitives): array
    {
        $commands = [new self($files, $primitives)];
        foreach (array_keys($primitives->all()) as $slug) {
            $commands[] = new self($files, $primitives, $slug);
        }

        return $commands;
    }

    protected function getStub(): string
    {
        return $this->override()
            ?? (is_null($this->slug) ? null : $this->primitives->get($this->slug)['stub'])
            ?? ScenePrimitives::genericStub();
    }

    protected function buildClass(string $name): string
    {
        $namespace = $this->getNamespace($name);

        return $this->primitives->render($this->slug, $namespace, substr($name, strlen($namespace) + 1), $this->override());
    }

    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return $rootNamespace.'\Scenes';
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the Scene already exists'],
        ];
    }

    private function override(): ?string
    {
        $path = $this->venusian->basePath('stubs/scene/'.($this->slug ?? 'scene').'.stub');

        return is_file($path) ? $path : null;
    }
}
