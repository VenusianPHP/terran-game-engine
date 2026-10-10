<?php
declare(strict_types=1);

use Surface\Contracts\HumanInput\Events\GamepadConnected;
use Surface\Contracts\HumanInput\GamepadButton;
use Surface\Contracts\HumanInput\HumanInputException;
use Surface\Contracts\HumanInput\Key;
use Surface\Contracts\HumanInput\MouseButton;
use Surface\HumanInput\Devices\GamePad;
use Terran\GameLoop;
use Terran\Input\Events\ActionPressed;
use Terran\Input\Events\ActionReleased;
use Terran\Input\Events\InputEvent;
use Terran\Input\Events\KeyPressed;
use Terran\Input\Events\KeyReleased;
use Terran\Input\Events\MouseButtonPressed;
use Terran\Input\Events\MouseMoved;
use Terran\Input\Events\MouseWheel;
use Terran\Input\Events\PadButtonPressed;
use Terran\Input\Events\PadConnected;
use Terran\Input\Events\TextTyped;
use Terran\Input\InputMap;
use Terran\Input\InputReader;
use Terran\Scene;
use Terran\World;
use Tests\Fixtures\FakeHumanInput;
use Voyager\Signals\SignalDispatcher;

final class Listener extends Scene
{
    /** @var list<InputEvent> */
    public array $seen = [];
    public bool $handles = false;
    public array $physics = [];
    public float $last = 0.0;
    public function process(float $delta): void
    {
        $this->last = $this->world()->loop()->now();
    }

    public function input(InputEvent $event): bool { $this->seen[] = $event; return $this->handles; }
    public function physicsProcess(float $delta): void { $this->physics[] = $this->actions()->isPressed('jump'); }
}

/** @return array{0: World, 1: InputReader, 2: FakeHumanInput, 3: ?SignalDispatcher} */
function inputWorld(array $actions = [], array $events = ['keyboard', 'mouse'], ?int &$now = null): array
{
    $now = 0;
    [$registry, $waiter, $promises] = testLoop();
    $loop = new GameLoop($registry, $waiter, $promises, null, 60.0, 8, function () use (&$now): int { return $now; });
    $map = new InputMap($actions);
    $signals = new SignalDispatcher();
    $world = new World($loop, $signals, null, [], $map);
    $human = new FakeHumanInput(inputFrame());

    return [$world, new InputReader($human, $map, $events), $human, $signals];
}

it('reads no device when no binding and no live scene wants input', function () {
    [$world, $reader, $human] = inputWorld();
    $world->setRoot(new class('root') extends Scene {});

    expect($reader->read([], $world))->toBe([])
        ->and($human->reads)->toBe(['keyboard' => 0, 'mouse' => 0, 'pads' => 0, 'controllers' => 0]);
});

it('reads only pads for a pad-only map, so a failing keyboard never stops it', function () {
    [$world, $reader, $human] = inputWorld(['jump' => ['pad:south']]);
    $world->setRoot(new class('root') extends Scene {});
    $human->keyboard_fails = new HumanInputException('No input engine for the appkit session.');

    $reader->read([], $world);

    expect($human->reads)->toBe(['keyboard' => 0, 'mouse' => 0, 'pads' => 1, 'controllers' => 1]);
});

it('lets HumanInput\'s exception through unchanged when it does read the keyboard', function () {
    [$world, $reader, $human] = inputWorld(['jump' => ['key:space']]);
    $world->setRoot(new class('root') extends Scene {});
    $failure = new HumanInputException('No input engine for the appkit session.');
    $human->keyboard_fails = $failure;

    try {
        $reader->read([], $world);
        $caught = null;
    } catch (Throwable $e) {
        $caught = $e;
    }

    expect($caught)->toBe($failure);
});

it('reads keyboard and mouse for events while a live scene overrides input(), and pads only when asked', function () {
    [$world, $reader, $human] = inputWorld();
    $world->setRoot(new Listener('root'));
    $reader->read([], $world);
    expect($human->reads)->toBe(['keyboard' => 1, 'mouse' => 1, 'pads' => 0, 'controllers' => 0]);

    [$world, $reader, $human] = inputWorld([], ['keyboard', 'mouse', 'pads']);
    $world->setRoot(new Listener('root'));
    $reader->read([], $world);
    expect($human->reads)->toBe(['keyboard' => 1, 'mouse' => 1, 'pads' => 1, 'controllers' => 1]);
});

