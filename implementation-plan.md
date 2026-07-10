# Implementation Plan

**Status:** Draft — July 10, 2026.

Build-and-verify schedule for the Support Ticket System. Design and test scope: [`data-model.md`](data-model.md), [`design-notes.md`](design-notes.md), [`ui-flow.md`](ui-flow.md), [`test-strategy.md`](test-strategy.md).

**Philosophy:** Config-as-code first, then domain services, then thin Drupal integration (hooks, forms, View alter, theme). Every build item is paired with verification from [`test-strategy.md`](test-strategy.md) — tests are not a separate late phase.

**Logging:** Planning artifacts (this document) are logged in `ai-prompts/planning.md`. Code and test work described here is logged in `ai-prompts/implementation.md`.

**Out of scope:** REST/JSON:API, OpenAPI, comment deletion, auto-assignment, custom breadcrumbs (core defaults suffice). Manual Admin bootstrap is README-only.

---

## Overview

Drupal 10 monolith under `src/`: `support_ticket` module, `support_ticket_theme`, config-as-code install, `TicketStatusService` + `TicketAccessService`, server-rendered screens per ui-flow. Kernel tests prove services and constraints; Functional tests prove role flows, listing, and terminal read-only behavior. CI runs PHPUnit on push/PR once Kernel tests exist.

## Task Breakdown

Grouped checklist — each area built and verified per test-strategy (Kernel, Functional, or smoke as noted there).

### Scaffold & environment
- [ ] Docker + Drupal 10 app skeleton
- [ ] Module and theme skeletons
- [ ] CI workflow running PHPUnit
- [ ] README: local run, secrets handling, Admin bootstrap

### Config-as-code
- [ ] Ticket bundle, fields, roles, permissions
- [ ] Comment type on tickets
- [ ] Tickets View at `/tickets` (columns, filters, sort, pagination)
- [ ] Menu links (Tickets, People)

### Domain services & constraints
- [ ] `TicketStatusService` (transitions, terminal states, role scope, stale-status check) → Kernel
- [ ] `TicketAccessService` (view/update/delete/assign/comment, queue scope) → Kernel
- [ ] Six validation constraints + user delete guards → Kernel

### Access & forms integration
- [ ] Node access hooks and route callbacks → Kernel + Functional
- [ ] Views query alter (list scoping) → Functional
- [ ] Transition form (sole status-change path) → Kernel + Functional
- [ ] Node form alters (role field matrix, status off edit form) → Functional
- [ ] Status re-check on write forms → Functional
- [ ] Anonymous redirect on protected routes → Functional

### Feature flows
- [ ] Ticket create/edit/delete and create defaults
- [ ] Assignment rules (manual only, Agent-only assignee, self-assign, Reporter denial)
- [ ] State machine via transition form
- [ ] Terminal ticket read-only behavior
- [ ] Comments (add, edit own, no delete)
- [ ] User management and delete blocking
- [ ] Ticket list: search, filters, sort, pagination, role visibility
- [ ] Error presentation (inline validation, access denied)

### Theme & presentation
- [ ] Twig overrides (assignee hidden for Reporter, terminal read-only)
- [ ] Local tasks and navigation per ui-flow
- [ ] Empty-state messages per ui-flow (themed copy for empty lists and comment threads)

### Ship-ready
- [ ] Full Kernel + Functional suite green in CI
- [ ] Acceptance-criteria walkthrough; deferred items noted in `test-results.md`
- [ ] Login/logout smoke test

## Milestones

| # | Goal | Done when |
|---|------|-----------|
| **M1** | Runnable scaffold with data model installed | Docker/README work; module enable installs config; `/tickets` loads |
| **M2** | Domain rules correct in isolation | All P0 Kernel tests pass (services, constraints, delete guards) |
| **M3** | Drupal wired to domain rules | Access hooks, transition form, form alters, View alter in place; P0 Functional paths pass |
| **M4** | End-to-end ticket, comment, user, and list flows | P1 + representative P2 Functional tests pass |
| **M5** | Screens match ui-flow presentation | Theme, local tasks, empty-state copy in place; Reporter assignee absent from output |
| **M6** | Submission-ready | CI green; acceptance-criteria Core/Validation/Error Handling covered per test-strategy tiers |

## AI Usage Plan

Use Cursor per task: prompt with the relevant design doc section and test-strategy target, scaffold a minimal diff, review against data-model and test-strategy, fix failures, commit when asked, then log the session. Attach `project-context.md` and the doc for the current milestone. Human reviews config install order, access edge cases, and final acceptance walkthrough.

## Risks

- Config install dependency order breaks clean module enable.
- `field_ticket_status` confused with node publish `status`.
- View list scoping diverges from detail-page access rules.
- Status changed via edit form instead of transition form.
- Time pressure leads to skipped P2 tests without documenting deferrals.

## Mitigation

- Enable module on clean DB in CI after each config change.
- Services and constraints use only `field_ticket_status`; add Kernel assertion on publish flag.
- Both list alter and node access delegate to `TicketAccessService`; test list and detail per role.
- Omit status from edit form; Functional test confirms transition form is the only path.
- Track test-strategy P0→P3 explicitly; record skips in `test-results.md`.
