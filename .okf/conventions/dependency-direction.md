---
type: Convention
title: Dependency direction
description: game-engine → ux + specific tubes/* + fabricate/*; never import concrete gfx WindowHandler brands into engine src.
tags: [convention, dependency]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T03:05:00Z" }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: game-engine requires ux + tubes/* + fabricate/* ^0.7
---

# Direction

```text
scrapyard-io/game-engine
  ├─ scrapyard-io/ux
  ├─ tubes/{canvas,rendering,windows,inputs,human-input,contracts}
  └─ fabricate/{sketches,contracts,nuts-and-bolts}
```

Do **not** require the `scrapyard-io/framework` or `scrapyard-io/tubes` metapackages from this package. Declare the Fabricate/Tubes components used by `src/` (waveforms-style). UX may still transitively pull metapackages until UX is narrowed.

| Must | Must not |
|------|----------|
| Depend on UX `Node` / `Drawable` / `UIComponent` / `Scene` | Depend on Metal/SDL/OGX WindowHandler classes from engine `src/` |
| Type-hint tubes `Canvas` / `Renderer2D` / `EngineInput` / `WindowManager` | Put framebuffers back into framework core |
| Keep widgets in UX where possible | Make tubes depend on game-engine or ux |
| Prefer `tubes/*` + `fabricate/*` over scrapyard-io metapackages | Require full `scrapyard-io/framework` or `scrapyard-io/tubes` |

Engine extends the UX seam; UX paints through tubes; tubes owns present.[^composer]

# Related

- [Ownership vs UX / tubes](../orientation/ownership-vs-ux-tubes.md)
- [Gfx-agnostic input](gfx-agnostic-input.md)

[^composer]: See `composer.json` require block.
