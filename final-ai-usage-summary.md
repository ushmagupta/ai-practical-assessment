# Final AI Usage Summary

## Overview

Cursor was used throughout the Support Ticket Management System assessment (July 6–23, 2026) as the primary AI pair-programming tool. Work followed a milestone plan (M1a scaffold → M6 ship-ready) with human review at each stage. All significant prompts and outcomes are logged under `ai-prompts/` (`planning.md`, `design.md`, `implementation.md`, `debugging.md`, `code-review.md`).

## How AI was used

| Phase | AI role | Human role |
|-------|---------|------------|
| Requirements & architecture | Drafted and updated analysis docs; clarified edge cases | Chose Drupal-only monolith; approved assumptions |
| Design | data-model, ui-flow, test-strategy, design-notes | Reviewed and iterated (e.g. assignment rules, type field) |
| Implementation | Generated module/theme code, config-as-code, services, hooks | Reviewed diffs; manual testing; rejected over-engineering |
| Debugging | Diagnosed 7 manual-review issues (see `debugging-notes.md`) | Reproduced in browser/Lando; validated fixes |
| Code review | Senior review of M1–M5; applied hardening items 1–8 | Deferred item 9 (hook → OOP refactor) |
| Testing | Kernel + smoke tests; suite trim for CI runtime | Set 10-minute budget; accepted P3 deferrals |
| Ship-ready | Acceptance walkthrough, submission docs | Final manual login/logout smoke |

## What worked well

- **Structured logging** (`ai-prompts/*`) kept context across sessions and supported assessment evidence.
- **Milestone sequencing** — config and Kernel tests before HTTP wiring reduced rework.
- **Manual review + AI debug loop** — issues like terminal Edit tab, assignee leakage, and anonymous 403 were found by human review and fixed with targeted AI diagnosis.
- **Smoke suite** — replacing 50+ browser tests with 8 merged flows cut runtime from ~45+ min to ~6 min while Kernel retained domain coverage.

## What was rejected or deferred

- REST/JSON:API and OpenAPI (architecture descope).
- Full Functional matrix for list filters/sort/pagination (P3; View config still shipped).
- Refactoring all `.module` hooks into event subscribers (code review point 9).
- `testing` install profile for browser tests (broke routes; reverted to `standard` + smoke trim).

## Artifacts

- **Code:** `web/modules/custom/support_ticket/`, `web/themes/custom/support_ticket_theme/`
- **Tests:** 30 Kernel + 8 smoke (`test-results.md`)
- **CI:** `.github/workflows/ci.yml`
- **Prompt history:** `ai-prompts/`
- **Debug journal:** `debugging-notes.md`

## Closing note

AI accelerated scaffolding, repetitive Drupal config, and test authoring. Human judgment remained authoritative for architecture, security-sensitive access rules, manual acceptance checks, and deciding what not to automate.
