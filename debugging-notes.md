# Debugging Notes

## Issue 1 — Reporter missing create-ticket link on list page

### Problem
Reporters could create tickets (`create ticket content` permission) but had no
visible link or button on `/tickets` to reach `/node/add/ticket`, violating
ui-flow.md list screen spec.

### How I Investigated
Manual review of the ticket list as Reporter; confirmed menu only had "Tickets"
and the View config had an empty header (`header: { }`).

### How AI Helped
Suggested Drupal **local action** (`*.links.action.yml`) instead of a custom
Views area plugin — same pattern as existing `links.menu.yml` / `links.task.yml`,
with permission delegated to the core `node.add` route.

### What I Validated
Functional test: Reporter on `/tickets` sees "Create ticket" linking to
`/node/add/ticket`.

### Final Fix
Added `support_ticket.links.action.yml` with `appears_on: view.tickets.page_1`.

## Issue 2 — Reporter can edit cancelled terminal tickets

### Problem
Reporters saw an Edit tab on cancelled (and closed) tickets and could reach the
edit form, despite terminal read-only rules in data-model.md.

### How I Investigated
Manual review on a cancelled own ticket as Reporter; confirmed Edit tab visible.
Kernel tests showed `canUpdate()` already returned FALSE for closed tickets.

### How AI Helped
Identified that `hook_menu_local_tasks_alter` only hid "Change status", not
"Edit", when transition was denied — inverted logic left Edit visible on
terminal tickets. Proposed gating both tabs on `canUpdate()`.

### What I Validated
Kernel: reporter `canUpdate()` FALSE for cancelled. Functional: 403 on edit
route and no Edit link on detail for closed and cancelled.

### Final Fix
Refactored `hook_menu_local_tasks_alter` to unset Edit and transition tabs when
`!canUpdate()`; transition tab alone when update allowed but transition denied.

## Issue 3
