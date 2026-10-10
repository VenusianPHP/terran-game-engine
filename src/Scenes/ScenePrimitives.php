<?php
declare(strict_types=1);
namespace Terran\Scenes;

use Terran\Scene;
use Terran\Scenes\ThreeD\Camera3D;
use Terran\Scenes\ThreeD\DirectionalLight;
use Terran\Scenes\ThreeD\MeshInstance;
use Terran\Scenes\ThreeD\Scene3D;
use Terran\Scenes\TwoD\AnimatedSprite2D;
use Terran\Scenes\TwoD\Camera2D;
use Terran\Scenes\TwoD\Label2D;
use Terran\Scenes\TwoD\Scene2D;
use Terran\Scenes\TwoD\Shape2D;
use Terran\Scenes\TwoD\Sprite2D;
use Terran\Scenes\TwoD\TileMap;
use Terran\Scenes\UI\Button;
use Terran\Scenes\UI\HBox;
use Terran\Scenes\UI\Label;
use Terran\Scenes\UI\Panel;
use Terran\Scenes\UI\ProgressBar;
use Terran\Scenes\UI\UIControl;
use Terran\Scenes\UI\VBox;
use Terran\TerranException;

/**
 * The Scene primitives a make:scene:<slug> command can extend, by slug.
 * Bound as terran.primitives; a package adds its own with
 * callAfterResolving('terran.primitives', fn ($p) => $p->register(...)) in
 * its provider's register(), and its commands appear when the console starts.
 */
final class ScenePrimitives
{
    /** @var array<string, array{class: class-string<Scene>, stub: ?string}> */
    private array $entries = [];

    public static function terran(): self
    {
        $primitives = new self();
        foreach ([
            'scene2d' => Scene2D::class, 'sprite2d' => Sprite2D::class, 'animatedsprite2d' => AnimatedSprite2D::class,
            'tilemap' => TileMap::class, 'camera2d' => Camera2D::class, 'label2d' => Label2D::class, 'shape2d' => Shape2D::class,
            'uicontrol' => UIControl::class, 'panel' => Panel::class, 'label' => Label::class, 'button' => Button::class,
            'progressbar' => ProgressBar::class, 'vbox' => VBox::class, 'hbox' => HBox::class,
            'scene3d' => Scene3D::class, 'camera3d' => Camera3D::class, 'meshinstance' => MeshInstance::class,
            'directionallight' => DirectionalLight::class,
        ] as $slug => $class) {
            $primitives->register($slug, $class);
        }

        return $primitives;
    }

    public static function genericStub(): string
    {
        return dirname(__DIR__, 2).'/stubs/scene.primitive.stub';
    }

    /** @param class-string<Scene> $class */
    public function register(string $slug, string $class, ?string $stub = null): static
    {
        if (! preg_match('/^[a-z][a-z0-9]*$/', $slug)) {
            throw new TerranException("A primitive slug is lower-case letters and digits, starting with a letter; got '{$slug}'.");
        }
        if (isset($this->entries[$slug])) {
            throw new TerranException("Primitive '{$slug}' is already registered, to {$this->entries[$slug]['class']}.");
        }
        if (! class_exists($class) || ! is_a($class, Scene::class, true)) {
            throw new TerranException("Primitive '{$slug}' names {$class}, which is not a Terran Scene class.");
        }
        if (! is_null($stub) && ! is_file($stub)) {
            throw new TerranException("Primitive '{$slug}' stub '{$stub}' does not exist.");
        }
        $this->entries[$slug] = ['class' => $class, 'stub' => $stub];

        return $this;
    }

    /** @return array<string, array{class: class-string<Scene>, stub: ?string}> */
    public function all(): array { return $this->entries; }

    public function has(string $slug): bool { return isset($this->entries[$slug]); }

    /** @return array{class: class-string<Scene>, stub: ?string} */
    public function get(string $slug): array
    {
        return $this->entries[$slug] ?? throw new TerranException("No primitive '{$slug}'. Primitives: ".implode(', ', array_keys($this->entries)).'.');
    }

    /**
     * The source make:scene (null slug) or make:scene:<slug> writes: $class in
     * $namespace extending the primitive, from $stub, else the primitive's own
     * stub, else the generic one.
     */
    public function render(?string $slug, string $namespace, string $class, ?string $stub = null): string
    {
        $base = is_null($slug) ? Scene::class : $this->get($slug)['class'];
        $template = $stub ?? (is_null($slug) ? null : $this->get($slug)['stub']) ?? self::genericStub();
        $short = substr($base, (int) strrpos($base, '\\') + 1);
        $alias = $short === $class ? "Base{$short}" : $short;
        $draws = ! is_a($base, Scene3D::class, true);
        $uses = [$alias === $short ? "use {$base};" : "use {$base} as {$alias};", 'use Terran\\Input\\Events\\InputEvent;'];
        if ($draws) {
            $uses[] = 'use Surface\\Contracts\\Drawing\\RenderingEngine;';
        }
        sort($uses);

        return strtr((string) file_get_contents($template), [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $class,
            '{{ base }}' => $alias,
            '{{ uses }}' => implode("\n", $uses),
            '{{ hooks }}' => self::hooks($draws),
        ]);
    }

    private static function hooks(bool $draws): string
    {
        $hooks = [
            "    public function ready(): void\n    {\n        parent::ready();\n    }",
            "    public function process(float \$delta): void\n    {\n        parent::process(\$delta);\n    }",
            "    public function physicsProcess(float \$delta): void\n    {\n        parent::physicsProcess(\$delta);\n    }",
            "    public function input(InputEvent \$event): bool\n    {\n        return parent::input(\$event);\n    }",
            "    public function exit(): void\n    {\n        parent::exit();\n    }",
        ];
        if ($draws) {
            $hooks[] = "    public function draw(RenderingEngine \$ge): void\n    {\n        parent::draw(\$ge);\n    }";
        }

        return implode("\n\n", $hooks);
    }
}