it('turns the frame\'s edges into events, in order', function () {
    [$world, $reader, $human] = inputWorld(['jump' => ['key:space']], ['keyboard', 'mouse', 'pads']);
    $target = fakeTarget();
    $world->addViewport('main', $target, drawing()->renderer('velvet', ['output' => $target]), null, null, 'main');
    $world->setRoot(new Listener('root'));
    $pad = new GamePad($human->input_frame, 'p1', 'Pad', [GamepadButton::SOUTH]);
    $human->hand_pads['p1'] = $pad;

    $human->kb->update(Key::SPACE, true);
    $human->kb->appendText(' ');
    $human->pointer->setPosition(4.0, 2.0, 'main')->addMotion(1.0, 0.5)->update(MouseButton::LEFT, true);
    $human->pointer->addWheel(0.0, 1.0);
    $pad->update(GamepadButton::SOUTH, true);

    $events = $reader->read([new GamepadConnected('p1', 'Pad')], $world);

    expect(array_map(fn ($e) => $e::class, $events))->toBe([
        PadConnected::class, KeyPressed::class, TextTyped::class, MouseButtonPressed::class,
        MouseMoved::class, MouseWheel::class, PadButtonPressed::class, ActionPressed::class,
    ])
        ->and($events[1]->key)->toBe(Key::SPACE)
        ->and($events[2]->text)->toBe(' ')
        ->and([$events[3]->viewport, $events[3]->x, $events[3]->y])->toBe(['main', 4.0, 2.0])
        ->and([$events[4]->dx, $events[4]->dy])->toBe([1.0, 0.5])
        ->and($events[6]->pad)->toBe('p1')
        ->and($events[7]->action)->toBe('jump')
        ->and($events[7]->name())->toBe('terran.input.action.pressed');
});

it('gives a tap inside one frame both edges as events', function () {
    [$world, $reader, $human] = inputWorld(['jump' => ['key:space']]);
    $world->setRoot(new class('root') extends Scene {});
    $human->kb->update(Key::SPACE, true);
    $human->kb->update(Key::SPACE, false);

    $classes = array_map(fn ($e) => $e::class, $reader->read([], $world));

    expect($classes)->toBe([KeyPressed::class, KeyReleased::class, ActionPressed::class, ActionReleased::class]);
});

it('walks events down the tree until a scene handles one, and dispatches every event on signals', function () {
    [$world, $reader, $human, $signals] = inputWorld();
    $heard = [];
    // A wildcard listener hears (name, payload); the payload of an object signal is [the signal].
    $signals->listen('terran.input.*', function (string $name, array $payload) use (&$heard) { $heard[] = $payload[0]->name(); });
    $root = new Listener('root');
    $child = new Listener('child');
    $root->add($child);
    $root->handles = true;
    $world->setRoot($root);
    $human->kb->update(Key::A, true);

    $world->tick($reader->read([], $world), 0.016);

    expect(count($root->seen))->toBe(1)->and($child->seen)->toBe([])
        ->and($heard)->toBe(['terran.input.key.pressed']);
});

it('shows a tap to exactly one physics step', function () {
    $now = 0;
    [$world, $reader, $human] = inputWorld(['jump' => ['key:space']], ['keyboard', 'mouse'], $now);
    $root = new Listener('root');
    $world->setRoot($root);
    $loop = $world->loop();
    $loop->advance(0);

    $human->kb->update(Key::SPACE, true);
    $human->kb->update(Key::SPACE, false);
    $world->tick($reader->read([], $world), 0.016);
    $human->settle();
    $loop->advance(3 * $loop->stepNs());

    $human->settle();
    $world->tick($reader->read([], $world), 0.016);
    $loop->advance(4 * $loop->stepNs());

    expect($root->physics)->toBe([true, false, false, false])
        ->and(array_filter($root->seen, fn ($e) => $e instanceof ActionPressed))->toHaveCount(1);
});

it('reads the map from process() and the latch from physicsProcess()', function () {
    [$world] = inputWorld(['jump' => ['key:space']]);
    $root = new class('root') extends Scene {
        public array $kinds = [];
        public function process(float $delta): void { $this->kinds[] = $this->actions()::class; }
        public function physicsProcess(float $delta): void { $this->kinds[] = $this->actions()::class; }
    };
    $world->setRoot($root);
    $world->tick([], 0.016);
    $world->loop()->advance(0);
    $world->loop()->advance($world->loop()->stepNs());

    expect($root->kinds)->toBe([InputMap::class, \Terran\Input\PhysicsLatch::class]);
});

it('scales mouse motion to the viewport\'s pixels, like its position', function () {
    [$world, $reader, $human] = inputWorld();
    $window = Mockery::mock(\Surface\Contracts\Windows\StagedWindow::class);
    $window->allows('pixelSize')->andReturn([200, 100]);
    $window->allows('size')->andReturn([100, 50]);
    $world->addViewport('main', $window, Mockery::mock(\Surface\Contracts\Drawing\RenderingEngine::class), null, null, 'main');
    $world->setRoot(new Listener('root'));
    $human->pointer->setPosition(10.0, 20.0, 'main')->addMotion(1.0, 0.5);

    $moved = array_values(array_filter($reader->read([], $world), fn ($e) => $e instanceof MouseMoved))[0];

    expect([$moved->x, $moved->y, $moved->dx, $moved->dy])->toBe([20.0, 40.0, 2.0, 1.0]);
    Mockery::close();
});

