<?php
declare(strict_types=1);

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\NutsAndBolts\Affine;
use Surface\NutsAndBolts\Color;
use Terran\Assets\SpriteSheet;
use Terran\Assets\Texture;
use Terran\Assets\TileSet;
use Terran\Math\Vector2;
use Terran\Scenes\Align;
use Terran\Scenes\TwoD\Label2D;
use Terran\Scenes\TwoD\Scene2D;
use Terran\Scenes\TwoD\Shape2D;
use Terran\Scenes\TwoD\ShapeKind;
use Terran\Scenes\TwoD\TileMap;
use Terran\TerranException;

function tiles(): TileSet
{
    $texture = Texture::of(assets()->blank(4, 2)->fill(0xFF0000FF)->setSegment(2, 0, 2, 2, 0x0000FFFF));

    return new TileSet(SpriteSheet::grid($texture, 2, 1, assets()), 2, 2);
}

function drawn2d(Scene2D $scene, int $w = 16, int $h = 16): object
{
    [$world, $target] = canvasWorld($w, $h);
    $root = new Scene2D('root');
    $root->add($scene);
    $world->setRoot($root);
    $world->render();

    return $target;
}

it('draws set cells at col × tile width, row × tile height', function () {
    $map = new TileMap('map', tiles());
    $map->setCell(0, 0, 0)->setCell(1, 0, 1)->setCell(2, 1, 1);
    $target = drawn2d($map, 8, 8);

    expect(hexAt($target->framebuffer(), 1, 1))->toBe('FF0000FF')
        ->and(hexAt($target->framebuffer(), 2, 0))->toBe('0000FFFF')
        ->and(hexAt($target->framebuffer(), 5, 3))->toBe('0000FFFF')
        ->and(hexAt($target->framebuffer(), 1, 3))->toBe('000000FF');
});

it('reads, finds and clears cells', function () {
    $map = new TileMap('map', tiles());
    $map->setCell(-1, 2, 0)->setCell(3, 0, 1)->setCell(3, 0, null);

    expect($map->cell(-1, 2))->toBe(0)
        ->and($map->cell(3, 0))->toBeNull()
        ->and($map->cellAt(new Vector2(-0.5, 5.0)))->toBe([-1, 2])
        ->and($map->usedRect())->toBe([-1, 2, 1, 1])
        ->and($map->clear()->usedRect())->toBe([0, 0, 0, 0])
        ->and(fn () => $map->setCell(0, 0, 2))->toThrow(TerranException::class, 'tile 2')
        ->and(fn () => (new TileMap('bare'))->cellAt(Vector2::zero()))->toThrow(TerranException::class, "TileMap 'bare' has no TileSet");
});

it('draws only the cells inside the visible area', function () {
    $map = new TileMap('map', tiles());
    $map->setCell(0, 0, 0)->setCell(1, 1, 1)->setCell(100, 100, 0);
    $ge = Mockery::spy(RenderingEngine::class);
    $ge->allows('matrix')->andReturn(Affine::identity());
    $ge->allows('width')->andReturn(8);
    $ge->allows('height')->andReturn(8);
    $map->draw($ge);

    $ge->shouldHaveReceived('image')->twice();
});

