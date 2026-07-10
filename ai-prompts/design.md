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
