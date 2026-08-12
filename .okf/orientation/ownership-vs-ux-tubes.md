---
type: Orientation
title: Ownership vs UX / tubes
description: tubes owns Canvas/FB/Renderer2D; UX owns Node/Drawable/Scene paint; game-engine owns Godot lifecycle, physics, signals, catalogs.
tags: [orientation, ownership, tubes, ux, framebuffer]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: scene-tree
    resource: src/SceneTree.php
    title: SceneTree extends UX Scene; binds Canvas + PhysicsServer
  - id: game
    resource: src/Game.php
    title: Game owns Canvas + Renderer2D + InputServer + SceneTree
  - id: control
    resource: src/Nodes/Control/Control.php
    title: Engine Control extends UX UIComponent
---

# Rule

| Layer | Owns |
|-------|------|
| tubes `Canvas` | Framebuffer + present (`OSWindow` / `FullColorPanel`) |
| tubes `Renderer2D` | Draw primitives (borrows FB via set/unset) |
| tubes `InputHandler` / `EngineInput` | Raw key/mouse pump from any `*-gfx` companion |
| ux `Scene` / `Node` / `Drawable` / `UIComponent` | Tree identity, layout rects, paint orchestration, widgets |
| game-engine `SceneTree` / `Game` | Godot enter/ready/process/physics/input clocks |
| game-engine nodes | Node2D / Control catalogs, signals, physics bounds |
| game-engine input | Action map + Godot-shaped `InputEvent*` over tubes events |

Engine types **extend** UX. Presentation type-hints tubes `Canvas` + `Renderer2D` only — never Metal/SDL brand classes.[^game]

```text
tubes Canvas/Renderer2D
        ↑ borrow paint
ux Scene / Drawable / UIComponent
        ↑ extend
game-engine SceneTree / Node2D / Control
```

# Related

- [Scene tree](../core/scene-tree.md)
- [Canvas targets](../core/canvas-targets.md)
- [Dependency direction](../conventions/dependency-direction.md)
- [Gfx-agnostic input](../conventions/gfx-agnostic-input.md)

[^scene-tree]: SceneTree extends UX Scene; binds Canvas + PhysicsServer
[^game]: Game owns Canvas + Renderer2D + InputServer + SceneTree
[^control]: Engine Control extends UX UIComponent
