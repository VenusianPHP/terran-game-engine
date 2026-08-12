---
type: Core
title: Canvas targets
description: Engine presents through tubes OSWindow and FullColorPanel profiles — monochrome panels out of scope.
tags: [core, canvas, oswindow, panel]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: demo
    resource: src/Runner/Sketches/GameEngineSpriteDemo.php
    title: Window::profile → OSWindow open path
  - id: scene-tree
    resource: src/SceneTree.php
    title: drawFrame / present; pollEvents on OSWindow
  - id: ownership
    resource: orientation/ownership-vs-ux-tubes.md
    title: tubes owns Canvas + framebuffer
---

# Supported targets

| Target | Open path | Notes |
|--------|-----------|-------|
| `OSWindow` | `Window::profile($name)->open()` | Desktop / companion gfx windows |
| `FullColorPanel` | `Panel::profile($name)` | SPI / full-color panel IC |

`SceneTree::drawFrame` paints via borrowed `Renderer2D` into `$canvas->framebuffer()`, then `present()`. On `OSWindow`, `present` also `pollEvents()`.[^scene-tree]

# Out of scope

Monochrome / 1-bit panels are not a game-engine canvas target for this slice — keep those demos in tubes/UX.

# Related

- [Ownership vs UX / tubes](../orientation/ownership-vs-ux-tubes.md)
- [Scene tree](scene-tree.md)
- [Gfx-agnostic input](../conventions/gfx-agnostic-input.md)

[^demo]: Window::profile → OSWindow open path
[^scene-tree]: drawFrame / present; pollEvents on OSWindow
[^ownership]: tubes owns Canvas + framebuffer
