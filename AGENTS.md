# Agent guidelines — venusian/terran

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from the Composer dist via `.gitattributes` `export-ignore`). Before changing code or advising on this package: read [`.okf/index.md`](.okf/index.md) first, open only the concepts the task needs, prefer `status: stable` over `draft`. When you learn something durable, update the affected concept(s), bump `generated.at`, and append [`.okf/log.md`](.okf/log.md); new or changed concepts stay `status: draft` until a human verifies them. The bundle documents the package, never a session.

Do **not** create `.okf` folders under `src/*`.

## Where this package sits

`venusian-voyager/*` (loop, sketches, signals) + `venusian-surface/*` (drawing, framebuffers, windows) → **`venusian/terran`** → games and applications (`terran-tetris`, `terran-fps`, Weatherman's sketch).

## Package rules — 0.10.x

- Composer `venusian/terran` **0.10.0**, PHP `^8.4|^8.5`, namespace `Terran\` → `src/`.
- **Component splits only.** Never `venusian/framework` or `venusian/surface`, nothing from `jovian/*`. Those two repos are read-only from here; a need there is a failing test or a one-line ask.
- **Terran names no toolkit, stager, engine package or display.** Targets come from `config('terran.targets')` through `TargetOpener` kinds (`staged`, `canvas`, `display`), each through Surface's own manager; engines from `app('drawing')->renderer($engine, ['output' => $target])`.
- **`GameEngine extends Sketch`**, named `game` by attribute; the provider registers it on the sketch registry. `loop()` is the render phase. Subclasses override `openTargets()` / `mainScene()`.
- **`GameLoop extends EventLoop`**, re-bound over `'event-loop'` in the provider's `boot()`. The physics phase is a repeating timer; `turn()` is never overridden. `physicsProcess()` receives the constant step; wall time is `GameLoop::now()`.
- **Scene `remove()` is terminal.** The subtree exits and leaves the loop; it is not re-added.
- **Exceptions** descend from `Terran\TerranException`; messages say what was given and what is accepted.
- **One HumanInput read per frame**, in `InputReader::read()`. Nothing else in Terran reads `keyboard()`, `mouse()`, `gamePads()` or `gameControllers()`; a second reader takes the frame's edges. A device is read only when bindings or `terran.input.events` (with a live `input()` override) need it; HumanInput's exceptions pass through.
- **Seats reconcile inside `InputReader::read()`**, before events are built, from the same pad listing; nothing else calls `Seats::reconcile()`. A seat is keyed by `hardwareId() ?? id()`; seat numbers start at 1.
- **Physics:** `GameLoop::advance()` runs whole steps, at most `max_physics_steps`, dropping a longer stall. Hooks run inside `GameLoop::hooks()`; a wait there outside a loop fiber throws.
- **Interrupts and Autoloads** are attributes read up the class chain and cached per class.
- `is_null($x)` over `$x === null`; strict types everywhere; Pest v4.
- **Sheets are cut once.** `SpriteSheet` and `TileSet` hold one framebuffer per cell, cut at load; nothing draws a sub-rectangle (the engine's `image()` has none).
- **Primitives work inside their hooks** (`Button::input()`, `AnimatedSprite2D::process()`); subclasses and generated stubs call the parent's hook.
- **UIControls get input by hit test or focus only**; `World::deliver()` skips them in the depth-first walk.
- **Primitives register through `terran.primitives`**; a slug gives `make:scene:<slug>` when the console starts.

## Verification

```bash
php -d memory_limit=128M vendor/bin/pest
```

Hardware-free. The live check is `php rocket game` in an application with a stager installed.
