# Log

## 2026-10-09

* Slice 2b: `Seats` over each frame's pad listing (hardware id key, reservations, namesake rule, takeover, lobby `assign()`/`release()`, player lights), per-seat `InputMap`/`PhysicsLatch` views, seat on pad and action events, `actions(?int $seat)`, config `seats` / `keyboard_seat` / `seat_on_connect`. [input](architecture/input.md).
* `terran.stager` (`TERRAN_STAGER`): the stager every staged target opens through unless its `options.toolkit` names one; null leaves Surface's default. [engine](architecture/engine.md).
* Slice 2a: fixed-step accumulator with `max_physics_steps`, hook passes and the `until()` guard, `World::async()`, Interrupts, Autoloads, Viewport re-mint on resize and `toPixels()`. [engine](architecture/engine.md).
* Slice 2a input: `InputReader`, events, `InputMap`, `PhysicsLatch`, `Scene::input()` / `actions()`. [input](architecture/input.md).

## 2026-10-08

* Slice 1: package scaffold; `GameEngine` (sketch `game`), `GameLoop` over `'event-loop'`, `World`, `Viewport`, `Scene` with five hooks, `TargetOpener` with `staged` / `canvas` / `display`, `config/terran.php`. [engine](architecture/engine.md).
* Staged windows join their toolkit session to the loop; `OwnedTarget::alive()` stops the engine on a closed window. [engine](architecture/engine.md).
