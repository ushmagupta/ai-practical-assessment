# AI Prompts — design

## July 10, 2026 — Approved data-model.md

**Prompt:**
Started the design phase from the Support Ticket Design Plan (architecture decided,
four design docs in order). Assigned the `data-model` to-do: write `data-model.md`
first.

**AI response summary:**
Drafted `data-model.md`: User/Ticket/Comment mapped to Drupal core (`node` bundle
`ticket`, core comments). Documented fields, enums, relationships, validation, and
status transitions per requirements-analysis.md.

**Accepted:**
`data-model.md` approved — baseline for remaining design docs and implementation.

**Changed:**
Reviewed the draft and asked Cursor to correct colliding field names
(`field_type`, `field_status`) that clash with Node base fields. Approved the
revised draft.
Renamed `field_type` → `field_ticket_type` and `field_status` →
`field_ticket_status` throughout `data-model.md` and `requirements-analysis.md`.

**Rejected:**
None.

## July 10, 2026 — Approved design-notes.md

**Prompt:**
Assigned the `design-notes` to-do from the Support Ticket Design Plan: write
`design-notes.md` (architecture + config-as-code + services). Restructured headers
to match project template (Architecture Overview, Frontend/Backend/Database Design,
Validation, Error Handling, Testing Strategy Link). Requested critical review to
strip implementation-specific detail (config file names, method signatures, drush
commands) and remove duplication with `data-model.md`.

**AI response summary:**
Drafted `design-notes.md` covering layer split, technology choices, domain services
(`TicketStatusService`, `TicketAccessService`), config-as-code decision, validation
layers, and error-handling strategy. Restructured to requested section headers.
After review, condensed from ~411 to ~169 lines: kept architectural decisions and
rationale; replaced duplicated tables and file inventories with pointers to
`data-model.md`, `ui-flow.md`, and `test-strategy.md`.

**Accepted:**
Condensed `design-notes.md` approved — architectural handoff doc for implementation
phase.

**Changed:**
Initial draft was too implementation-specific; revised after architect-style review
before approval.

**Rejected:**
None.

## July 10, 2026 — Approved ui-flow.md

**Prompt:**
Assigned the `ui-flow` to-do from the Support Ticket Design Plan: write
`ui-flow.md` (routes and role-based screens). Requested critical architect review
to remove guessed Drupal route machine names, duplication with `data-model.md` and
`design-notes.md`, repeated role×field matrices, and config-as-code file listings;
consolidate two navigation diagrams into one. Show diff and summary before approval.

**AI response summary:**
Drafted `ui-flow.md` covering entry/auth, route map, screens, navigation,
empty/loading states, and flow diagram. After review, condensed from ~363 to ~157
lines: paths and screen-specific UI nuance only; pointers to data-model for field
rules and design-notes for errors/services; single navigation mermaid diagram;
removed route names, HTTP columns, UI visibility summary, and config artifact table.

**Accepted:**
Condensed `ui-flow.md` approved — screen-level handoff doc for implementation
phase.

**Changed:**
Initial draft was too implementation-specific and repetitive; revised after
architect-style review before approval.

**Rejected:**
None.

## July 10, 2026 — Created test-strategy.md

**Prompt:**
Read requirements-analysis.md, acceptance-criteria.md, data-model.md,
design-notes.md, and ui-flow.md in full as sole source of truth. Define test
strategy (what "correct" means) before implementation-plan.md. Fill
test-strategy.md keeping existing template headers; adapt API/Integration section
for Drupal-only no-API architecture; trace every target to documented rules;
explicitly state out-of-scope items under limited budget. Show full content
before approval.

**AI response summary:**
Drafted test-strategy.md (~280 lines): Kernel + Functional layers per FR-53 and
design-notes; adapted "API / Integration Tests" to workflow integration (state
machine via forms/services); P0–P3 priority tiers; Kernel coverage for
TicketStatusService, TicketAccessService, and six constraints; Functional
scenarios for role flows, listing, terminal read-only, and errors; EC-1–EC-16
matrix; explicit not-covered table (REST, comment delete, Docker, combinatorial
filters). Approved without changes.

**Accepted:**
`test-strategy.md` approved — completes design-phase doc set; next step is
`implementation-plan.md`.

**Changed:**
None.

**Rejected:**
None.
