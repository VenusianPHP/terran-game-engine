<?php
declare(strict_types=1);

use Terran\Math\Vector2;
use Terran\Scenes\TwoD\Scene2D;
use Terran\Scenes\UI\Anchor;
use Terran\Scenes\UI\HBox;
use Terran\Scenes\UI\Label;
use Terran\Scenes\UI\Panel;
use Terran\Scenes\UI\VBox;

function sized(string $name, float $w, float $h, bool $expand = false): Panel
{
    $panel = new Panel($name);
    $panel->minSize = new Vector2($w, $h);
    $panel->expand = $expand;

    return $panel;
}

function boxed(\Terran\Scenes\UI\UIControl $box): void
{
    [$world] = canvasWorld(32, 20);
    $root = new Scene2D('root');
    $root->add($box);
    $world->setRoot($root);
    $world->render();
}

it('stacks children down at their minimum heights, filling its width', function () {
    $box = (new VBox('v'))->setAnchor(Anchor::FULL_RECT);
    $box->separation = 2.0;
    [$a, $b, $c] = [sized('a', 3.0, 4.0), sized('b', 3.0, 4.0), sized('c', 3.0, 4.0)];
    $box->add($a)->add($b)->add($c);
    boxed($box);

    expect([$a->rect(), $b->rect(), $c->rect()])->toBe([[0.0, 0.0, 32.0, 4.0], [0.0, 6.0, 32.0, 4.0], [0.0, 12.0, 32.0, 4.0]]);
});

it('shares the space left among expanding children', function () {
    $box = (new VBox('v'))->setAnchor(Anchor::FULL_RECT);
    $box->separation = 2.0;
    [$a, $b, $c] = [sized('a', 0.0, 4.0), sized('b', 0.0, 4.0, true), sized('c', 0.0, 4.0)];
    $box->add($a)->add($b)->add($c);
    boxed($box);

    expect([$a->rect()[1], $b->rect()[1], $b->rect()[3], $c->rect()[1]])->toBe([0.0, 6.0, 8.0, 16.0]);
});

it('lays children across in an HBox, skipping hidden ones and ignoring their anchors', function () {
    $box = (new HBox('h'))->setMargin(0.0, 0.0, 20.0, 6.0);
    $box->separation = 1.0;
    $hidden = sized('hidden', 5.0, 1.0);
    $hidden->visible = false;
    $anchored = sized('anchored', 4.0, 1.0)->setAnchor(Anchor::BOTTOM_RIGHT)->setMargin(9.0, 9.0, 9.0, 9.0);
    $box->add(sized('a', 3.0, 1.0))->add($hidden)->add($anchored);
    boxed($box);

    expect($box->find('a')->rect())->toBe([0.0, 0.0, 3.0, 6.0])
        ->and($anchored->rect())->toBe([4.0, 0.0, 4.0, 6.0]);
});

it('asks for the sum of its children along its axis and the largest across, nested', function () {
    $outer = new VBox('outer');
    $outer->separation = 0.0;
    $row = new HBox('row');
    $row->separation = 2.0;
    $row->add(sized('a', 3.0, 5.0))->add(sized('b', 4.0, 2.0));
    $outer->add($row)->add(sized('c', 1.0, 1.0));
    boxed($outer);

    expect($row->rect())->toBe([0.0, 0.0, 9.0, 5.0])
        ->and($outer->rect())->toBe([0.0, 0.0, 9.0, 6.0]);
});

it('sizes a Label child to its text', function () {
    $box = (new VBox('v'))->setAnchor(Anchor::FULL_RECT);
    $label = new Label('l', 'HI');
    $box->add($label);
    boxed($box);

    expect($label->rect()[3])->toBeGreaterThan(0.0);
});
