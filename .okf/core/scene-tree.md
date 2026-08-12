---
type: Core
title: Scene tree
description: Game + SceneTree drive input, fixed physics, process, paint, and present over a UX Scene.
tags: [core, scene, game, loop]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: game
    resource: src/Game.php
    title: Top-level Canvas + Renderer2D + SceneTree + InputServer
  - id: scene-tree
    resource: src/SceneTree.php
    title: Extends UX Scene; physics walk + drawFrame/present
  - id: demo
    resource: src/Runner/Sketches/GameEngineSpriteDemo.php
    title: Smoke demo sketch game-engine-sprite-demo
---

# Types

| Type | Role |
|------|------|
| `Game` | Owns `Canvas`, `Renderer2D`, `SceneTree`, `InputServer`, fiber scheduler |
| `SceneTree` | Extends UX `Scene`; engine root enter/ready; physics + paint |
| `EngineNode` | Contract for Godot lifecycle on Node / CanvasItem / Control |

`Game::tick($renderDelta)` is the cooperative single-frame entry (tests + sketches).[^game]

# Root rules

- Prefer an `EngineNode` root so enter/ready/process/physics/input run.
- Plain UX nodes still mount/paint via `Scene`, but skip engine physics/input internals.
- `bindCanvas($canvas)` is `Scene::attach` — framebuffer ownership stays with tubes.

# Demo

```bash
./runner game-engine-sprite-demo
```

Sketch opens a tubes `Window::profile`, builds `Sprite2D` + `CharacterBody2D` + UX Label HUD.[^demo]

# Related

- [Godot backend mapping](../orientation/godot-backend-mapping.md)
- [Physics server](physics-server.md)
- [Canvas targets](canvas-targets.md)

[^game]: Top-level Canvas + Renderer2D + SceneTree + InputServer
[^scene-tree]: Extends UX Scene; physics walk + drawFrame/present
[^demo]: Smoke demo sketch game-engine-sprite-demo
