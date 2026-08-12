---
type: Core
title: Signals
description: Godot-like #[Signal] declarations with HasSignals connect/emit/disconnect and exitTree cleanup.
tags: [core, signals]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: attr
    resource: src/Signals/Signal.php
    title: Repeatable class Attribute declaring name + arg map
  - id: trait
    resource: src/Signals/HasSignals.php
    title: connect / emit / disconnect + inbound WeakMap index
  - id: connection
    resource: src/Signals/SignalConnection.php
    title: Per-connection target + handler + oneshot
---

# Surface

```php
#[Signal('pressed')]
#[Signal('moved', ['x' => 'int', 'y' => 'int'])]
class Example extends Node2D
{
    use HasSignals;
}
```

| API | Behavior |
|-----|----------|
| `connect($signal, $target, $handler, $oneshot = false)` | Register outbound connection |
| `emit($signal, ...$args)` | Fan-out to connections; drop oneshots after fire |
| `disconnect($signal, $target, $handler = null)` | Remove matching connections |

Inbound connections are indexed in a `WeakMap` so teardown can disconnect reverse edges on `exitTree`.[^trait]

# Related

- [Scene tree](scene-tree.md)
- [Godot backend mapping](../orientation/godot-backend-mapping.md)

[^attr]: Repeatable class Attribute declaring name + arg map
[^trait]: connect / emit / disconnect + inbound WeakMap index
[^connection]: Per-connection target + handler + oneshot
