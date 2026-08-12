---
okf_version: "0.2"
---

# scrapyard-io/game-engine Knowledge Bundle

Package knowledge for `scrapyard-io/game-engine` (Godot-inspired 2D + Control backend for ScrapyardIO, v0.7.0).
Read this index first; open only the concepts needed for the task.

**Trust rule:** Prefer `status: stable`. Treat `deprecated` as historical only. New agent-written concepts stay `status: draft` until a human verifies them.
**Placement:** This bundle lives at the **game-engine package root** only — never under `src/`.
**Links:** Concept cross-links use paths relative to each file.
**Scope:** Document what exists on disk in this 0.7 tree, plus durable architecture decisions marked `draft`.
**Dist note:** `.okf/` and root `AGENTS.md` are `export-ignore` in `.gitattributes`.

# Orientation

Section index: [orientation/](orientation/index.md)

* [Package (0.7)](orientation/package.md) - Composer identity, namespace, provider, stack role.
* [Ownership vs UX / tubes](orientation/ownership-vs-ux-tubes.md) - Who owns Canvas, Scene, lifecycle, physics, input.
* [Godot backend mapping](orientation/godot-backend-mapping.md) - Godot hooks → PHP engine methods and frame loop.

# Core

Section index: [core/](core/index.md)

* [Scene tree](core/scene-tree.md) - `Game` + `SceneTree` clocks, root enter/ready, paint/present.
* [Signals](core/signals.md) - `#[Signal]` + `HasSignals` connect/emit/disconnect.
* [Physics server](core/physics-server.md) - `PhysicsServer` / `PhysicsResolver`, bounds + bodies.
* [Input map](core/input-map.md) - `InputServer` / `InputMap` over tubes `EngineInput`.
* [Node2D catalog](core/node2d-catalog.md) - Living Godot 4.5 Node2D checklist vs `src/Nodes/Node2D`.
* [Control catalog](core/control-catalog.md) - Living Godot 4.5 Control checklist vs `src/Nodes/Control`.
* [Resources](core/resources.md) - Textures, shapes, tiles, `ResourceStore` SQLite.
* [Canvas targets](core/canvas-targets.md) - `OSWindow` + `FullColorPanel` via tubes profiles.

# Conventions

Section index: [conventions/](conventions/index.md)

* [Dependency direction](conventions/dependency-direction.md) - game-engine → ux → tubes → framework.
* [PHP enums](conventions/php-enums.md) - Backed enums, no class constants, `is_null`.
* [Gfx-agnostic input](conventions/gfx-agnostic-input.md) - No Metal/SDL brand imports in engine src.
