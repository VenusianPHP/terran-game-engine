---
title: Input
status: draft
generated:
  at: 2026-10-09
---

# Input

Terran is HumanInput's frame owner. HumanInput's `InputFrame` ends a frame on reads: first device read in a frame runs catch-up; next poll after a read clears edges. Second reader in one frame → finds edges gone. So `InputReader::read($mail, $world)` is the only reader, once per frame from `GameEngine::loop()`, before `World::tick()`. Nothing else in Terran calls `keyboard()`, `mouse()`, `gamePads()`, `gameControllers()`. Scenes read `actions()` and `input()`, never HumanInput.

Device scoping (`devicesFor()`): `InputMap::devices()` (bindings: `key:` → keyboard, `mouse:` → mouse, `pad:`/`axis:` → pads), plus `terran.input.events` (`keyboard`, `mouse`, `pads`; default first two) while `World::wantsInput()`. Unused device never read → pad-only game never touches the keyboard, so a session with no input engine does not stop it. A read that throws (`HumanInputException`) passes through unchanged out of `loop()`. Unknown `events` entry → `TerranException` at construction.

Events (`Terran\Input\Events\*`, `readonly`, `NamedSignal` `terran.input.<kind>`), in this order:

| Source | Event | Name |
|---|---|---|
| `GamepadConnected` / `GamepadDisconnected` mail | `PadConnected(pad, name, seat)` / `PadDisconnected(pad, ?name, seat)` | `pad.connected` / `pad.disconnected` |
| `pressedKeys()` / `releasedKeys()` | `KeyPressed` / `KeyReleased(key, modifiers)` | `key.pressed` / `key.released` |
| `text()` non-empty | `TextTyped(text)` | `text` |
| mouse button edges per `MouseButton` | `MouseButtonPressed` / `Released(button, viewport, x, y)` | `mouse.pressed` / `mouse.released` |
| `motion()` non-zero | `MouseMoved(viewport, x, y, dx, dy)` | `mouse.moved` |
| `wheel()` non-zero | `MouseWheel(viewport, dx, dy)`, lines, `dy > 0` away from user | `mouse.wheel` |
| pad button edges per supported `GamepadButton` | `PadButtonPressed` / `Released(pad, button, seat)` | `pad.pressed` / `pad.released` |
| `InputMap` edges | `ActionPressed` / `ActionReleased(action, seat)` | `action.pressed` / `action.released` |

Pointer: `viewport` = the Viewport whose window name equals `Mouse::window()`, else null. `x`, `y` = `Viewport::toPixels()`: staged window points × `pixelSize / size` (Retina crosshair sits under the pointer); other targets one to one; no viewport → window coordinates. Canvas targets carry no window name, so no viewport. `MouseMoved` `dx`, `dy` scale by the same `Viewport::pixelsPerPoint()`, so motion and position share units.

`InputMap(actions)`: bindings parsed by `Binding::parse()` at construction; wrong form/value/dead zone (`0 ≤ dz < 1`) → `TerranException` listing accepted forms or enum values. Unknown action on read → lists actions. `capture(keyboard?, mouse?, pads)` once per frame: button edges straight from HumanInput's edges (tap inside one frame → pressed and released together); axis: value × sign past dead zone → strength rescaled `(v − dz) / (1 − dz)`, press on crossing out, release on falling back; pad and axis bindings match any pad. Action `isDown` = any binding held; `pressed` only when the action was not held last capture (a second binding going down under a held action is no new press); `released` only when no binding still held — presses and releases alternate; `strength` = strongest binding. `axis(neg, pos) = strength(pos) − strength(neg)`.

`PhysicsLatch`: each capture ORs its pressed/released into the latch; `World` registers `clear()` as a `GameLoop::afterStep()` callback → a tap reaches exactly one physics step; with no step between frames, edges carry until one runs. `isDown`/`strength` are the snapshot. `Scene::actions()` = latch while `GameLoop::inPhysics()`, map elsewhere.

`World::tick()`: each event walks the tree depth first until `input()` returns true, then `signals->dispatch()` whether handled or not. UIControls-first ordering arrives with UIControl (slice 3).

## Seats

`Seats(max, keyboard_seat, seat_on_connect)`, bound as `terran.seats` (alias `Seats::class`) from `terran.input.seats` (default 4, `TERRAN_SEATS`), `keyboard_seat` (1), `seat_on_connect` (true). `InputReader::read()` calls `reconcile($pads)` with the frame's pad listing (pads then controllers) before building events, so every event carries the seat it has now. Pads are not read → no reconcile that frame.

- Key = `hardwareId() ?? id()`. Same physical pad keeps its seat whatever id it returns under.
- Pad leaves → seat reserved for its key. Back (absent last listing, or same key under a new id) → re-lit, reported seated.
- New pad (`seat_on_connect`): first a seat reserved for an absent pad with no hardware id and the same name (namesake rule: two such pads cannot be told apart; the Mac Xbox pad has no SDL hardware id and comes back under a new id). Then lowest seat neither held nor reserved; then lowest reservation of an absent pad (reservation ends); else unseated.
- Every seat taken → `setPlayerIndex(seat - 1)`; a present pad losing its seat → `setPlayerIndex(null)`.
- `seat_on_connect` false: nothing seats itself; the game calls `assign(seat, pad_id)` (moves the pad, takes the seat from any holder or reservation, holder goes dark) and `release(seat)`. Unconnected pad → `TerranException` listing connected ids.
- `seats` 0: seating off, every seat null, `check()` throws for any seat. Out of range → `TerranException` naming `terran.input.seats`.
- Reads: `seatOf(pad_id)` (by any id ever listed, held or reserved), `padIn(seat)` / `pad(seat)` (present pad only), `all()` (seat → present id, by seat), `max()`, `keyboardSeat()` (null when off).
- `PadDisconnected` carries only HumanInput's `id`; `InputReader` keeps an id → name map from listings and connect mail for its `name`.

`InputMap` keeps three views: any (every source; `actions()` with no seat), one per seat (its pad, plus keyboard and mouse on `keyboard_seat`), and unseated (pads with no seat; keyboard/mouse when `keyboard_seat` is null). Each press lands in exactly one seat view or unseated. `edges()` → `{action, seat, kind}`: seats ascending then null, pressed before released; `ActionPressed`/`Released` once per entry. `seat(n)` → that seat's `Actions`; `PhysicsLatch::seat(n)` → `LatchView` with per-seat latching, same one-step rule. `World::actions(?int $seat)` / `Scene::actions(?int $seat)`: latch view in physics, map view elsewhere.
