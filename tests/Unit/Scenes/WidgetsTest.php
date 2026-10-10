<?php
declare(strict_types=1);

use Surface\Contracts\HumanInput\MouseButton;
use Surface\NutsAndBolts\Color;
use Terran\Input\Events\ActionPressed;
use Terran\Input\Events\MouseButtonPressed;
use Terran\Input\Events\MouseButtonReleased;
use Terran\Input\Events\MouseMoved;
use Terran\Scenes\Align;
use Terran\Scenes\TwoD\Scene2D;
use Terran\Scenes\UI\Button;
use Terran\Scenes\UI\Label;
use Terran\Scenes\UI\Panel;
use Terran\Scenes\UI\ProgressBar;
use Terran\Scenes\UI\UIControl;
use Terran\TerranException;

/** @return array{\Terran\World, object} a 32×16 World whose root holds $control, rendered once */
function widget(UIControl $control): array
{
    [$world, $target] = canvasWorld(32, 16);
    $root = new Scene2D('root');
    $root->add($control);
    $world->setRoot($root);
    $world->render();

    return [$world, $target];
}

function down(float $x, float $y): MouseButtonPressed { return new MouseButtonPressed(MouseButton::LEFT, 'main', $x, $y); }

function up(float $x, float $y): MouseButtonReleased { return new MouseButtonReleased(MouseButton::LEFT, 'main', $x, $y); }

it('fills a Panel and draws its border inside the rect', function () {
    $panel = (new Panel('p', Color::hex('#0000FF')))->setMargin(0.0, 0.0, 10.0, 10.0);
    $panel->borderColor = Color::hex('#FFFFFF');
    $panel->borderWidth = 2.0;
    [, $target] = widget($panel);

    expect(hexAt($target->framebuffer(), 0, 0))->toBe('FFFFFFFF')
        ->and(hexAt($target->framebuffer(), 1, 5))->toBe('FFFFFFFF')
        ->and(hexAt($target->framebuffer(), 5, 5))->toBe('0000FFFF')
        ->and(hexAt($target->framebuffer(), 10, 10))->toBe('000000FF');
});

it('places Label text inside its rect by align, and sizes to its text', function () {
    $left = (new Label('l', 'HI'))->setMargin(2.0, 2.0, 30.0, 14.0);
    $right = (new Label('r', 'HI'))->setMargin(2.0, 2.0, 30.0, 14.0);
    $right->align = Align::RIGHT;
    [, $l] = widget($left);
    [, $r] = widget($right);
    $fit = new Label('fit', 'HI');
    $fit->size = 2;
    widget($fit);
    $one = new Label('one', 'HI');
    widget($one);
    // the box textBounds() reports, which for a fixed-cell face includes a glyph's blank columns
    [, , $width] = drawing()->renderer('velvet', ['output' => fakeTarget()])->textBounds('HI', assets()->font(null));

    expect(min(inkColumns($l)))->toBe(2)
        ->and(min(inkColumns($r)) - min(inkColumns($l)))->toBe((int) (28 - $width))
        ->and(max(inkColumns($r)) - min(inkColumns($r)))->toBe(max(inkColumns($l)) - min(inkColumns($l)))
        ->and($fit->rect()[2])->toBeGreaterThan(0.0)
        ->and($fit->rect()[2])->toBe(2 * $one->rect()[2]);
});

it('emits pressed on a click inside, and not when the release lands outside', function () {
    $button = (new Button('b', 'OK'))->setMargin(0.0, 0.0, 12.0, 8.0);
    $count = 0;
    $button->connect('pressed', function () use (&$count) { $count++; });
    [$world] = widget($button);
    $world->tick([down(3.0, 3.0), up(3.0, 3.0)], 0.0);
    $after_click = $count;
    // 'OK' grows the button past its 12×8 margins to its text plus padding, so the drag ends in the far corner
    $outside = $button->hitTest(31.0, 15.0);
    $world->tick([down(3.0, 3.0), up(31.0, 15.0)], 0.0);
    $after_drag_off = [$count, $button->down()];
    $world->tick([down(3.0, 3.0), up(3.0, 3.0)], 0.0);

    expect($after_click)->toBe(1)
        ->and($outside)->toBeFalse()
        ->and($after_drag_off)->toBe([1, false])
        ->and($count)->toBe(2)
        ->and($button->hasFocus())->toBeTrue();
});

