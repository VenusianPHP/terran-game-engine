# Directory Update Log

## 2026-08-11

* **Initialization**: Created OKF v0.2 bundle for `scrapyard-io/game-engine` 0.7 — orientation (package, ownership vs UX/tubes, Godot backend mapping), core (SceneTree/Game, signals, PhysicsServer, InputMap, Node2D + Control living catalogs scanned from `src/Nodes/Node2D` and `src/Nodes/Control`, resources, canvas targets), conventions (dependency direction, PHP enums, gfx-agnostic input). All new concepts `status: draft` pending human verification.
* **Catalogs**: Node2D checklist covers Godot 4.5 non-editor Node2D descendants (all present under `src/`); Control checklist covers Godot 4.5 non-editor Control descendants (including `ColorPicker`, `ColorPickerButton`, `GraphFrame`). Scrapyard-only extensions noted separately (`PhysicsBounds2D` / `PhysicsObject2D`, `PopupMenu` / `VirtualJoystick`).

* **Close-out**: Added `ColorPicker`, `ColorPickerButton`, `GraphFrame`; wired `PhysicsBounds2D` → `Area2D::monitorOverlaps` after fixed step; Pest for StaticBody2D/Area2D signals + BoxContainer layout; Control catalog gaps cleared.
* **CI / deps**: Dropped `scrapyard-io/framework` + `scrapyard-io/tubes` metapackage requires in favor of `fabricate/{contracts,nuts-and-bolts,sketches}` + `tubes/{canvas,contracts,human-input,inputs,rendering,windows}` + `scrapyard-io/ux`. Demo uses `WindowManager` (no tubes MagicAlias). Added GitHub Actions Pest matrix (PHP 8.4/8.5, `pdo_sqlite`).

