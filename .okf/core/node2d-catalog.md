---
type: Core
title: Node2D catalog
description: Living checklist of Godot 4.5 non-editor Node2D classes vs src/Nodes/Node2D (and Node2D.php).
tags: [core, catalog, node2d, godot-4.5]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: godot-node2d
    resource: https://docs.godotengine.org/en/4.5/classes/class_node2d.html
    title: Godot 4.5 Node2D Inherited By
  - id: src-dir
    resource: src/Nodes/Node2D/
    title: Filesystem scan of engine Node2D catalog
  - id: node2d-base
    resource: src/Nodes/Node2D.php
    title: Base Node2D class (parent of catalog namespace)
---

# Living checklist (Godot 4.5, non-editor)

Mark `[x]` when a matching `.php` exists under `src/` (base at `src/Nodes/Node2D.php`; descendants under `src/Nodes/Node2D/`). Re-scan after catalog changes.

## Base

- [x] `Node2D` — `src/Nodes/Node2D.php`

## Direct / common descendants

- [x] `AnimatedSprite2D`
- [x] `AudioListener2D`
- [x] `AudioStreamPlayer2D`
- [x] `BackBufferCopy`
- [x] `Bone2D`
- [x] `Camera2D`
- [x] `CanvasGroup`
- [x] `CanvasModulate`
- [x] `CollisionObject2D`
- [x] `CollisionPolygon2D`
- [x] `CollisionShape2D`
- [x] `CPUParticles2D`
- [x] `GPUParticles2D`
- [x] `Joint2D`
- [x] `Light2D`
- [x] `LightOccluder2D`
- [x] `Line2D`
- [x] `Marker2D`
- [x] `MeshInstance2D`
- [x] `MultiMeshInstance2D`
- [x] `NavigationLink2D`
- [x] `NavigationObstacle2D`
- [x] `NavigationRegion2D`
- [x] `Parallax2D`
- [x] `ParallaxLayer`
- [x] `Path2D`
- [x] `PathFollow2D`
- [x] `Polygon2D`
- [x] `RayCast2D`
- [x] `RemoteTransform2D`
- [x] `ShapeCast2D`
- [x] `Skeleton2D`
- [x] `Sprite2D`
- [x] `TileMap`
- [x] `TileMapLayer`
- [x] `TouchScreenButton`
- [x] `VisibleOnScreenNotifier2D`

## CollisionObject2D / PhysicsBody2D branch

- [x] `Area2D`
- [x] `PhysicsBody2D`
- [x] `CharacterBody2D`
- [x] `RigidBody2D`
- [x] `StaticBody2D`
- [x] `AnimatableBody2D`

## Joint2D branch

- [x] `PinJoint2D`
- [x] `GrooveJoint2D`
- [x] `DampedSpringJoint2D`

## Light2D branch

- [x] `DirectionalLight2D`
- [x] `PointLight2D`

## VisibleOnScreenNotifier2D branch

- [x] `VisibleOnScreenEnabler2D`

# Scrapyard extensions (not Godot)

Present under `src/Nodes/Node2D/` but outside the Godot 4.5 Node2D tree — keep for physics packaging:

- [x] `PhysicsBounds2D` — world bounds + snapshot for `PhysicsServer`
- [x] `PhysicsObject2D` — shared physics-object base used by bounds export

# Generator

Idempotent stubs: `bin/generate-catalog.php` (Node2D portion).

# Related

- [Control catalog](control-catalog.md)
- [Physics server](physics-server.md)
- [Resources](resources.md)

[^godot-node2d]: Godot 4.5 Node2D Inherited By
[^src-dir]: Filesystem scan of engine Node2D catalog
[^node2d-base]: Base Node2D class (parent of catalog namespace)
