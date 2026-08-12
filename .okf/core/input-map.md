---
type: Core
title: Input map
description: InputServer + InputMap turn tubes EngineInput into Godot-like InputEvent* and actions.
tags: [core, input]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: server
    resource: src/Input/InputServer.php
    title: Pumps InputHandler / EngineInput into scene dispatch
  - id: map
    resource: src/Input/InputMap.php
    title: Action name → key/mouse matchers
  - id: events
    resource: src/Input/Events/InputEvent.php
    title: InputEvent / Key / MouseButton / Action
---

# Flow

```text
*-gfx companion registers InputHandler
        ↓
tubes EngineInput
        ↓
InputServer.pump(SceneTree, Game)
        ↓
InputMap action match → InputEventAction / Key / MouseButton
        ↓
SceneTree.dispatchInput → EngineNode.inputInternal
```

`InputMap` APIs: `addAction`, `actionAddKey`, `actionAddMouseButton`, `hasAction`, `getActions`.[^map]

# Rule

game-engine `src/` imports tubes `EngineInput` / `InputHandler` / `MouseButton` only — never Metal, SDL3, OGX, or other brand WindowHandler types.[^server]

# Related

- [Gfx-agnostic input](../conventions/gfx-agnostic-input.md)
- [Scene tree](scene-tree.md)
- [Canvas targets](canvas-targets.md)

[^server]: Pumps InputHandler / EngineInput into scene dispatch
[^map]: Action name → key/mouse matchers
[^events]: InputEvent / Key / MouseButton / Action
