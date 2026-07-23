# Code Review Notes

## Summary

**Date:** July 23, 2026  
**Scope:** M5 theme (`support_ticket_theme`)  
**Verdict:** M5 requirements met. Review fixes #2–#4 applied; #1 and #5 pending.

## Review findings (original)

| # | Issue | Status |
|---|--------|--------|
| 1 | Olivero CSS selectors (`.tabs.primary`, comment-form) | Pending — cosmetic |
| 2 | Dead empty-state code with `empty_table: true` | Done |
| 3 | Terminal status hardcoded in theme | Done — module passes `ticket_terminal` |
| 4 | `#markup` + HTML in `t()`; missing cache context | Done |
| 5 | Duplicate `hide_assignee` in node + field preprocess | Pending — remove field-level only |

## Changes made after review

- **#3** — `support_ticket_preprocess_node()` sets `ticket_terminal` and read-only message; theme/Twig only consume those variables.
- **#2** — Removed unused `preprocess_views_view` empty handler and `views-view--tickets--page-1.html.twig`.
- **#4** — Empty ticket list uses link render arrays; role-based preprocess includes `user.roles` cache context.

## Pushed back

- **#5** — Remove unused `hide_assignee` block from `preprocess_field` (keep node-level for `node--ticket--full.html.twig`).
- **#1** — Update CSS to `.tabs.tabs--primary`; fix or drop broken terminal comment selectors.

## `hide_assignee` (issue #5)

- **Module** `filterRenderedTicket()` — authoritative; strips assignee for Reporters.
- **Theme** `preprocess_node` — intentional; supports conditional assignee output in node Twig.
- **Theme** `preprocess_field` — not used by any field template; safe to remove.

## Suggestions rejected

| Suggestion | Why |
|------------|-----|
| Theme calls `TicketStatusService` | Terminal flag belongs in module preprocess |
| Remove node-level `hide_assignee` | Required for current node template pattern |
| Rush #1 before #3/#5 | User deprioritized cosmetic CSS |
