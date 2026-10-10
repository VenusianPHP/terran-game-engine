<?php
declare(strict_types=1);

use Surface\Contracts\HumanInput\Key;
use Surface\Contracts\HumanInput\Modifiers;
use Surface\Contracts\HumanInput\MouseButton;
use Surface\NutsAndBolts\Color;
use Terran\Input\Events\ActionPressed;
use Terran\Input\Events\KeyPressed;
use Terran\Input\Events\MouseButtonPressed;
use Terran\Input\Events\MouseButtonReleased;
use Terran\Input\Events\MouseMoved;
use Terran\Input\Events\MouseWheel;
use Terran\Math\Vector2;
use Terran\Scenes\TwoD\Camera2D;
use Terran\Scenes\TwoD\Scene2D;
use Terran\Scenes\UI\Anchor;
use Terran\Scenes\UI\MouseFilter;
use Terran\Scenes\UI\UIControl;
use Terran\TerranException;
use Tests\Fixtures\InputSpy;
use Tests\Fixtures\RecordingControl;

/** A live World over a 32×16 'main' viewport, a root holding an InputSpy and the given controls, rendered once so layout exists. */
function uiWorld(UIControl ...$controls): array
{
    [$world, $target] = canvasWorld(32, 16);
    $root = new Scene2D('root');
    $spy = new InputSpy('spy');
    $root->add($spy);
    foreach ($controls as $control) {
        $root->add($control);
    }
    $world->setRoot($root);
    $world->render();

    return [$world, $target, $spy, $root];
}

function box(string $name, float $x, float $y, float $w, float $h, string $hex = '#FFFFFF'): RecordingControl
{
    return (new RecordingControl($name, $hex))->setMargin($x, $y, $x + $w, $y + $h);
}

function press(float $x, float $y): MouseButtonPressed { return new MouseButtonPressed(MouseButton::LEFT, 'main', $x, $y); }

function release(float $x, float $y): MouseButtonReleased { return new MouseButtonReleased(MouseButton::LEFT, 'main', $x, $y); }

it('lays out from anchors and margins in viewport pixels', function () {
    $full = (new RecordingControl('full'))->setAnchor(Anchor::FULL_RECT)->setMargin(2.0, 2.0, -2.0, -2.0);
    $centre = (new RecordingControl('centre'))->setAnchor(Anchor::CENTER)->setMargin(-4.0, -3.0, 4.0, 3.0);
    [, $target] = uiWorld($full, $centre);

    expect($full->rect())->toBe([2.0, 2.0, 28.0, 12.0])
        ->and($centre->rect())->toBe([12.0, 5.0, 8.0, 6.0])
        ->and(hexAt($target->framebuffer(), 2, 2))->toBe('FFFFFFFF')
        ->and(hexAt($target->framebuffer(), 1, 1))->toBe('000000FF')
        ->and(hexAt($target->framebuffer(), 29, 13))->toBe('FFFFFFFF')
        ->and(hexAt($target->framebuffer(), 30, 14))->toBe('000000FF');
});

it('lays a child out inside its nearest UIControl ancestor, floored by minSize', function () {
    $parent = box('parent', 4.0, 4.0, 8.0, 8.0);
    $group = new Scene2D('group');
    $child = (new RecordingControl('child'))->setAnchor(Anchor::FULL_RECT)->setMargin(1.0, 1.0, -1.0, -1.0);
    $group->add($child);
    $parent->add($group);
    $floored = new RecordingControl('floored');
    $floored->minSize = new Vector2(5.0, 3.0);
    uiWorld($parent, $floored);

    expect($child->rect())->toBe([5.0, 5.0, 6.0, 6.0])
        ->and($floored->rect())->toBe([0.0, 0.0, 5.0, 3.0]);
});

it('lays out again at the new size when the window resizes', function () {
    $full = (new RecordingControl('full'))->setAnchor(Anchor::FULL_RECT);
    [$world, $target] = uiWorld($full);
    $target->resize(20, 10);
    $world->render();

    expect($full->rect())->toBe([0.0, 0.0, 20.0, 10.0]);
});

it('lays out and hit-tests per viewport', function () {
    $full = (new RecordingControl('full'))->setAnchor(Anchor::FULL_RECT);
    [$world] = uiWorld($full);
    $hud = fakeTarget(8, 8);
    $world->addViewport('hud', $hud, drawing()->renderer('velvet', ['output' => $hud]), Color::hex('#000000'));
    $world->render();

    expect($full->rect('main'))->toBe([0.0, 0.0, 32.0, 16.0])
        ->and($full->rect('hud'))->toBe([0.0, 0.0, 8.0, 8.0])
        ->and($full->hitTest(20.0, 4.0, 'main'))->toBeTrue()
        ->and($full->hitTest(20.0, 4.0, 'hud'))->toBeFalse();
});

