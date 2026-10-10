# venusian/terran

Godot-shaped game engine for Venusian Framework applications. Scenes in a tree, a World that owns it, one Viewport per output target. Physics ticks on the event loop at a fixed step; rendering ticks on the sketch at the refresh rate. Draws through Surface's rendering engines onto a staged window, a toolkit canvas or an embedded panel, and never names which.

## Install

```bash
composer require venusian/terran
php computer vendor:publish --tag=terran-config
```

Until the 0.10 `venusian-surface/*` splits publish, `composer.json` carries path repositories to the framework and surface checkouts beside this one.

## Run

```bash
php rocket game
```

Set `terran.main_scene` (or `TERRAN_MAIN_SCENE`) to a Scene class. The default target is a staged window, 960×540, on the Velvet software engine.

```php
use Terran\Scene;
use Surface\Contracts\Drawing\RenderingEngine;
use Surface\NutsAndBolts\Color;

final class Bounce extends Scene
{
    private float $x = 0.0;

    public function physicsProcess(float $delta): void { $this->x += 120 * $delta; }

    public function draw(RenderingEngine $ge): void { $ge->fillRect($this->x, 40, 40, 40, Color::hex('#FF8800')); }
}
```

## Hooks

| Hook | When |
|---|---|
| `ready()` | once, when the Scene's tree goes live |
| `physicsProcess(float $step)` | every fixed step on the GameLoop; `$step` is `1 / terran.physics_hz`, constant |
| `process(float $delta)` | every rendered frame, with the real frame delta |
| `input(InputEvent $event): bool` | every input event this frame, until a Scene returns true |
| `draw(RenderingEngine $ge)` | when a Viewport renders this Scene |
| `exit()` | once, when removed or the World is destroyed |

A step runs at most `terran.max_physics_steps` (8) times per advance; a longer stall is dropped, not replayed. A hook that waits on a promise throws; start I/O with `$this->world()->async(...)` and take the result in `->then()`.

## Interrupts

```php
#[Interrupt('hit', [Scene::class, 'int'])]
class Paddle extends Scene {}

$paddle->connect('hit', $score, 'onHit');   // or a callable: $score->onHit(...)
$paddle->emit('hit', $ball, 10);           // connections run; app('signals') hears terran.<path>.hit
```

Declarations inherit. A freed Scene's connections go with it, and connections to it are dropped.

## Autoloads

```php
#[Autoload(ScoreKeeper::class)]
class Level extends Scene { public function ready(): void { $this->autoload(ScoreKeeper::class)->reset(); } }
```

One instance per World, inherited by subclasses; `terran.autoloads` lists world-wide ones. A Scene class autoloads as a child of the root.

## Input

```php
// config/terran.php
'input' => ['actions' => ['jump' => ['key:space', 'pad:south'], 'left' => ['key:a', 'axis:left_x:-']]],

public function physicsProcess(float $delta): void
{
    if ($this->actions()->isPressed('jump')) { … }        // each tap reaches one step
    $this->x += 200 * $delta * $this->actions()->axis('left', 'right');
}
```

Bindings: `key:<Key>`, `mouse:<MouseButton>`, `pad:<GamepadButton>`, `axis:<GamepadAxis>:+|-[:dead zone]` (default 0.15), values as HumanInput's enums spell them. Terran reads HumanInput once a frame and only the devices the game uses: bound ones, plus `terran.input.events` (keyboard, mouse) while a live Scene overrides `input()`. Mouse event `x`, `y` and motion `dx`, `dy` are the Viewport's framebuffer pixels. A second binding going down under a held action is no new press.

### Seats

```php
'input' => ['seats' => 4, 'keyboard_seat' => 1, 'seat_on_connect' => true, …],

$p2 = $this->actions(2);                              // seat 2's pad only
$this->x += 200 * $delta * $p2->axis('left', 'right');
```

Pads take the lowest free seat as they connect and are lit as that player. A pad that leaves keeps its seat reserved and gets it back on reconnect: by hardware id, or by name for a pad with none. With no free seat a new pad takes over a reservation, else stays unseated (its presses carry seat null). Keyboard and mouse belong to `keyboard_seat`. `actions()` with no seat reads every source. Pad and action events carry `seat`. For a lobby set `seat_on_connect` to false and seat pads yourself with `app('terran.seats')->assign($seat, $event->pad)` / `release($seat)`. `seats` 0 turns seating off.

A Viewport whose target changes size re-mints its engine before drawing; a zero size skips the frame.

## Own your window

Extend `GameEngine`, open the window in `openTargets()`, return its canvas as a `canvas` target, and return your root from `mainScene()`.

## Tests

```bash
php -d memory_limit=128M vendor/bin/pest
```

Hardware-free: a fake output target, Velvet over native framebuffers, a real EventLoop over stream_select.
