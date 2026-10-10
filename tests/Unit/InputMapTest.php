<?php
declare(strict_types=1);

use Surface\Contracts\HumanInput\GamepadAxis;
use Surface\Contracts\HumanInput\GamepadButton;
use Surface\Contracts\HumanInput\Key;
use Surface\Contracts\HumanInput\MouseButton;
use Surface\HumanInput\Devices\GameController;
use Surface\HumanInput\Devices\GamePad;
use Surface\HumanInput\Devices\Keyboard;
use Surface\HumanInput\Devices\Mouse;
use Terran\Input\Binding;
use Terran\Input\BindingDevice;
use Terran\Input\InputMap;
use Terran\TerranException;

it('parses the four binding forms, with an optional dead zone', function () {
    expect(Binding::parse('a', 'key:left'))->toEqual(new Binding(BindingDevice::KEY, Key::LEFT))
        ->and(Binding::parse('a', 'mouse:left'))->toEqual(new Binding(BindingDevice::MOUSE, MouseButton::LEFT))
        ->and(Binding::parse('a', 'pad:dpad_left'))->toEqual(new Binding(BindingDevice::PAD, GamepadButton::DPAD_LEFT))
        ->and(Binding::parse('a', 'axis:left_x:-'))->toEqual(new Binding(BindingDevice::AXIS, GamepadAxis::LEFT_X, -1, 0.15))
        ->and(Binding::parse('a', 'axis:left_trigger:+:0.4'))->toEqual(new Binding(BindingDevice::AXIS, GamepadAxis::LEFT_TRIGGER, 1, 0.4));
});

it('names the accepted forms and values when a binding is wrong', function () {
    expect(fn () => Binding::parse('jump', 'kye:space'))->toThrow(TerranException::class, "Action 'jump' binding 'kye:space' is not key:<key>, mouse:<button>, pad:<button> or axis:<axis>:+|-[:dead zone].")
        ->and(fn () => Binding::parse('jump', 'key:spcae'))->toThrow(TerranException::class, "Action 'jump' binding 'key:spcae': 'spcae' is not a Key. Keys: ")
        ->and(fn () => Binding::parse('jump', 'axis:left_x'))->toThrow(TerranException::class, "Action 'jump' binding 'axis:left_x' needs a direction: axis:left_x:+ or axis:left_x:-.")
        ->and(fn () => Binding::parse('jump', 'axis:left_x:+:1.5'))->toThrow(TerranException::class, "Action 'jump' binding 'axis:left_x:+:1.5': the dead zone is at least 0 and below 1, got 1.5.");
});

it('says which devices its bindings read', function () {
    expect((new InputMap(['jump' => ['key:space']]))->devices())->toBe(['key' => true, 'mouse' => false, 'pad' => false])
        ->and((new InputMap(['fire' => ['mouse:left', 'pad:south'], 'steer' => ['axis:left_x:+']]))->devices())->toBe(['key' => false, 'mouse' => true, 'pad' => true]);
});

it('reads a key action down, pressed and released across frames', function () {
    $frame = inputFrame();
    $keyboard = new Keyboard($frame);
    $map = new InputMap(['jump' => ['key:space', 'key:w']]);

    $keyboard->update(Key::SPACE, true);
    expect($map->capture($keyboard, null, []))->toBe(['pressed' => ['jump'], 'released' => []])
        ->and($map->isDown('jump'))->toBeTrue()->and($map->isPressed('jump'))->toBeTrue()->and($map->strength('jump'))->toBe(1.0);

    $keyboard->settle();
    $map->capture($keyboard, null, []);
    expect($map->isPressed('jump'))->toBeFalse()->and($map->isDown('jump'))->toBeTrue();

    $keyboard->settle();
    $keyboard->update(Key::SPACE, false);
    expect($map->capture($keyboard, null, []))->toBe(['pressed' => [], 'released' => ['jump']])
        ->and($map->wasReleased('jump'))->toBeTrue()->and($map->isDown('jump'))->toBeFalse();
});

it('reads a tap inside one frame as pressed and released together', function () {
    $keyboard = new Keyboard(inputFrame());
    $map = new InputMap(['jump' => ['key:space']]);
    $keyboard->update(Key::SPACE, true);
    $keyboard->update(Key::SPACE, false);

    expect($map->capture($keyboard, null, []))->toBe(['pressed' => ['jump'], 'released' => ['jump']])
        ->and($map->isDown('jump'))->toBeFalse();
});