/** @return array{0: World, 1: InputReader, 2: FakeHumanInput, 3: \Terran\Input\Seats} */
function seatWorld(array $actions, int $max = 4, ?int $keyboard_seat = 1, ?int &$now = null): array
{
    $now = 0;
    [$registry, $waiter, $promises] = testLoop();
    $loop = new GameLoop($registry, $waiter, $promises, null, 60.0, 8, function () use (&$now): int { return $now; });
    $seats = new \Terran\Input\Seats($max, $keyboard_seat);
    $map = new InputMap($actions, $seats);
    $world = new World($loop, new SignalDispatcher(), null, [], $map);
    $human = new FakeHumanInput(inputFrame());

    return [$world, new InputReader($human, $map, ['keyboard', 'mouse'], $seats), $human, $seats];
}

function seatedPad(FakeHumanInput $human, string $id, string $hardware): GamePad
{
    return $human->hand_pads[$id] = new GamePad($human->input_frame, $id, 'Pad', [GamepadButton::SOUTH], $hardware);
}

it('seats the frame\'s pads before its events, and every pad and action event carries its seat', function () {
    [$world, $reader, $human] = seatWorld(['jump' => ['pad:south']]);
    $world->setRoot(new Listener('root'));
    $one = seatedPad($human, 'p1', 'hw-1');
    $two = seatedPad($human, 'p2', 'hw-2');
    $two->update(GamepadButton::SOUTH, true);

    $events = $reader->read([new GamepadConnected('p1', 'Pad'), new GamepadConnected('p2', 'Pad')], $world);

    expect(array_map(fn ($e) => [$e::class, $e->seat], $events))->toBe([
        [PadConnected::class, 1], [PadConnected::class, 2],
        [\Terran\Input\Events\PadButtonPressed::class, 2],
        [ActionPressed::class, 2],
    ]);
});

it('gives a disconnected pad\'s event the seat it keeps reserved', function () {
    [$world, $reader, $human, $seats] = seatWorld(['jump' => ['pad:south']]);
    $world->setRoot(new class('root') extends Scene {});
    seatedPad($human, 'p1', 'hw-1');
    $reader->read([], $world);
    unset($human->hand_pads['p1']);

    $events = $reader->read([new \Surface\Contracts\HumanInput\Events\GamepadDisconnected('p1')], $world);

    expect($events[0])->toBeInstanceOf(\Terran\Input\Events\PadDisconnected::class)
        ->and($events[0]->seat)->toBe(1)
        ->and($events[0]->name)->toBe('Pad')
        ->and($seats->padIn(1))->toBeNull();
});

it('gives events seat null with seating off, as slice 2a did', function () {
    [$world, $reader, $human] = seatWorld(['jump' => ['pad:south']], 0);
    $world->setRoot(new class('root') extends Scene {});
    seatedPad($human, 'p1', 'hw-1')->update(GamepadButton::SOUTH, true);

    $events = $reader->read([], $world);

    expect(array_map(fn ($e) => [$e::class, $e->seat], $events))->toBe([
        [\Terran\Input\Events\PadButtonPressed::class, null], [ActionPressed::class, null],
    ]);
});

it('reads a seat through Scene::actions(), and its latch inside physicsProcess()', function () {
    $now = 0;
    [$world, $reader, $human] = seatWorld(['jump' => ['pad:south']], 4, 1, $now);
    $root = new class('root') extends Scene {
        public array $frames = [];
        public array $steps = [];
        public function process(float $delta): void { $this->frames[] = [$this->actions(1)->isPressed('jump'), $this->actions(2)->isPressed('jump')]; }
        public function physicsProcess(float $delta): void { $this->steps[] = [$this->actions(1)->isPressed('jump'), $this->actions(2)->isPressed('jump')]; }
    };
    $world->setRoot($root);
    $loop = $world->loop();
    $loop->advance(0);
    seatedPad($human, 'p1', 'hw-1');
    $two = seatedPad($human, 'p2', 'hw-2');
    $two->update(GamepadButton::SOUTH, true);
    $two->update(GamepadButton::SOUTH, false);

    $world->tick($reader->read([], $world), 0.016);
    $human->settle();
    $loop->advance(2 * $loop->stepNs());

    expect($root->frames)->toBe([[false, true]])
        ->and($root->steps)->toBe([[false, true], [false, false]])
        ->and(fn () => $root->actions(5))->toThrow(\Terran\TerranException::class, 'Seat 5 is beyond terran.input.seats (4).');
});

it('names a disconnected pad it never saw as null rather than failing', function () {
    [$world, $reader] = inputWorld();
    $world->setRoot(new class('root') extends Scene {});

    $events = $reader->read([new \Surface\Contracts\HumanInput\Events\GamepadDisconnected('ghost')], $world);

    expect([$events[0]->pad, $events[0]->name, $events[0]->seat])->toBe(['ghost', null, null]);
});
