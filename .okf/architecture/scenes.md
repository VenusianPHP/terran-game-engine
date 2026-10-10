---
title: Scenes
status: draft
generated:
  at: 2026-10-10
---

# Scenes

Primitives = engine's built-in Scene types; games extend them. Namespaces `Terran\Scenes\{TwoD,UI,ThreeD}`; math `Terran\Math`; assets `Terran\Assets`.

Assets: `AssetCache` (`terran.assets`, World's sixth arg) decodes once per real path through Surface `images`, cuts `SpriteSheet` grid/regions into one RGBA8 framebuffer per cell (engine `image()` has no source rect; cut copies rows, alpha kept), resolves font slugs (unknown → lists faces). `SpriteFrames` named animations; `TileSet` id = cell; `Mesh` box/plane/OBJ (fan, negative indexes, normals computed when absent, bad index → file:line).

2D: `Scene2D` position/rotation/scale, `globalTransform()` from nearest Scene2D ancestors; `CanvasItem` trait: `zIndex` relative, `visible` hides subtree (`Scene::visibleInTree()` checks every ancestor's `hidden()`, any kind), `modulate` multiplied. `Sprite2D` texture xor sheet+frame, centred, flips in place. `AnimatedSprite2D` advances in `process()`, one `animationFinished` for one-shots, `play()` on a finished one-shot replays. `TileMap` culls to engine's visible area via inverse matrix. `Shape2D` rect/circle centred, polygon, line. `Label2D` aligned by its `textBounds()` box, origin at the box top edge.

Viewport frame: clear → 3D (none until slice 6) → 2D pass (Scene2D + plain Scenes visible in tree, by effective z then tree order, `view × global`) → UI pass (top-level controls laid out at viewport size, drawn by z from rect top-left) → present. `Camera2D` view = translate(centre) × zoom × translate(−(global pos + offset)). Cameras: `current` (set hook: true binds, false releases) + `viewport` name; bound on going live, on `current = true`/`makeCurrent()`, on `addViewport()`; `render()` throws naming viewports for a missing one; removal releases. `toWorld()` inverse view.

UI: anchors (fractions of nearest UIControl ancestor or viewport) + margins (px), `minSize`/content min, rect per viewport, recomputed each frame. `MouseFilter` STOP/PASS/IGNORE. `World::deliver()`: ui_next/ui_prev move focus; mouse events → controls under pointer topmost first (press focuses first focusable visited, captures every control visited; release → those first; STOP or true ends); others → focused control first (hidden focused control loses focus; `focus()` refuses hidden); then non-UI Scenes depth first. Hover via `mouseEntered()`/`mouseExited()`; wheel at last pointer. Removed control forgotten (focus, hover, capture). `Button` pressed on press+release inside or `ui_accept` focused; `ProgressBar` clamps; boxes stack at min sizes, `expand` shares rest.

3D: `Scene3D` local `Transform3D`, translate/rotate in parent space, `lookAt` global target. `Camera3D` perspective/orthographic `projection(aspect)`, `view()`. `MeshInstance`, `DirectionalLight` (global −Z).

Generators: `ScenePrimitives` (`terran.primitives`) slug → class (+stub); 18 Terran slugs; packages `callAfterResolving` + `register()`. `ScenePrimitiveMakeCommand` per slug + `make:scene`, added on `Computer::starting`; hooks call parent, no `draw()` on 3D, base aliased on name clash; `stubs/scene/<slug>.stub` overrides.