it('does not release an action while another of its bindings is held', function () {
    $keyboard = new Keyboard(inputFrame());
    $map = new InputMap(['jump' => ['key:space', 'key:w']]);
    $keyboard->update(Key::SPACE, true);
    $keyboard->update(Key::W, true);
    $map->capture($keyboard, null, []);
    $keyboard->settle();
    $keyboard->update(Key::SPACE, false);

    expect($map->capture($keyboard, null, []))->toBe(['pressed' => [], 'released' => []])
        ->and($map->isDown('jump'))->toBeTrue();
});

it('reads mouse and pad buttons on any pad', function () {
    $frame = inputFrame();
    $mouse = new Mouse($frame);
    $pad = new GamePad($frame, 'p1', 'Pad', [GamepadButton::SOUTH]);
    $map = new InputMap(['fire' => ['mouse:left'], 'jump' => ['pad:south']]);
    $mouse->update(MouseButton::LEFT, true);
    $pad->update(GamepadButton::SOUTH, true);

    expect($map->capture(null, $mouse, [$pad]))->toBe(['pressed' => ['fire', 'jump'], 'released' => []]);
});

it('reads axis strength past the dead zone, rescaled, and an axis pair', function () {
    $stick = new GameController(inputFrame(), 'c1', 'Pad', [], [GamepadAxis::LEFT_X]);
    $map = new InputMap(['left' => ['axis:left_x:-', 'key:a'], 'right' => ['axis:left_x:+']]);

    $stick->setAxis(GamepadAxis::LEFT_X, 0.1);
    $map->capture(null, null, [$stick]);
    expect($map->strength('right'))->toBe(0.0)->and($map->isDown('right'))->toBeFalse();

    $stick->setAxis(GamepadAxis::LEFT_X, -0.575);
    expect($map->capture(null, null, [$stick]))->toBe(['pressed' => ['left'], 'released' => []])
        ->and($map->strength('left'))->toEqualWithDelta(0.5, 1e-9)
        ->and($map->axis('left', 'right'))->toEqualWithDelta(-0.5, 1e-9);

    $stick->setAxis(GamepadAxis::LEFT_X, 0.0);
    expect($map->capture(null, null, [$stick]))->toBe(['pressed' => [], 'released' => ['left']]);
});

it('names the actions it has when asked for another', function () {
    $map = new InputMap(['jump' => ['key:space'], 'left' => ['key:a']]);
    expect(fn () => $map->isDown('jmp'))->toThrow(TerranException::class, "No action 'jmp'. Actions: jump, left.");
});

it('latches edges for physics until a step clears them, carrying them across frames with no step', function () {
    $keyboard = new Keyboard(inputFrame());
    $map = new InputMap(['jump' => ['key:space']]);
    $latch = $map->physics();

    $keyboard->update(Key::SPACE, true);
    $keyboard->update(Key::SPACE, false);
    $map->capture($keyboard, null, []);
    $keyboard->settle();
    $map->capture($keyboard, null, []);

    expect($map->isPressed('jump'))->toBeFalse()
        ->and($latch->isPressed('jump'))->toBeTrue()->and($latch->wasReleased('jump'))->toBeTrue();

    $latch->clear();
    expect($latch->isPressed('jump'))->toBeFalse()->and($latch->isDown('jump'))->toBeFalse();
});

it('does not press an action again when a second binding goes down while it is held', function () {
    $keyboard = new Keyboard(inputFrame());
    $map = new InputMap(['jump' => ['key:space', 'key:w']]);
    $keyboard->update(Key::SPACE, true);
    $first = $map->capture($keyboard, null, []);
    $keyboard->settle();
    $keyboard->update(Key::W, true);
    $second = $map->capture($keyboard, null, []);
    $keyboard->settle();
    $keyboard->update(Key::W, false);
    $third = $map->capture($keyboard, null, []);
    $keyboard->settle();
    $keyboard->update(Key::SPACE, false);
    $fourth = $map->capture($keyboard, null, []);

    expect([$first, $second, $third, $fourth])->toBe([
        ['pressed' => ['jump'], 'released' => []],
        ['pressed' => [], 'released' => []],
        ['pressed' => [], 'released' => []],
        ['pressed' => [], 'released' => ['jump']],
    ])->and($map->physics()->isPressed('jump'))->toBeTrue();
});

