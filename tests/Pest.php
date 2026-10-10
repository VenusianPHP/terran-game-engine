<?php
declare(strict_types=1);

/*
| Terran has no application in this suite. Tests build GameLoop, World,
| Viewport, TargetOpener and GameEngine from plain objects: a real
| EventLoop stack over StreamSelectWaiterBackend, Surface's DrawingManager
| over the native framebuffer and rasterize drivers, and FakeTarget as the
| OutputTarget. Nothing here opens a window or touches a panel.
*/

require_once __DIR__.'/Fixtures/Managers.php';   // first: FakeTarget mints through framebuffers()
require_once __DIR__.'/Fixtures/FakeTarget.php';
require_once __DIR__.'/Fixtures/RecordingScene.php';
require_once __DIR__.'/Fixtures/RecordingSession.php';
require_once __DIR__.'/Fixtures/FakeHumanInput.php';
require_once __DIR__.'/Fixtures/Block2D.php';
require_once __DIR__.'/Fixtures/RecordingControl.php';
require_once __DIR__.'/Fixtures/InputSpy.php';
require_once __DIR__.'/Fixtures/Tetromino.php';

use Surface\HumanInput\InputFrame;
use Tests\Fixtures\FakeTarget;
use Voyager\IOPools\LoopWaiter;
use Voyager\IOPools\PromiseEngines\GuzzlePromiseEngine;
use Voyager\IOPools\ResourceRegistry;
use Voyager\IOPools\Waiter\StreamSelectWaiterBackend;

function fakeTarget(int $width = 32, int $height = 16): FakeTarget
{
    return new FakeTarget($width, $height);
}

/** The three collaborators an EventLoop (and so a GameLoop) is built from. */
function testLoop(int $pace_ms = 1): array
{
    $registry = new ResourceRegistry();

    return [$registry, new LoopWaiter($registry, new StreamSelectWaiterBackend(), $pace_ms * 1_000_000), new GuzzlePromiseEngine()];
}

/** An InputFrame for hand-driven devices: 60 Hz frame length, clock from 0. */
function inputFrame(): InputFrame
{
    return new InputFrame(fn (): int => 16_666_667, 0);
}

use Surface\Contracts\Framebuffers\Framebuffer;
use Surface\Fonts\FontManager;
use Surface\Images\Extended\ExtendedImageDecoder;
use Surface\Images\Native\NativeImageDecoder;
use Terran\Assets\AssetCache;

/** A fresh AssetCache over Surface's decoder (ext-imgdec when loaded, as the images 'auto' driver chooses), native framebuffers and the built-in fonts. */
function assets(): AssetCache
{
    $driver = framebuffers()->driver('native');
    $decoder = function_exists('imgdec_png') ? new ExtendedImageDecoder($driver) : new NativeImageDecoder($driver);

    return new AssetCache($decoder, $driver, new FontManager());
}

/**
 * An RGBA8 PNG written to the temp dir in plain PHP (zlib + CRC32, no gd),
 * transparent except the listed pixels.
 *
 * @param array<string, array{int, int, int, int}> $pixels "x,y" → [r, g, b, a], each 0..255
 */
function pngFile(int $width, int $height, array $pixels = []): string
{
    $rows = '';
    for ($y = 0; $y < $height; $y++) {
        $rows .= "\0";
        for ($x = 0; $x < $width; $x++) {
            $rows .= pack('C4', ...($pixels["{$x},{$y}"] ?? [0, 0, 0, 0]));
        }
    }
    $chunk = fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    $png = "\x89PNG\r\n\x1a\n"
        .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0))
        .$chunk('IDAT', (string) gzcompress($rows))
        .$chunk('IEND', '');
    $path = sys_get_temp_dir().'/terran-'.bin2hex(random_bytes(6)).'.png';
    file_put_contents($path, $png);
    $GLOBALS['terran_temp_files'][] = $path;

    return $path;
}

/** A framebuffer of one colour, 0xRRGGBBAA. */
function solid(int $width, int $height, int $rgba): Framebuffer
{
    return assets()->blank($width, $height)->fill($rgba);
}

/** A pixel as "RRGGBBAA". */
function hexAt(Framebuffer $framebuffer, int $x, int $y): string
{
    return sprintf('%08X', $framebuffer->getPixel($x, $y));
}

uses()->afterEach(function () {
    foreach ($GLOBALS['terran_temp_files'] ?? [] as $path) {
        @unlink($path);
    }
    $GLOBALS['terran_temp_files'] = [];
})->in('Unit');

use Surface\NutsAndBolts\Color;
use Terran\GameLoop;
use Terran\World;

/**
 * A World with an AssetCache and one Velvet viewport 'main' over a fake
 * target, black background, re-minting on resize.
 *
 * @return array{World, FakeTarget}
 */
function canvasWorld(int $width = 16, int $height = 16): array
{
    [$registry, $waiter, $promises] = testLoop();
    $world = new World(new GameLoop($registry, $waiter, $promises), null, null, [], null, assets());
    $target = fakeTarget($width, $height);
    $remint = fn ($t) => drawing()->renderer('velvet', ['output' => $t]);
    $world->addViewport('main', $target, $remint($target), Color::hex('#000000'), $remint);

    return [$world, $target];
}

/** @return list<int> x of every non-black pixel */
function inkColumns(object $target): array
{
    [$w, $h] = $target->pixelSize();
    $xs = [];
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            if (($target->framebuffer()->getPixel($x, $y) >> 8) !== 0) {
                $xs[] = $x;
            }
        }
    }

    return $xs;
}