it('fills and strokes shapes centred on the origin', function () {
    $rect = Shape2D::rect('r', 4.0, 4.0);
    $rect->fill = Color::hex('#FF0000');
    $rect->position = new Vector2(8.0, 8.0);
    $circle = Shape2D::circle('c', 3.0);
    $circle->fill = Color::hex('#00FF00');
    $circle->position = new Vector2(8.0, 8.0);
    $outline = Shape2D::rect('o', 6.0, 6.0);
    $outline->stroke = Color::hex('#FFFFFF');
    $outline->strokeWidth = 2.0;
    $outline->position = new Vector2(8.0, 8.0);
    $triangle = Shape2D::polygon('t', [new Vector2(0.0, 0.0), new Vector2(4.0, 0.0), new Vector2(0.0, 4.0)]);
    $triangle->fill = Color::hex('#0000FF');
    $triangle->position = new Vector2(2.0, 2.0);

    $r = drawn2d($rect);
    $c = drawn2d($circle);
    $o = drawn2d($outline);
    $t = drawn2d($triangle);

    expect([hexAt($r->framebuffer(), 6, 6), hexAt($r->framebuffer(), 9, 9), hexAt($r->framebuffer(), 5, 5)])->toBe(['FF0000FF', 'FF0000FF', '000000FF'])
        ->and([hexAt($c->framebuffer(), 8, 8), hexAt($c->framebuffer(), 8, 3)])->toBe(['00FF00FF', '000000FF'])
        ->and([hexAt($o->framebuffer(), 5, 8), hexAt($o->framebuffer(), 8, 8)])->toBe(['FFFFFFFF', '000000FF'])
        ->and([hexAt($t->framebuffer(), 3, 3), hexAt($t->framebuffer(), 5, 5)])->toBe(['0000FFFF', '000000FF'])
        ->and($rect->kind())->toBe(ShapeKind::Rect);
});

it('draws a line in its stroke colour', function () {
    $line = Shape2D::line('l', new Vector2(0.0, 0.0), new Vector2(8.0, 0.0));
    $line->stroke = Color::hex('#FFFFFF');
    $line->strokeWidth = 2.0;
    $line->position = new Vector2(0.0, 4.0);
    $target = drawn2d($line);

    expect(hexAt($target->framebuffer(), 4, 3) === 'FFFFFFFF' || hexAt($target->framebuffer(), 4, 4) === 'FFFFFFFF')->toBeTrue();
});

it('refuses shapes it cannot draw', function () {
    expect(fn () => Shape2D::polygon('p', [Vector2::zero(), Vector2::one()]))->toThrow(TerranException::class, 'at least 3 points')
        ->and(fn () => Shape2D::rect('r', 0.0, 1.0))->toThrow(TerranException::class, 'positive')
        ->and(fn () => Shape2D::circle('c', -1.0))->toThrow(TerranException::class, 'positive');
});

it('aligns text about its origin, scaled by size', function () {
    $left = new Label2D('l', 'HI');
    $left->size = 2;
    $left->position = new Vector2(2.0, 2.0);
    $right = new Label2D('r', 'HI');
    $right->size = 2;
    $right->align = Align::RIGHT;
    $right->position = new Vector2(30.0, 2.0);
    $centre = new Label2D('c', 'HI');
    $centre->size = 2;
    $centre->align = Align::CENTER;
    $centre->position = new Vector2(16.0, 2.0);

    $l = inkColumns(drawn2d($left, 32, 24));
    $r = inkColumns(drawn2d($right, 32, 24));
    $c = inkColumns(drawn2d($centre, 32, 24));
    // the box textBounds() reports, which for a fixed-cell face includes a glyph's blank columns
    [, , $width] = drawing()->renderer('velvet', ['output' => fakeTarget()])->textBounds('HI', assets()->font(null));

    expect(min($l))->toBe(2)
        ->and(min($r) - min($l))->toBe((int) (28 - 2 * $width))
        ->and(min($c) - min($l))->toBe((int) (14 - $width))
        ->and(min($c) < 16 && max($c) >= 16)->toBeTrue()
        ->and(max($l) - min($l))->toBe(max($r) - min($r));
});

it('names the faces when a label asks for one that does not exist, and refuses a size under 1', function () {
    $label = new Label2D('l', 'x');
    $label->font = 'nope';
    [$world] = canvasWorld();
    $root = new Scene2D('root');
    $root->add($label);
    $world->setRoot($root);
    $small = new Label2D('s', 'x');
    $small->size = 0;

    expect(fn () => $world->render())->toThrow(TerranException::class, "No font face 'nope'. Faces: ")
        ->and(fn () => drawn2d($small))->toThrow(TerranException::class, 'size is 1 or more');
});
