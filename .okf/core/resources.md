---
type: Core
title: Resources
description: Textures, sprite frames, Shape2D family, tiles/curves/nav, and SQLite ResourceStore.
tags: [core, resources]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: store
    resource: src/Resources/ResourceStore.php
    title: SQLite-backed resource cache
  - id: config
    resource: config/game-engine.php
    title: resource_store.database path
  - id: texture
    resource: src/Resources/Texture.php
    title: Texture / ImageTexture base
---

# Catalog (on disk)

| Resource | Path |
|----------|------|
| `Texture` / `ImageTexture` | `src/Resources/` |
| `SpriteFrames` | `src/Resources/SpriteFrames.php` |
| `Shape2D` + shapes | `src/Resources/Shapes/` (`Rectangle`, `Circle`, `Capsule`, `Segment`, `WorldBoundary`) |
| `TileSet` / `TileSetAtlasSource` | `src/Resources/` |
| `Curve2D` | `src/Resources/Curve2D.php` |
| `OccluderPolygon2D` | `src/Resources/OccluderPolygon2D.php` |
| `NavigationPolygon` | `src/Resources/NavigationPolygon.php` |
| `ResourceStore` | SQLite singleton via provider |

`GameEngineServiceProvider` binds `ResourceStore` to `config('game-engine.resource_store.database')` or `storage/game-engine/resources.sqlite`.[^config]

# Related

- [Node2D catalog](node2d-catalog.md)
- [Physics server](physics-server.md)

[^store]: SQLite-backed resource cache
[^config]: resource_store.database path
[^texture]: Texture / ImageTexture base
