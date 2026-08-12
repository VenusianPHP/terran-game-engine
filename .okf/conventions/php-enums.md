---
type: Convention
title: PHP enums
description: Prefer backed PHP enums with FULLY_UPPERCASE cases; no class-level constants; prefer is_null.
tags: [convention, php, enums]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-12T02:45:00Z" }
status: draft
sources:
  - id: house
    resource: .
    title: ScrapyardIO house PHP style for packages
---

# Rules

1. **No PHP class-level constants** — put discrete values in a PHP Enum.
2. Enums are **backed** by `int` or `string`.
3. Enum **cases** are always `FULLY_UPPERCASE`.
4. Class names stay `PascalCase`.
5. Prefer `is_null($var)` over `$var === null`.

When adding engine modes (concurrency, body modes, blend modes, …), introduce a backed enum under an appropriate namespace rather than littering class constants.

# Related

- [Dependency direction](dependency-direction.md)
