---
type: Orientation
title: Godot backend mapping
description: Godot 4.x lifecycle and loop mapped onto ScrapyardIO Game / SceneTree / EngineNode.
tags: [orientation, godot, lifecycle, mapping]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: engine-node
    resource: src/Contracts/EngineNode.php
    title: Shared Godot-like lifecycle surface
  - id: game
    resource: src/Game.php
    title: Variable render delta + fixed physics TPS accumulator
  - id: scene-tree
    resource: src/SceneTree.php
    title: input → physics → process → paint → present
---

# Lifecycle hooks

| Godot | Engine |
|-------|--------|
| `_enter_tree` | `enterTree` / `enterTreeInternal` |
| `_ready` | `ready` |
| `_process` | `process` / `processInternal` |
| `_physics_process` | `physicsProcess` / `physicsProcessInternal` |
| `_input` | `input` / `inputInternal` |
| `_exit_tree` | `exitTree` / `exitTreeInternal` |
| `_draw` | `onDraw` (CanvasItem) / UX `draw` |

`SceneTree::setRoot` owns enter/ready order for `EngineNode` roots (bypasses UX mount when the root is an engine node).[^scene-tree]

`InteractsWithSceneTree::addChild` / `removeChild` / `clearChildren` mirror Godot: children added under an in-tree parent are entered automatically; removed children exit first. Apps should not call `enterTreeInternal` / `exitTreeInternal` on phase swaps.

# Frame loop

Godot-shaped clocks in `Game`:[^game]

```text
InputServer.pump(SceneTree)
  → fixed PhysicsServer steps (physics_tps accumulator)
  → process / FiberProcessScheduler (sync processInternal; no per-frame Fiber until something suspends)
  → SceneTree.drawFrame (Renderer2D + Scene::paint)
  → Canvas.present (+ OSWindow.pollEvents)
```

Config keys: `game-engine.target_fps`, `game-engine.physics_tps`, `game-engine.concurrency`.

# Related

- [Scene tree](../core/scene-tree.md)
- [Physics server](../core/physics-server.md)
- [Input map](../core/input-map.md)

[^engine-node]: Shared Godot-like lifecycle surface
[^game]: Variable render delta + fixed physics TPS accumulator
[^scene-tree]: input → physics → process → paint → present
