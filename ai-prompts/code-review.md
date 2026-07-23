# AI Prompts — code-review

## July 23, 2026 — M5 theme senior themer review

**Prompt:**
Review M5 `support_ticket_theme` as a senior Drupal themer against requirements
(`ui-flow.md`, `design-notes.md`) and Drupal theming standards.

**AI response summary:**
Reviewed theme preprocess, Twig overrides, CSS, and `TicketThemeFunctionalTest`.
M5 requirements are met: role/filter-aware empty list copy, terminal read-only
banner, Reporter assignee hiding (supplementary to module), empty comment copy,
and local task behavior (module-owned; theme styles tabs). Identified non-blocking
issues: Olivero CSS selector mismatches (`.tabs.primary`, `.ticket--terminal
.comment-form`), dead empty-state paths due to Views `empty_table: true`, terminal
status duplicated in theme preprocess, and minor render/cache standards gaps.
Only `testReporterEmptyListMessage` was run in session (~30s); full
`TicketThemeFunctionalTest` recommended before M6.

**Accepted:**
M5 deliverable is acceptable for milestone completion; architecture (presentation
only, module authoritative) is correct.

**Changed:**
None (review only).

**Rejected:**
None.

## July 23, 2026 — Post-review fixes (user pushback)

**Prompt:**
Implement review issues #2 (dead empty-state paths) and #4 (render arrays + cache
contexts). For #3 (domain logic in theme), pass `ticket_terminal` from module
preprocess — do not inject services into the theme. Do not collapse #5
(`hide_assignee` duplication) until rationale is documented. Deprioritize #1
(Olivero CSS) — cosmetic only. Log pushback in code-review files.

**AI response summary:**
Applied #2–#4: module `preprocess_node()` sets `ticket_terminal`; theme reads
that flag only; dead empty-state code removed; empty-list copy uses render arrays
with role cache context. Documented #5 (`hide_assignee`): node-level is
intentional; field-level is unused. #5 field-level removal and #1 CSS fixes
approved but not yet applied.

**Accepted:**
Module-owned terminal flag; dead code removal; render-array empty states; cache
contexts; explicit logging of pushback and deferred items.

**Changed:**
| Issue | Status |
|-------|--------|
| #3 | Done — module sets `ticket_terminal`; theme only reads it |
| #2 | Done — removed dead empty-state paths (`empty_table`) |
| #4 | Done — empty list uses render arrays; `user.roles` cache context |
| #5 | Pending — remove field-level `hide_assignee`; keep node-level |
| #1 | Pending — fix Olivero tab CSS (cosmetic) |

**Rejected:**
- Theme calling `TicketStatusService` directly — use module preprocess variables instead.
- Removing node-level `hide_assignee` — still needed for the custom node Twig layout.
