# scrapyard-io/game-engine

Godot-inspired **2D scene-tree** backend for ScrapyardIO **0.7** — nodes, signals, fixed-timestep physics, gfx-agnostic input, sprites, and Control UI — built on `scrapyard-io/ux` plus the tubes/Fabricate components this package actually uses.

```bash
# From an app (path repo example):
composer require scrapyard-io/game-engine:@dev
php workshop package:discover
./runner game-engine-sprite-demo
```

### Dependencies

Direct runtime requires (no `scrapyard-io/framework` / `scrapyard-io/tubes` metapackages):

| Package | Why |
|---------|-----|
| `scrapyard-io/ux` | `Node` / `Drawable` / `UIComponent` / `Scene` seam |
| `tubes/canvas` `tubes/rendering` `tubes/windows` | paint / present surfaces |
| `tubes/inputs` `tubes/human-input` `tubes/contracts` | gfx-agnostic `InputHandler` / `EngineInput` |
| `fabricate/sketches` `fabricate/contracts` `fabricate/nuts-and-bolts` | sketch demo + service provider |

`scrapyard-io/ux` may still pull the tubes/framework metapackages until UX itself is narrowed.

### Official Documentation

Docs live on the [ScrapyardIO website](https://scrapyard-io.projectsaturnstudios.com/ecosystem/scrapyard-io/game-engine/0.7.x/overview) when published. Package knowledge for agents is in [`.okf/`](.okf/).

### License

MIT — see [LICENSE](LICENSE).
