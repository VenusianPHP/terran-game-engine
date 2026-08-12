---
type: Core
title: Physics server
description: PhysicsServer submits plain-array snapshots to PhysicsResolver (parallel on ZTS, sync otherwise).
tags: [core, physics]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: server
    resource: src/Physics/PhysicsServer.php
    title: submitStep — parallel\\Runtime when ZTS+parallel
  - id: resolver
    resource: src/Physics/PhysicsResolver.php
    title: Deterministic step over snapshot arrays
  - id: bounds
    resource: src/Nodes/Node2D/PhysicsBounds2D.php
    title: Scrapyard world bounds + body export/apply
---

# Contract

Callers hand `PhysicsServer::submitStep($bounds_snapshot, $delta)` a **plain array** snapshot (no objects/closures) and receive a plain array (or `parallel\Future` on ZTS+parallel).[^server]

`SceneTree::physicsStep` walks the tree for `PhysicsBounds2D` and submits each bounds node, then runs `physicsProcessInternal` on the engine root.

# Body types (Godot-shaped)

Under a `PhysicsBounds2D` (Scrapyard extension):

| Class | Role |
|-------|------|
| `CharacterBody2D` | Kinematic / player-style motion |
| `StaticBody2D` | Immovable collider |
| `AnimatableBody2D` | Moving static (teleport-safe sibling of Static) |
| `RigidBody2D` | Dynamic body |
| `Area2D` | Overlap / trigger volume |
| `PhysicsObject2D` | Scrapyard shared physics-object base |

Shapes live under `Resources/Shapes/*` (`RectangleShape2D`, `CircleShape2D`, …).

# Related

- [Scene tree](scene-tree.md)
- [Node2D catalog](node2d-catalog.md)
- [Resources](resources.md)

[^server]: submitStep — parallel\\Runtime when ZTS+parallel
[^resolver]: Deterministic step over snapshot arrays
[^bounds]: Scrapyard world bounds + body export/apply