it('draws UI outside the camera and above the 2D pass, by z', function () {
    [$world, $target] = canvasWorld(16, 16);
    $root = new Scene2D('root');
    $camera = (new Camera2D('cam'))->makeCurrent();
    $camera->zoom = new Vector2(2.0, 2.0);
    $low = box('low', 0.0, 0.0, 2.0, 2.0, '#FF0000');
    $low->zIndex = 1;
    $high = box('high', 0.0, 0.0, 2.0, 2.0, '#00FF00');
    $high->zIndex = 2;
    $root->add($camera)->add($high)->add($low);
    $world->setRoot($root);
    $world->render();

    expect(hexAt($target->framebuffer(), 1, 1))->toBe('00FF00FF')
        ->and(hexAt($target->framebuffer(), 2, 2))->toBe('000000FF');
});

it('gives a click to the control under it before the tree, and STOP ends the walk', function () {
    $stop = box('stop', 0.0, 0.0, 8.0, 8.0);
    [$world, , $spy] = uiWorld($stop);
    $world->tick([press(3.0, 3.0)], 0.0);

    expect($stop->got)->toBe([MouseButtonPressed::class])->and($spy->got)->toBe([]);
});

it('lets the walk go on past PASS, skips IGNORE, and gives clicks outside every control to the tree', function () {
    $pass = box('pass', 0.0, 0.0, 8.0, 8.0);
    $pass->mouseFilter = MouseFilter::PASS;
    $ignore = box('ignore', 10.0, 0.0, 8.0, 8.0);
    $ignore->mouseFilter = MouseFilter::IGNORE;
    [$world, , $spy] = uiWorld($pass, $ignore);
    $world->tick([press(3.0, 3.0), press(12.0, 3.0), press(30.0, 14.0)], 0.0);

    expect($pass->got)->toBe([MouseButtonPressed::class])
        ->and($ignore->got)->toBe([])
        ->and($spy->got)->toBe([MouseButtonPressed::class, MouseButtonPressed::class, MouseButtonPressed::class]);
});

it('visits the topmost control first, and a handled event stops a PASS control\'s walk', function () {
    $under = box('under', 0.0, 0.0, 8.0, 8.0);
    $over = box('over', 0.0, 0.0, 8.0, 8.0);
    $over->zIndex = 1;
    $over->mouseFilter = MouseFilter::PASS;
    $over->handles = true;
    [$world, , $spy] = uiWorld($under, $over);
    $world->tick([press(3.0, 3.0)], 0.0);

    expect($over->got)->toBe([MouseButtonPressed::class])->and($under->got)->toBe([])->and($spy->got)->toBe([]);
});

it('gives the release to the control that took the press, wherever it lands', function () {
    $button = box('button', 0.0, 0.0, 8.0, 8.0);
    [$world, , $spy] = uiWorld($button);
    $world->tick([press(3.0, 3.0), release(20.0, 12.0), press(3.0, 3.0), release(3.0, 3.0)], 0.0);

    expect($button->got)->toBe([MouseButtonPressed::class, MouseButtonReleased::class, MouseButtonPressed::class, MouseButtonReleased::class])
        ->and($spy->got)->toBe([]);
});

it('tells controls when the pointer enters and leaves, and wheels at the last pointer position', function () {
    $c = box('c', 0.0, 0.0, 8.0, 8.0);
    [$world, , $spy] = uiWorld($c);
    $world->tick([new MouseMoved('main', 3.0, 3.0, 1.0, 0.0), new MouseWheel('main', 0.0, 1.0), new MouseMoved('main', 20.0, 3.0, 17.0, 0.0)], 0.0);

    expect([$c->entered, $c->exited])->toBe([1, 1])
        ->and($c->got)->toBe([MouseMoved::class, MouseWheel::class])
        ->and($spy->got)->toBe([MouseMoved::class]);
});

it('gives keys to the focused control first, and never to an unfocused one', function () {
    $focused = box('focused', 0.0, 0.0, 4.0, 4.0);
    $focused->focusable = true;
    $other = box('other', 8.0, 0.0, 4.0, 4.0);
    [$world, , $spy] = uiWorld($focused, $other);
    $focused->grabFocus();
    $world->tick([new KeyPressed(Key::A, new Modifiers())], 0.0);

    expect($focused->hasFocus())->toBeTrue()
        ->and($focused->got)->toBe([KeyPressed::class])
        ->and($other->got)->toBe([])
        ->and($spy->got)->toBe([KeyPressed::class]);
});

