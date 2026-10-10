<?php
declare(strict_types=1);

use Surface\Contracts\Drawing\OutputTarget;
use Surface\Contracts\EmbeddedDisplays\EmbeddedDisplay;
use Surface\Contracts\Windows\Primitives\TKCanvas;
use Surface\Contracts\Windows\StagedWindow as StagedWindowContract;
use Surface\Windows\StagedWindow;
use Terran\GameLoop;
use Terran\Targets\OwnedTarget;
use Terran\Targets\TargetOpener;
use Terran\TerranException;
use Tests\Fixtures\RecordingSession;
use Voyager\Vessel\ControlPanel;

/**
 * A container holding a GameLoop as 'event-loop', and fake 'staged-windows' and 'displays'
 * managers that record what they were asked into $asked. Every staged window lives on $session.
 */
function openerApp(ArrayObject $asked, ?RecordingSession $session = null): ControlPanel
{
    [$registry, $waiter, $promises] = testLoop();
    $app = new ControlPanel();
    $app->registerInstance('event-loop', new GameLoop($registry, $waiter, $promises));
    $app->registerInstance('staged-windows', new class($asked, $session ?? (new RecordingSession())->connect()) {
        public function __construct(private readonly ArrayObject $asked, private readonly RecordingSession $session) {}
        public function open(string $name, int $w, int $h, array $options = []): StagedWindow
        {
            $this->asked[] = ['staged', $name, $w, $h, $options];
            $window = Mockery::mock(StagedWindow::class, ['name' => $name, 'session' => $this->session]);
            $open = true;
            $window->shouldReceive('isOpen')->andReturnUsing(function () use (&$open): bool { return $open; });
            $window->shouldReceive('close')->andReturnUsing(function () use (&$open): void { $open = false; });

            return $window;
        }
    });
    $app->registerInstance('displays', new class($asked) {
        public function __construct(private readonly ArrayObject $asked) {}
        public function panel(string $panel, ?string $config = null, ?string $name = null, bool $direct = false): EmbeddedDisplay
        {
            $this->asked[] = ['display', $panel, $config, $name, $direct];
            return Mockery::mock(EmbeddedDisplay::class);
        }
        public function detach(string $name): void { $this->asked[] = ['detach', $name]; }
    });

    return $app;
}

afterEach(fn () => Mockery::close());

it('opens a staged window with the title folded into the options', function () {
    $asked = new ArrayObject();
    $opener = new TargetOpener(openerApp($asked));

    $owned = $opener->open('main', ['kind' => 'staged', 'width' => 640, 'height' => 360, 'title' => 'T', 'engine' => 'velvet', 'options' => ['toolkit' => 'sdl3']]);

    expect($asked->getArrayCopy())->toBe([['staged', 'main', 640, 360, ['toolkit' => 'sdl3', 'title' => 'T']]])
        ->and($owned)->toBeInstanceOf(OwnedTarget::class)
        ->and($owned->window)->toBe('main')->and($owned->engine)->toBe('velvet')
        ->and($owned->target)->toBeInstanceOf(OutputTarget::class);
});

it('asks for the configured stager when the target names none', function () {
    $asked = new ArrayObject();
    $opener = new TargetOpener(openerApp($asked), 'glfw');

    $opener->open('main', ['kind' => 'staged', 'width' => 640, 'height' => 360]);

    expect($asked->getArrayCopy())->toBe([['staged', 'main', 640, 360, ['toolkit' => 'glfw']]]);
});

it('lets a target\'s own toolkit option win over the configured stager', function () {
    $asked = new ArrayObject();
    $opener = new TargetOpener(openerApp($asked), 'glfw');

    $opener->open('main', ['kind' => 'staged', 'width' => 640, 'height' => 360, 'options' => ['toolkit' => 'sdl3']]);

    expect($asked->getArrayCopy())->toBe([['staged', 'main', 640, 360, ['toolkit' => 'sdl3']]]);
});

it('leaves the stager to Surface when none is configured', function () {
    $asked = new ArrayObject();
    $opener = new TargetOpener(openerApp($asked));

    $opener->open('main', ['kind' => 'staged', 'width' => 640, 'height' => 360]);

    expect($asked->getArrayCopy())->toBe([['staged', 'main', 640, 360, []]]);
});

