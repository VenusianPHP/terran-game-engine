<?php
declare(strict_types=1);

use Terran\Assets\SpriteFrames;
use Terran\Assets\SpriteSheet;
use Terran\Assets\Texture;
use Terran\Math\Vector2;
use Terran\Scenes\TwoD\AnimatedSprite2D;
use Terran\Scenes\TwoD\Scene2D;
use Terran\Scenes\TwoD\Sprite2D;
use Terran\TerranException;

function spriteAt(Sprite2D $sprite, float $x, float $y): array
{
    [$world, $target] = canvasWorld(8, 8);
    $sprite->position = new Vector2($x, $y);
    $root = new Scene2D('root');
    $root->add($sprite);
    $world->setRoot($root);
    $world->render();

    return [$world, $target];
}

it('draws a texture centred on its position, or from its top-left', function () {
    [, $centred] = spriteAt(new Sprite2D('s', Texture::of(solid(2, 2, 0xFF0000FF))), 4.0, 4.0);
    $corner = new Sprite2D('s', Texture::of(solid(2, 2, 0xFF0000FF)));
    $corner->centered = false;
    [, $cornered] = spriteAt($corner, 4.0, 4.0);

    expect([hexAt($centred->framebuffer(), 3, 3), hexAt($centred->framebuffer(), 4, 4), hexAt($centred->framebuffer(), 5, 5)])->toBe(['FF0000FF', 'FF0000FF', '000000FF'])
        ->and([hexAt($cornered->framebuffer(), 4, 4), hexAt($cornered->framebuffer(), 5, 5), hexAt($cornered->framebuffer(), 3, 3)])->toBe(['FF0000FF', 'FF0000FF', '000000FF']);
});

it('draws a sheet cell by frame', function () {
    $texture = Texture::of(assets()->blank(2, 1)->setPixel(0, 0, 0xFF0000FF)->setPixel(1, 0, 0x0000FFFF));
    $sprite = new Sprite2D('s', SpriteSheet::grid($texture, 2, 1, assets()));
    $sprite->frame = 1;
    $sprite->centered = false;
    [, $target] = spriteAt($sprite, 0.0, 0.0);

    expect(hexAt($target->framebuffer(), 0, 0))->toBe('0000FFFF');
});

it('flips in place', function () {
    $make = function (bool $flip): Sprite2D {
        $sprite = new Sprite2D('s', Texture::of(assets()->blank(2, 1)->setPixel(0, 0, 0xFF0000FF)->setPixel(1, 0, 0x0000FFFF)));
        $sprite->centered = false;
        $sprite->flipH = $flip;

        return $sprite;
    };
    [, $plain] = spriteAt($make(false), 4.0, 4.0);
    [, $flipped] = spriteAt($make(true), 4.0, 4.0);

    expect([hexAt($plain->framebuffer(), 4, 4), hexAt($plain->framebuffer(), 5, 4)])->toBe(['FF0000FF', '0000FFFF'])
        ->and([hexAt($flipped->framebuffer(), 4, 4), hexAt($flipped->framebuffer(), 5, 4)])->toBe(['0000FFFF', 'FF0000FF']);
});

it('draws at its effective modulate', function () {
    $sprite = new Sprite2D('s', Texture::of(solid(2, 2, 0xFF0000FF)));
    $sprite->modulate = 0.5;
    [, $target] = spriteAt($sprite, 4.0, 4.0);
    $red = ($target->framebuffer()->getPixel(4, 4) >> 24) & 0xFF;

    expect($red)->toBeGreaterThan(0x70)->toBeLessThan(0x90);
});

it('refuses a sprite given both a texture and a sheet', function () {
    $sprite = new Sprite2D('s', Texture::of(solid(2, 2, 0)));
    $sprite->sheet = SpriteSheet::grid(Texture::of(solid(2, 2, 0)), 1, 1, assets());

    expect(fn () => $sprite->image())->toThrow(TerranException::class, "Sprite2D 's' has both a texture and a sheet; set one.");
});

function frames(): SpriteFrames
{
    $sheet = SpriteSheet::grid(Texture::of(solid(4, 1, 0xFFFFFFFF)), 4, 1, assets());

    return (new SpriteFrames())->add('run', $sheet, [0, 1, 2], 4.0)->add('once', $sheet, [2, 3], 4.0, false);
}

it('advances by fps × speed × delta and wraps a looping animation', function () {
    $sprite = new AnimatedSprite2D('a', frames(), 'run');
    $sprite->play();
    $sprite->process(0.25);
    $after_one = $sprite->frame;
    $sprite->process(0.625);

    expect($after_one)->toBe(1)
        ->and($sprite->frame)->toBe(0)
        ->and($sprite->playing)->toBeTrue();
});

it('stops a one-shot animation on its last frame and says so once', function () {
    $sprite = new AnimatedSprite2D('a', frames());
    $finished = [];
    $sprite->connect('animationFinished', function (string $name) use (&$finished) { $finished[] = $name; });
    $sprite->play('once');
    $sprite->process(1.0);
    $sprite->process(1.0);

    expect($sprite->frame)->toBe(1)
        ->and($sprite->playing)->toBeFalse()
        ->and($finished)->toBe(['once'])
        ->and($sprite->image())->not->toBeNull();
});

it('restarts at frame 0 when another animation plays, and keeps its place when the same one does', function () {
    $sprite = new AnimatedSprite2D('a', frames(), 'run');
    $sprite->play();
    $sprite->process(0.25);
    $sprite->play('run');
    $kept = $sprite->frame;
    $sprite->play('once');

    expect($kept)->toBe(1)->and($sprite->frame)->toBe(0)->and($sprite->animation)->toBe('once');
});

it('says what is missing when it cannot play', function () {
    expect(fn () => (new AnimatedSprite2D('a', frames()))->play())->toThrow(TerranException::class, "AnimatedSprite2D 'a' has no animation to play; pass a name.")
        ->and(fn () => (new AnimatedSprite2D('a', frames()))->play('jump'))->toThrow(TerranException::class, "No animation 'jump'. Animations: run, once.")
        ->and(fn () => (new AnimatedSprite2D('a'))->play('run'))->toThrow(TerranException::class, "AnimatedSprite2D 'a' has no SpriteFrames.")
        ->and(function () {
            $sprite = new AnimatedSprite2D('a', frames(), 'run');
            $sprite->speed = -1.0;
            $sprite->play();
            $sprite->process(0.1);
        })->toThrow(TerranException::class, 'speed is 0 or more');
});

it('replays a finished one-shot animation from frame 0', function () {
    $sprite = new AnimatedSprite2D('a', frames());
    $finished = [];
    $sprite->connect('animationFinished', function (string $name) use (&$finished) { $finished[] = $name; });
    $sprite->play('once');
    $sprite->process(1.0);
    $sprite->process(1.0);
    $sprite->play('once');
    $replay = [$sprite->frame, $sprite->playing];
    $sprite->process(1.0);
    $sprite->process(1.0);

    expect($replay)->toBe([0, true])
        ->and($finished)->toBe(['once', 'once'])
        ->and($sprite->frame)->toBe(1);
});
