---
okf_version: "0.2"
---

# venusian/terran

# Architecture

* [Engine](architecture/engine.md) - GameEngine the sketch, GameLoop the loop with a physics phase, World/Viewport/Scene, TargetOpener kinds, config.
* [Input](architecture/input.md) - InputReader as HumanInput's frame owner, device scoping, event order, InputMap bindings and the physics latch, pointer mapping.
* [Scenes](architecture/scenes.md) - 2D, UI and 3D primitives, assets, Viewport passes and cameras, the UI-first input walk, make:scene:<slug>.