it('joins the staged windows\' session to the event loop once, and leaves after the last one closes', function () {
    $asked = new ArrayObject();
    $session = (new RecordingSession())->connect();
    $app = openerApp($asked, $session);
    $opener = new TargetOpener($app);

    $a = $opener->open('a', ['kind' => 'staged', 'width' => 8, 'height' => 8]);
    $b = $opener->open('b', ['kind' => 'staged', 'width' => 8, 'height' => 8]);
    $mail = new stdClass();
    $session->post($mail);

    expect($session->calls)->toBe(['join'])
        ->and($app->make('event-loop')->registry->mail())->toBe([$mail]);

    expect($a->alive())->toBeTrue();
    $a->close();
    expect($session->calls)->toBe(['join'])->and($a->alive())->toBeFalse()->and($b->alive())->toBeTrue();

    $b->close();
    $b->close();
    expect($session->calls)->toBe(['join', 'leave']);
});

it('refuses a staged window with no toolkit session to join, and closes it', function () {
    $app = openerApp(new ArrayObject());
    $window = Mockery::mock(StagedWindowContract::class);
    $window->shouldReceive('close')->once();
    $app->registerInstance('staged-windows', new class($window) {
        public function __construct(private readonly StagedWindowContract $window) {}
        public function open(string $name, int $w, int $h, array $options = []): StagedWindowContract { return $this->window; }
    });

    expect(fn () => (new TargetOpener($app))->open('main', ['kind' => 'staged', 'width' => 8, 'height' => 8]))
        ->toThrow(TerranException::class, "Target 'main' of kind 'staged' joins its window's toolkit session to the loop, which takes a Surface\\Windows\\StagedWindow; the stager returned ".get_class($window).'.');
});

it('takes a canvas the app already owns, and refuses anything that is not a TKCanvas', function () {
    $asked = new ArrayObject();
    $opener = new TargetOpener(openerApp($asked));
    $canvas = Mockery::mock(TKCanvas::class);

    $owned = $opener->open('hud', ['kind' => 'canvas', 'canvas' => $canvas]);
    expect($owned->target)->toBe($canvas)->and($owned->window)->toBeNull()->and($asked->getArrayCopy())->toBe([]);

    expect(fn () => $opener->open('hud', ['kind' => 'canvas', 'canvas' => 'nope']))
        ->toThrow(TerranException::class, "Target 'hud' of kind 'canvas' takes 'canvas', a TKCanvas; got string.");
});

it('conjures a display panel and detaches it on close', function () {
    $asked = new ArrayObject();
    $opener = new TargetOpener(openerApp($asked));

    $owned = $opener->open('tft', ['kind' => 'display', 'panel' => 'st7796', 'config' => 'pi', 'direct' => true]);
    $owned->close();

    expect($asked->getArrayCopy())->toBe([['display', 'st7796', 'pi', 'tft', true], ['detach', 'tft']]);
});

it('names the kinds it knows when given one it does not, and when kind is missing', function () {
    $asked = new ArrayObject();
    $opener = new TargetOpener(openerApp($asked));

    expect(fn () => $opener->open('x', ['kind' => 'hologram']))->toThrow(TerranException::class, "Target 'x' has kind 'hologram'. Known kinds: staged, canvas, display.")
        ->and(fn () => $opener->open('x', []))->toThrow(TerranException::class, "Target 'x' has no 'kind'. Known kinds: staged, canvas, display.");
});

it('requires width and height as positive ints for a staged window', function () {
    $asked = new ArrayObject();
    $opener = new TargetOpener(openerApp($asked));
    expect(fn () => $opener->open('main', ['kind' => 'staged', 'width' => 0, 'height' => 100]))
        ->toThrow(TerranException::class, "Target 'main' of kind 'staged' takes 'width' and 'height' as positive ints; got 0 and 100.");
});

it('takes a new kind through extend()', function () {
    $asked = new ArrayObject();
    $opener = new TargetOpener(openerApp($asked));
    $target = fakeTarget();
    $opener->extend('fake', fn (string $name, array $config, $app) => new OwnedTarget($name, $target, $config['engine'] ?? null, null, null));

    expect($opener->kinds())->toBe(['staged', 'canvas', 'display', 'fake'])
        ->and($opener->open('f', ['kind' => 'fake'])->target)->toBe($target);
});
