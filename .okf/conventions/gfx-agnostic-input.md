---
type: Convention
title: Gfx-agnostic input
description: Bind tubes InputHandler / EngineInput only — companions register handlers; engine stays brand-free.
tags: [convention, input, gfx]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: server
    resource: src/Input/InputServer.php
    title: Uses EngineInput + InputHandler + MouseButton enum only
---

# Rule

```text
metal-gfx / sdl3-gfx / ogx / …
        registers
tubes InputHandler → EngineInput
        consumed by
game-engine InputServer / InputMap
```

Allowed imports in game-engine input code: tubes contracts (`EngineInput`, `InputHandler`, `MouseButton`, …).

Forbidden in `src/`: concrete companion WindowHandler / brand namespaces for input or present.

Any `*-gfx` companion that registers an `InputHandler` works without engine code changes.[^server]

# Related

- [Input map](../core/input-map.md)
- [Dependency direction](dependency-direction.md)
- [Canvas targets](../core/canvas-targets.md)

[^server]: Uses EngineInput + InputHandler + MouseButton enum only
