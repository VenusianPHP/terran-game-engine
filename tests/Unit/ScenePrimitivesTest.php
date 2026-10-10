<?php
declare(strict_types=1);

use Terran\Console\ScenePrimitiveMakeCommand;
use Terran\Scene;
use Terran\Scenes\ScenePrimitives;
use Terran\Scenes\TwoD\Sprite2D;
use Terran\Scenes\UI\Label;
use Terran\TerranException;
use Tests\Fixtures\Tetromino;
use Voyager\Filesystem\Filesystem;

/** Write generated source to a temp file, lint it with this PHP, load it, and return the class name. */
function loadGenerated(string $source, string $fqcn): string
{
    $path = sys_get_temp_dir().'/terran-gen-'.bin2hex(random_bytes(6)).'.php';
    file_put_contents($path, $source);
    $GLOBALS['terran_temp_files'][] = $path;
    exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($path).' 2>&1', $out, $status);
    if ($status !== 0) {
        throw new RuntimeException(implode("\n", $out));
    }
    require_once $path;

    return $fqcn;
}

it('registers Terran\'s eighteen primitives', function () {
    expect(array_keys(ScenePrimitives::terran()->all()))->toBe([
        'scene2d', 'sprite2d', 'animatedsprite2d', 'tilemap', 'camera2d', 'label2d', 'shape2d',
        'uicontrol', 'panel', 'label', 'button', 'progressbar', 'vbox', 'hbox',
        'scene3d', 'camera3d', 'meshinstance', 'directionallight',
    ]);
});

it('generates a class per primitive that parses and extends it', function () {
    $primitives = ScenePrimitives::terran();
    foreach ($primitives->all() as $slug => ['class' => $base]) {
        $class = loadGenerated($primitives->render($slug, "Generated\\P{$slug}", 'Thing'), "Generated\\P{$slug}\\Thing");

        expect(is_subclass_of($class, $base))->toBeTrue();
    }
});

it('calls the parent in every generated hook, and leaves draw() off 3D Scenes', function () {
    $sprite = ScenePrimitives::terran()->render('sprite2d', 'Generated\\Hooks', 'Ship');
    $mesh = ScenePrimitives::terran()->render('meshinstance', 'Generated\\Hooks', 'Rock');

    expect($sprite)->toContain('parent::ready();')
        ->toContain('parent::process($delta);')
        ->toContain('parent::physicsProcess($delta);')
        ->toContain('return parent::input($event);')
        ->toContain('parent::exit();')
        ->toContain('parent::draw($ge);')
        ->and($mesh)->not->toContain('function draw');
});

it('aliases the base when the new class shares its short name', function () {
    $source = ScenePrimitives::terran()->render('label', 'Generated\\Alias', 'Label');
    $class = loadGenerated($source, 'Generated\\Alias\\Label');

    expect($source)->toContain('use Terran\\Scenes\\UI\\Label as BaseLabel;')
        ->toContain('extends BaseLabel')
        ->and(is_subclass_of($class, Label::class))->toBeTrue();
});

it('generates a plain Scene for make:scene', function () {
    $class = loadGenerated(ScenePrimitives::terran()->render(null, 'Generated\\Plain', 'Level'), 'Generated\\Plain\\Level');

    expect(is_subclass_of($class, Scene::class))->toBeTrue();
});

it('uses a primitive\'s own stub, and an override stub before either', function () {
    $own = sys_get_temp_dir().'/terran-own-'.getmypid().'.stub';
    file_put_contents($own, "<?php\nnamespace {{ namespace }};\n{{ uses }}\n// own stub\nclass {{ class }} extends {{ base }} {}\n");
    $override = sys_get_temp_dir().'/terran-override-'.getmypid().'.stub';
    file_put_contents($override, "<?php\nnamespace {{ namespace }};\n{{ uses }}\n// override\nclass {{ class }} extends {{ base }} {}\n");
    array_push($GLOBALS['terran_temp_files'], $own, $override);
    $primitives = ScenePrimitives::terran()->register('tetromino', Tetromino::class, $own);

    expect($primitives->render('tetromino', 'Generated\\Own', 'T'))->toContain('// own stub')
        ->and($primitives->render('tetromino', 'Generated\\Own', 'T', $override))->toContain('// override');
});

it('refuses slugs and classes it cannot register, and lists slugs for an unknown one', function () {
    $primitives = ScenePrimitives::terran();

    expect(fn () => $primitives->register('Bad-Slug', Sprite2D::class))->toThrow(TerranException::class, "lower-case letters and digits, starting with a letter; got 'Bad-Slug'")
        ->and(fn () => $primitives->register('sprite2d', Sprite2D::class))->toThrow(TerranException::class, "Primitive 'sprite2d' is already registered")
        ->and(fn () => $primitives->register('thing', stdClass::class))->toThrow(TerranException::class, 'is not a Terran Scene class')
        ->and(fn () => $primitives->register('thing', Sprite2D::class, '/nowhere/thing.stub'))->toThrow(TerranException::class, "stub '/nowhere/thing.stub' does not exist")
        ->and(fn () => $primitives->get('nope'))->toThrow(TerranException::class, "No primitive 'nope'. Primitives: scene2d, ");
});

it('makes make:scene plus one make:scene:<slug> command per primitive, a package\'s included', function () {
    $primitives = ScenePrimitives::terran()->register('tetromino', Tetromino::class);
    $names = array_map(fn (ScenePrimitiveMakeCommand $c): string => $c->getName(), ScenePrimitiveMakeCommand::all(new Filesystem(), $primitives));

    expect($names)->toHaveCount(20)
        ->and($names[0])->toBe('make:scene')
        ->and($names)->toContain('make:scene:sprite2d')
        ->and($names)->toContain('make:scene:tetromino');
});