it('moves focus through visible focusable controls with ui_next and ui_prev, wrapping', function () {
    [$a, $b, $c] = [box('a', 0.0, 0.0, 2.0, 2.0), box('b', 4.0, 0.0, 2.0, 2.0), box('c', 8.0, 0.0, 2.0, 2.0)];
    foreach ([$a, $b, $c] as $control) {
        $control->focusable = true;
    }
    $c->visible = false;
    [$world] = uiWorld($a, $b, $c);
    $seen = [];
    foreach (['ui_next', 'ui_next', 'ui_next', 'ui_prev'] as $action) {
        $world->tick([new ActionPressed($action)], 0.0);
        $seen[] = $world->focused()?->name;
    }

    expect($seen)->toBe(['a', 'b', 'a', 'b']);
});

it('refuses focus it cannot give', function () {
    $plain = box('plain', 0.0, 0.0, 2.0, 2.0);
    [$world] = uiWorld($plain);
    $loose = new RecordingControl('loose');
    $loose->focusable = true;

    expect(fn () => $plain->grabFocus())->toThrow(TerranException::class, "'plain' is not focusable; set focusable = true.")
        ->and(fn () => $loose->grabFocus())->toThrow(TerranException::class, "'loose' is not in a live tree; focus is taken once it is.");
});

it('forgets a removed control that was focused, hovered and holding a press', function () {
    $gone = box('gone', 0.0, 0.0, 8.0, 8.0);
    $gone->focusable = true;
    [$world, , $spy, $root] = uiWorld($gone);
    $gone->grabFocus();
    $world->tick([new MouseMoved('main', 3.0, 3.0, 0.0, 0.0), press(3.0, 3.0)], 0.0);
    $before = $gone->got;
    $root->remove($gone);
    $world->tick([release(3.0, 3.0), new KeyPressed(Key::A, new Modifiers()), new MouseMoved('main', 20.0, 3.0, 0.0, 0.0)], 0.0);

    expect($world->focused())->toBeNull()
        ->and($gone->got)->toBe($before)
        ->and($gone->exited)->toBe(0)
        ->and($spy->got)->toBe([MouseButtonReleased::class, KeyPressed::class, MouseMoved::class]);
});

it('hides every kind of Scene under a hidden Scene2D or control, from drawing and from clicks', function () {
    [$world, $target] = canvasWorld(16, 16);
    $root = new Scene2D('root');
    $hidden = new Scene2D('hidden');
    $hidden->visible = false;
    $plain = new class('plain') extends \Terran\Scene {
        public function draw(\Surface\Contracts\Drawing\RenderingEngine $ge): void { $ge->fillRect(0.0, 0.0, 2.0, 2.0, Color::hex('#00FF00')); }
    };
    $panel = (new \Terran\Scenes\UI\Panel('panel', Color::hex('#0000FF')))->setMargin(8.0, 8.0, 12.0, 12.0);
    $hidden->add($plain)->add($panel);
    $hiddenPanel = (new \Terran\Scenes\UI\Panel('hp', Color::hex('#0000FF')))->setMargin(0.0, 12.0, 4.0, 16.0);
    $hiddenPanel->visible = false;
    $shape = \Terran\Scenes\TwoD\Shape2D::rect('shape', 2.0, 2.0);
    $shape->fill = Color::hex('#FF0000');
    $shape->position = new Vector2(13.0, 1.0);
    $hiddenPanel->add($shape);
    $root->add($hidden)->add($hiddenPanel);
    $world->setRoot($root);
    $world->render();
    $fb = $target->framebuffer();

    expect(hexAt($fb, 1, 1))->toBe('000000FF')
        ->and(hexAt($fb, 9, 9))->toBe('000000FF')
        ->and(hexAt($fb, 13, 1))->toBe('000000FF')
        ->and($panel->visibleInTree())->toBeFalse()
        ->and($shape->visibleInTree())->toBeFalse()
        ->and($panel->hitTest(9.0, 9.0, 'main'))->toBeFalse();
});

it('drops focus from a control hidden while focused, and refuses to focus a hidden one', function () {
    [$world] = canvasWorld(32, 16);
    $root = new Scene2D('root');
    $c = new RecordingControl('c');
    $c->focusable = true;
    $c->setMargin(0.0, 0.0, 8.0, 8.0);
    $root->add($c);
    $world->setRoot($root);
    $world->focus($c);
    $c->visible = false;
    $world->tick([new ActionPressed('ui_accept')], 0.0);

    expect($c->got)->toBe([])
        ->and($world->focused())->toBeNull()
        ->and(fn () => $world->focus($c))->toThrow(TerranException::class, "'c' is hidden; show it before focusing it.");
});

it('focuses a focusable control on a press', function () {
    [$world] = canvasWorld(32, 16);
    $root = new Scene2D('root');
    $c = (new UIControl('c'))->setMargin(0.0, 0.0, 8.0, 8.0);
    $c->focusable = true;
    $root->add($c);
    $world->setRoot($root);
    $world->render();
    $world->tick([new MouseButtonPressed(MouseButton::LEFT, 'main', 3.0, 3.0)], 0.0);

    expect($world->focused())->toBe($c);
});
