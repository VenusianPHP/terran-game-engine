<?php
declare(strict_types=1);

use Terran\Assets\SpriteFrames;
use Terran\Assets\SpriteSheet;
use Terran\Assets\Texture;
use Terran\Assets\TileSet;
use Terran\GameLoop;
use Terran\TerranException;
use Terran\World;

it('decodes a texture once per path and keeps its alpha', function () {
    $cache = assets();
    $path = pngFile(2, 1, ['0,0' => [255, 0, 0, 255]]);
    $texture = Texture::load($path, $cache);

    expect(Texture::load($path, $cache))->toBe($texture)
        ->and([$texture->width(), $texture->height()])->toBe([2, 1])
        ->and(hexAt($texture->framebuffer(), 0, 0))->toBe('FF0000FF')
        ->and($texture->framebuffer()->getPixel(1, 0) & 0xFF)->toBe(0);
});

it('names the path when a texture is missing or does not decode', function () {
    $junk = sys_get_temp_dir().'/terran-junk-'.getmypid().'.png';
    file_put_contents($junk, 'not an image');
    $GLOBALS['terran_temp_files'][] = $junk;

    expect(fn () => Texture::load('/nowhere/ship.png', assets()))->toThrow(TerranException::class, "'/nowhere/ship.png' does not exist")
        ->and(fn () => Texture::load($junk, assets()))->toThrow(TerranException::class, "did not decode");
});

it('cuts a grid into cells left to right, top to bottom, alpha intact', function () {
    $path = pngFile(4, 2, ['0,0' => [255, 0, 0, 255], '2,0' => [0, 0, 255, 255], '1,1' => [0, 255, 0, 128]]);
    $sheet = SpriteSheet::grid(Texture::load($path, $cache = assets()), 2, 1, $cache);

    expect($sheet->count())->toBe(2)
        ->and([$sheet->cell(0)->viewportWidth(), $sheet->cell(0)->viewportHeight()])->toBe([2, 2])
        ->and(hexAt($sheet->cell(0), 0, 0))->toBe('FF0000FF')
        ->and(hexAt($sheet->cell(1), 0, 0))->toBe('0000FFFF')
        ->and($sheet->cell(0)->getPixel(1, 1) & 0xFF)->toBeGreaterThanOrEqual(126)->toBeLessThanOrEqual(130)
        ->and($sheet->cell(1)->getPixel(1, 1) & 0xFF)->toBe(0);
});

it('cuts given regions', function () {
    $path = pngFile(4, 4, ['3,3' => [255, 255, 255, 255]]);
    $sheet = SpriteSheet::regions(Texture::load($path, $cache = assets()), [[2, 2, 2, 2], [0, 0, 1, 1]], $cache);

    expect($sheet->count())->toBe(2)
        ->and(hexAt($sheet->cell(0), 1, 1))->toBe('FFFFFFFF')
        ->and($sheet->cell(1)->viewportWidth())->toBe(1);
});

it('refuses grids, regions and cells that do not fit', function () {
    $cache = assets();
    $texture = Texture::of(solid(5, 4, 0xFF0000FF));

    expect(fn () => SpriteSheet::grid($texture, 2, 1, $cache))->toThrow(TerranException::class, 'is 5×4, which a 2×1 grid does not divide evenly')
        ->and(fn () => SpriteSheet::grid($texture, 0, 1, $cache))->toThrow(TerranException::class, 'at least 1')
        ->and(fn () => SpriteSheet::regions($texture, [[4, 0, 2, 2]], $cache))->toThrow(TerranException::class, 'Region [4, 0, 2, 2] lies outside the 5×4 texture')
        ->and(fn () => SpriteSheet::grid(Texture::of(solid(4, 4, 0)), 2, 2, $cache)->cell(4))->toThrow(TerranException::class, 'Cell 4 is outside this sheet\'s cells 0 to 3');
});

it('keeps named animations and lists them for an unknown name', function () {
    $sheet = SpriteSheet::grid(Texture::of(solid(4, 1, 0xFFFFFFFF)), 4, 1, assets());
    $frames = (new SpriteFrames())->add('run', $sheet, [0, 1, 2], 12.0)->add('idle', $sheet, [3], 1.0, false);

    expect($frames->names())->toBe(['run', 'idle'])
        ->and($frames->animation('run')->cells)->toBe([0, 1, 2])
        ->and($frames->animation('idle')->loop)->toBeFalse()
        ->and(fn () => $frames->animation('jump'))->toThrow(TerranException::class, "No animation 'jump'. Animations: run, idle.")
        ->and(fn () => $frames->add('bad', $sheet, [9]))->toThrow(TerranException::class, 'cell 9')
        ->and(fn () => $frames->add('none', $sheet, []))->toThrow(TerranException::class, 'at least one cell')
        ->and(fn () => $frames->add('slow', $sheet, [0], 0.0))->toThrow(TerranException::class, 'fps');
});

it('maps tile ids to sheet cells', function () {
    $sheet = SpriteSheet::grid(Texture::of(solid(4, 2, 0x00FF00FF)), 2, 1, assets());
    $set = new TileSet($sheet, 2, 2);

    expect($set->count())->toBe(2)
        ->and($set->tile(1))->toBe($sheet->cell(1))
        ->and(fn () => new TileSet($sheet, 0, 2))->toThrow(TerranException::class, 'tile size');
});

it('resolves font faces and lists slugs for an unknown one', function () {
    $cache = assets();

    expect($cache->font(null))->toBe($cache->font('classic'))
        ->and(fn () => $cache->font('nope'))->toThrow(TerranException::class, "No font face 'nope'. Faces: ");
});

it('hands out the World\'s cache, and says how to get one when it has none', function () {
    [$registry, $waiter, $promises] = testLoop();
    $cache = assets();

    expect((new World(new GameLoop($registry, $waiter, $promises), null, null, [], null, $cache))->assets())->toBe($cache)
        ->and(fn () => (new World(new GameLoop(...testLoop())))->assets())->toThrow(TerranException::class, 'sixth argument');
});
