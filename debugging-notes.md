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

## Issue 3 — Reporter sees assignee when ticket is unassigned

### Problem
Reporters saw the "Assigned to" label on ticket detail when the field was empty.
FR-19 requires assignee to be omitted from all reporter output.

### How I Investigated
Manual review of unassigned own ticket as Reporter; `hook_node_view` used
`isset($build['field_assigned_to'])` which could miss empty-field render paths.

### How AI Helped
Recommended centralizing in `TicketAccessService::filterRenderedTicket()` and
calling from `hook_entity_view_alter` without value conditionals, per
design-notes.md.

### What I Validated
Kernel: `filterRenderedTicket()` removes assignee for reporter regardless of
assignment. Functional: no "Assigned to" text for unassigned and assigned tickets.

### Final Fix
Added `filterRenderedTicket()`; replaced `hook_node_view` with
`hook_entity_view_alter`; kept list column hiding in `hook_views_pre_render`.

## Issue 4 — Login lands on unscoped /node instead of /tickets

### Problem
After login, users landed on `/node` (Drupal default frontpage view), which lists
published content without `TicketAccessService` row scoping — a data exposure risk
for Agent/Reporter roles.

### How I Investigated
Manual login as Reporter; confirmed redirect to `/node` and visibility of tickets
outside role scope. Checked `system.site` config — `page.front` still `/node`.

### How AI Helped
Recommended setting `page.front` to `/tickets` via install/update hook **and**
redirecting direct `/node` hits via a request subscriber, since front page config
alone does not block bookmarked `/node` URLs.

### What I Validated
Functional: login lands on `/tickets`; `GET /node` redirects to `/tickets` and
does not show another reporter's ticket.

### Final Fix
`support_ticket.install` + `support_ticket_update_9001()` set front page;
`FrontpageRedirectSubscriber` redirects `/node` → `/tickets`.