/** Two pads seated 1 and 2, a keyboard on seat 1, and a map over them. @return array{0: InputMap, 1: Keyboard, 2: GamePad, 3: GamePad, 4: \Terran\Input\Seats} */
function seatedMap(array $actions, int $max = 4, ?int $keyboard_seat = 1): array
{
    $frame = inputFrame();
    $keyboard = new Keyboard($frame);
    $one = new GamePad($frame, 'p1', 'Pad', [GamepadButton::SOUTH], 'hw-1');
    $two = new GamePad($frame, 'p2', 'Pad', [GamepadButton::SOUTH], 'hw-2');
    $seats = new \Terran\Input\Seats($max, $keyboard_seat);
    $seats->reconcile([$one, $two]);

    return [new InputMap($actions, $seats), $keyboard, $one, $two, $seats];
}

it('keeps action state per seat: a seat reads its own pad, and the keyboard on the keyboard seat', function () {
    [$map, $keyboard, $one, $two] = seatedMap(['jump' => ['pad:south', 'key:space']]);
    $two->update(GamepadButton::SOUTH, true);
    $map->capture($keyboard, null, [$one, $two]);

    expect($map->isPressed('jump'))->toBeTrue()
        ->and($map->seat(1)->isPressed('jump'))->toBeFalse()
        ->and($map->seat(2)->isPressed('jump'))->toBeTrue()
        ->and($map->seat(3)->isDown('jump'))->toBeFalse()
        ->and($map->edges())->toBe([['action' => 'jump', 'seat' => 2, 'kind' => 'pressed']]);

    $two->settle();
    $keyboard->update(Key::SPACE, true);
    $map->capture($keyboard, null, [$one, $two]);
    expect($map->seat(1)->isPressed('jump'))->toBeTrue()
        ->and($map->seat(2)->isPressed('jump'))->toBeFalse()
        ->and($map->isPressed('jump'))->toBeFalse()
        ->and($map->edges())->toBe([['action' => 'jump', 'seat' => 1, 'kind' => 'pressed']]);
});

it('gives input no seat owns one edge with seat null', function () {
    [$map, $keyboard, $one, $two] = seatedMap(['jump' => ['pad:south', 'key:space']], 1, null);
    $two->update(GamepadButton::SOUTH, true);
    $keyboard->update(Key::SPACE, true);

    $map->capture($keyboard, null, [$one, $two]);

    expect($map->edges())->toBe([['action' => 'jump', 'seat' => null, 'kind' => 'pressed']])
        ->and($map->seat(1)->isDown('jump'))->toBeFalse();
});

it('gives every edge seat null with seating off or no Seats, as slice 2a did', function () {
    $keyboard = new Keyboard(inputFrame());
    $map = new InputMap(['jump' => ['key:space']], new \Terran\Input\Seats(0));
    $keyboard->update(Key::SPACE, true);
    $keyboard->update(Key::SPACE, false);
    $map->capture($keyboard, null, []);

    expect($map->edges())->toBe([['action' => 'jump', 'seat' => null, 'kind' => 'pressed'], ['action' => 'jump', 'seat' => null, 'kind' => 'released']])
        ->and(fn () => $map->seat(1))->toThrow(TerranException::class, 'Seating is off: terran.input.seats is 0.')
        ->and(fn () => (new InputMap([]))->seat(1))->toThrow(TerranException::class, 'Seating is off: terran.input.seats is 0.');
});

it('latches each seat\'s edges for physics once', function () {
    [$map, $keyboard, $one, $two] = seatedMap(['jump' => ['pad:south']]);
    $two->update(GamepadButton::SOUTH, true);
    $two->update(GamepadButton::SOUTH, false);
    $map->capture(null, null, [$one, $two]);
    $two->settle();
    $map->capture(null, null, [$one, $two]);

    $latch = $map->physics();
    expect($latch->seat(2)->isPressed('jump'))->toBeTrue()
        ->and($latch->seat(2)->wasReleased('jump'))->toBeTrue()
        ->and($latch->seat(1)->isPressed('jump'))->toBeFalse()
        ->and($latch->isPressed('jump'))->toBeTrue();

    $latch->clear();
    expect($latch->seat(2)->isPressed('jump'))->toBeFalse()
        ->and(fn () => $latch->seat(9))->toThrow(TerranException::class, 'Seat 9 is beyond terran.input.seats (4).');
});

it('reads a seat at rest while its pad is gone', function () {
    [$map, $keyboard, $one, $two, $seats] = seatedMap(['jump' => ['pad:south']]);
    $two->update(GamepadButton::SOUTH, true);
    $map->capture(null, null, [$one, $two]);
    $seats->reconcile([$one]);
    $map->capture(null, null, [$one]);

    expect($map->seat(2)->isDown('jump'))->toBeFalse()
        ->and($map->edges())->toBe([['action' => 'jump', 'seat' => 2, 'kind' => 'released']]);
});
