<?php
declare(strict_types=1);

use Surface\Contracts\HumanInput\GamepadButton;
use Surface\HumanInput\Devices\GamePad;
use Terran\Input\Seats;
use Terran\TerranException;

/** A pad that records the player indexes it was lit with into $lights[$id]. */
function seatPad(string $id, ?string $hardware, array &$lights): GamePad
{
    return new GamePad(inputFrame(), $id, 'Pad', [GamepadButton::SOUTH], $hardware, function (?int $index) use ($id, &$lights): void {
        $lights[$id][] = $index;
    });
}

it('seats new pads in the lowest free seats, in listing order, and lights them', function () {
    $lights = [];
    $seats = new Seats(4);
    $a = seatPad('sdl3-1', 'hw-a', $lights);
    $b = seatPad('sdl3-2', 'hw-b', $lights);

    expect($seats->reconcile([$a, $b]))->toBe(['seated' => ['sdl3-1' => 1, 'sdl3-2' => 2], 'left' => []])
        ->and($seats->all())->toBe([1 => 'sdl3-1', 2 => 'sdl3-2'])
        ->and($seats->pad(2))->toBe($b)
        ->and($lights)->toBe(['sdl3-1' => [0], 'sdl3-2' => [1]]);
});

it('keeps a seat reserved while its pad is gone, and gives it back on reconnect under a new id', function () {
    $lights = [];
    $seats = new Seats(4);
    $seats->reconcile([seatPad('sdl3-1', 'hw-a', $lights), seatPad('sdl3-2', 'hw-b', $lights)]);

    expect($seats->reconcile([seatPad('sdl3-2', 'hw-b', $lights)]))->toBe(['seated' => [], 'left' => ['sdl3-1' => 1]])
        ->and($seats->padIn(1))->toBeNull()
        ->and($seats->seatOf('sdl3-1'))->toBe(1);

    $c = seatPad('sdl3-3', 'hw-c', $lights);
    $back = seatPad('sdl3-9', 'hw-a', $lights);
    expect($seats->reconcile([seatPad('sdl3-2', 'hw-b', $lights), $c, $back]))->toBe(['seated' => ['sdl3-3' => 3, 'sdl3-9' => 1], 'left' => []])
        ->and($seats->seatOf('sdl3-9'))->toBe(1)
        ->and($lights['sdl3-9'])->toBe([0]);
});

it('seats a pad with no hardware id by its id', function () {
    $lights = [];
    $seats = new Seats(2);
    $seats->reconcile([seatPad('p1', null, $lights)]);
    $seats->reconcile([]);
    expect($seats->reconcile([seatPad('p1', null, $lights)]))->toBe(['seated' => ['p1' => 1], 'left' => []]);
});

it('takes over the lowest reservation when every seat is held or reserved, and unseats a pad past the last', function () {
    $lights = [];
    $seats = new Seats(2);
    $seats->reconcile([seatPad('a', 'hw-a', $lights), seatPad('b', 'hw-b', $lights)]);
    $seats->reconcile([seatPad('b', 'hw-b', $lights)]);

    expect($seats->reconcile([seatPad('b', 'hw-b', $lights), seatPad('c', 'hw-c', $lights)]))->toBe(['seated' => ['c' => 1], 'left' => []])
        ->and($seats->seatOf('a'))->toBeNull();

    expect($seats->reconcile([seatPad('b', 'hw-b', $lights), seatPad('c', 'hw-c', $lights), seatPad('d', 'hw-d', $lights)]))->toBe(['seated' => [], 'left' => []])
        ->and($seats->seatOf('d'))->toBeNull();
});

it('seats nobody on its own in lobby mode; assign() and release() do it, with lights', function () {
    $lights = [];
    $seats = new Seats(4, 1, false);
    $a = seatPad('a', 'hw-a', $lights);
    $b = seatPad('b', 'hw-b', $lights);
    $seats->reconcile([$a, $b]);
    expect($seats->all())->toBe([]);

    $seats->assign(2, 'a');
    $seats->assign(2, 'b');
    $seats->assign(3, 'b');
    $seats->release(3);

    expect($seats->all())->toBe([])
        ->and($lights)->toBe(['a' => [1, null], 'b' => [1, 2, null]]);
});

it('moves a pad that assign() seats elsewhere out of its old seat', function () {
    $lights = [];
    $seats = new Seats(4);
    $seats->reconcile([seatPad('a', 'hw-a', $lights)]);
    $seats->assign(3, 'a');
    expect($seats->all())->toBe([3 => 'a']);
});

it('names what it takes when assign() or a seat number is wrong', function () {
    $lights = [];
    $seats = new Seats(4);
    $seats->reconcile([seatPad('a', 'hw-a', $lights)]);

    expect(fn () => $seats->assign(1, 'zz'))->toThrow(TerranException::class, "Pad 'zz' is not connected. Connected: a.")
        ->and(fn () => $seats->assign(5, 'a'))->toThrow(TerranException::class, 'Seat 5 is beyond terran.input.seats (4).')
        ->and(fn () => $seats->release(0))->toThrow(TerranException::class, 'Seats are numbered from 1; got 0.')
        ->and(fn () => (new Seats(0))->assign(1, 'a'))->toThrow(TerranException::class, 'Seating is off: terran.input.seats is 0.')
        ->and(fn () => new Seats(-1))->toThrow(TerranException::class, 'terran.input.seats is 0 or more; got -1.')
        ->and(fn () => new Seats(2, 3))->toThrow(TerranException::class, 'terran.input.keyboard_seat is null or a seat from 1 to 2; got 3.');
});

it('does nothing with seating off', function () {
    $lights = [];
    $seats = new Seats(0);
    expect($seats->reconcile([seatPad('a', 'hw-a', $lights)]))->toBe(['seated' => [], 'left' => []])
        ->and($seats->seatOf('a'))->toBeNull()->and($lights)->toBe([]);
});

it('relights a pad replugged between two listings under a new id, and leaves a steady pad alone', function () {
    $lights = [];
    $seats = new Seats(4);
    $seats->reconcile([seatPad('sdl3-1', 'hw-a', $lights)]);
    $seats->reconcile([seatPad('sdl3-1', 'hw-a', $lights)]);
    expect($seats->reconcile([seatPad('sdl3-5', 'hw-a', $lights)]))->toBe(['seated' => ['sdl3-5' => 1], 'left' => []])
        ->and($lights)->toBe(['sdl3-1' => [0], 'sdl3-5' => [0]]);
});

it('gives a pad with no hardware id back the seat a same-named pad with none left, under its new id', function () {
    $lights = [];
    $seats = new Seats(4);
    $seats->reconcile([seatPad('sdl3-5', 'hw-ps4', $lights), new GamePad(inputFrame(), 'sdl3-6', 'Controller', [], null)]);
    $seats->reconcile([seatPad('sdl3-5', 'hw-ps4', $lights)]);

    $back = new GamePad(inputFrame(), 'sdl3-8', 'Controller', [], null);
    $stranger = new GamePad(inputFrame(), 'sdl3-9', 'Other Pad', [], null);
    expect($seats->reconcile([seatPad('sdl3-5', 'hw-ps4', $lights), $stranger, $back]))->toBe(['seated' => ['sdl3-9' => 3, 'sdl3-8' => 2], 'left' => []])
        ->and($seats->seatOf('sdl3-6'))->toBeNull();
});
