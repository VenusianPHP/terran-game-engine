---
type: Orientation
title: Package (0.7)
description: scrapyard-io/game-engine 0.7.0 — Godot-inspired 2D + Control backend above UX + tubes.
resource: .
tags: [orientation, game-engine, scrapyard-io, 0.7]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: Package name, version, PHP, autoload, scrapyard-io providers
  - id: readme
    resource: README.md
    title: Package README + docs / OKF pointer
  - id: gitattributes
    resource: .gitattributes
    title: export-ignore list including /.okf
---

# What it is

Composer package `scrapyard-io/game-engine` at **0.7.0** — ScrapyardIO **Godot-inspired 2D scene-tree** backend for 0.7.[^composer]

| Field | Value |
|-------|-------|
| Name | `scrapyard-io/game-engine` |
| Version | `0.7.0` |
| PHP | `^8.4\|^8.5\|^8.6` |
| Namespace | `ScrapyardIO\GameEngine\` → `src/` |
| Discovery | `extra.scrapyard-io.providers` → `GameEngineServiceProvider` |
| Requires | `scrapyard-io/ux ^0.7`, `tubes/{canvas,contracts,human-input,inputs,rendering,windows} ^0.7`, `fabricate/{contracts,nuts-and-bolts,sketches} ^0.7` (not the framework/tubes metapackages) |

# What it is not

- Not a Godot runtime / GDScript host.
- Not the owner of framebuffers / Canvas / Window (tubes owns those).
- Not the general widget library (UX owns `UIComponent` widgets; engine `Control` extends that seam).
- Not slim framework core.

# Related

- [Ownership vs UX / tubes](ownership-vs-ux-tubes.md)
- [Godot backend mapping](godot-backend-mapping.md)
- [Dependency direction](../conventions/dependency-direction.md)

[^composer]: Package name, version, PHP, autoload, scrapyard-io providers
[^readme]: Package README + docs / OKF pointer
[^gitattributes]: export-ignore list including /.okf
