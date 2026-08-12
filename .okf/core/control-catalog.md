---
type: Core
title: Control catalog
description: Living checklist of Godot 4.5 non-editor Control classes vs src/Nodes/Control.
tags: [core, catalog, control, godot-4.5]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: godot-control
    resource: https://docs.godotengine.org/en/4.5/classes/class_control.html
    title: Godot 4.5 Control Inherited By
  - id: src-dir
    resource: src/Nodes/Control/
    title: Filesystem scan of engine Control catalog
---

# Living checklist (Godot 4.5, non-editor)

Mark `[x]` when `src/Nodes/Control/<Name>.php` exists. Editor-only classes (`EditorProperty`, `EditorSpinSlider`, `EditorResourcePicker`, `EditorToaster`, `FileSystemDock`, `ScriptEditorBase`, `OpenXRInteractionProfileEditorBase`, …) are excluded. Re-scan after catalog changes.

## Base

- [x] `Control`

## Direct Control children

- [x] `BaseButton`
- [x] `ColorRect`
- [x] `Container`
- [x] `GraphEdit`
- [x] `ItemList`
- [x] `Label`
- [x] `LineEdit`
- [x] `MenuBar`
- [x] `NinePatchRect`
- [x] `Panel`
- [x] `Range`
- [x] `ReferenceRect`
- [x] `RichTextLabel`
- [x] `Separator`
- [x] `TabBar`
- [x] `TextEdit`
- [x] `TextureRect`
- [x] `Tree`
- [x] `VideoStreamPlayer`

## BaseButton branch

- [x] `Button`
- [x] `LinkButton`
- [x] `TextureButton`
- [x] `CheckBox`
- [x] `CheckButton`
- [x] `ColorPickerButton`
- [x] `MenuButton`
- [x] `OptionButton`

## Container branch

- [x] `AspectRatioContainer`
- [x] `BoxContainer`
- [x] `HBoxContainer`
- [x] `VBoxContainer`
- [x] `ColorPicker` *(ships as Control with paint; Godot extends `VBoxContainer`)*
- [x] `CenterContainer`
- [x] `FlowContainer`
- [x] `HFlowContainer`
- [x] `VFlowContainer`
- [x] `FoldableContainer`
- [x] `GraphElement`
- [x] `GraphFrame`
- [x] `GraphNode`
- [x] `GridContainer`
- [x] `MarginContainer`
- [x] `PanelContainer`
- [x] `ScrollContainer`
- [x] `SplitContainer`
- [x] `HSplitContainer`
- [x] `VSplitContainer`
- [x] `SubViewportContainer`
- [x] `TabContainer`

## Range branch

- [x] `ProgressBar`
- [x] `ScrollBar`
- [x] `HScrollBar`
- [x] `VScrollBar`
- [x] `Slider`
- [x] `HSlider`
- [x] `VSlider`
- [x] `SpinBox`
- [x] `TextureProgressBar`

## Separator / TextEdit branches

- [x] `HSeparator`
- [x] `VSeparator`
- [x] `CodeEdit`

# Gaps (Godot class, no PHP file yet)

None for the non-editor Control inventory targeted by Game Engine 0.7.

# Scrapyard extras (not Godot Control)

Files present under `src/Nodes/Control/` outside the Godot Control tree (kept for engine convenience):

- [x] `PopupMenu` — Godot places this under `Popup` / `Window`, not `Control`
- [x] `VirtualJoystick` — engine-only helper

# Related

- [Node2D catalog](node2d-catalog.md)
- [Ownership vs UX / tubes](../orientation/ownership-vs-ux-tubes.md)

[^godot-control]: Godot 4.5 Control Inherited By
[^src-dir]: Filesystem scan of engine Control catalog