it('emits pressed on ui_accept while focused, and a disabled button ignores both', function () {
    $button = (new Button('b', 'OK'))->setMargin(0.0, 0.0, 12.0, 8.0);
    $count = 0;
    $button->connect('pressed', function () use (&$count) { $count++; });
    [$world] = widget($button);
    $world->tick([new ActionPressed('ui_accept')], 0.0);
    $unfocused = $count;
    $button->grabFocus();
    $world->tick([new ActionPressed('ui_accept')], 0.0);
    $focused = $count;
    $button->disabled = true;
    $world->tick([new ActionPressed('ui_accept'), down(3.0, 3.0), up(3.0, 3.0)], 0.0);

    expect([$unfocused, $focused, $count])->toBe([0, 1, 1]);
});

it('draws a button in its hover colour while the pointer is over it', function () {
    $button = (new Button('b'))->setMargin(0.0, 0.0, 12.0, 8.0);
    [$world, $target] = widget($button);
    $idle = hexAt($target->framebuffer(), 1, 6);
    $world->tick([new MouseMoved('main', 3.0, 3.0, 0.0, 0.0)], 0.0);
    $world->render();
    $hover = hexAt($target->framebuffer(), 1, 6);
    $world->tick([new MouseMoved('main', 25.0, 3.0, 0.0, 0.0)], 0.0);
    $world->render();

    expect($button->hovered())->toBeFalse()
        ->and($idle)->not->toBe($hover)
        ->and(hexAt($target->framebuffer(), 1, 6))->toBe($idle);
});

it('fills a ProgressBar by ratio, clamping its value', function () {
    $bar = (new ProgressBar('p'))->setMargin(0.0, 0.0, 20.0, 4.0);
    $bar->fillColor = Color::hex('#00FF00');
    $bar->backColor = Color::hex('#FF0000');
    $bar->value = 50.0;
    [, $target] = widget($bar);
    $bar->value = 150.0;

    expect(hexAt($target->framebuffer(), 2, 2))->toBe('00FF00FF')
        ->and(hexAt($target->framebuffer(), 15, 2))->toBe('FF0000FF')
        ->and($bar->value)->toBe(100.0)
        ->and($bar->ratio())->toBe(1.0);
});

it('refuses a ProgressBar whose max is not above its min', function () {
    $bar = new ProgressBar('p');
    $bar->max = 0.0;

    expect(fn () => $bar->ratio())->toThrow(TerranException::class, "ProgressBar 'p' needs max above min; got min 0, max 0.");
});

it('lets go of a button pressed under a PASS overlay and released outside', function () {
    [$world] = canvasWorld(64, 32);
    $root = new Scene2D('root');
    $button = (new Button('b', 'OK'))->setMargin(0.0, 0.0, 30.0, 16.0);
    $overlay = (new UIControl('overlay'))->setAnchor(\Terran\Scenes\UI\Anchor::FULL_RECT);
    $overlay->mouseFilter = \Terran\Scenes\UI\MouseFilter::PASS;
    $overlay->zIndex = 5;
    $pressed = 0;
    $button->connect('pressed', function () use (&$pressed) { $pressed++; });
    $root->add($button)->add($overlay);
    $world->setRoot($root);
    $world->render();
    $world->tick([down(3.0, 3.0), up(50.0, 20.0)], 0.0);
    $after = $button->down();
    $world->tick([down(50.0, 20.0), up(3.0, 3.0)], 0.0);

    expect($after)->toBeFalse()->and($pressed)->toBe(0);
});
