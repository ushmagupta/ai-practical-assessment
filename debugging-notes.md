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

### Follow-up — access hooks returned neutral on deny
`AccessResult::allowedIf(false)` is neutral in Drupal 10, so core `edit own ticket content`
still granted update on terminal tickets. Hooks now return explicit `forbidden()`.

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

### Follow-up — entity_view_alter signature
Drupal 10 passes three arguments to `hook_entity_view_alter`; a fourth `$view_mode`
parameter caused a 500 on ticket detail pages.

### Follow-up — assignee visible on edit form
`hook_form_node_ticket_form_alter` only runs on the add form (`node_ticket_form`).
The edit form uses `node_ticket_edit_form` with base `node_form`, so reporters
still saw "Assigned to" on `/node/{id}/edit`. Switched to `hook_form_node_form_alter`
with a ticket bundle check so add and edit both hide the field.

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

### Follow-up — explicit login redirect
Added `hook_form_user_login_form_alter` to redirect to `/tickets` when no
`destination` query parameter is set; functional test base sets `page.front` in setUp.

## Issue 5 — Front page still /node on existing install

### Problem
After manual review, `system.site:page.front` was still `/node`. Visiting `/` or
logging in showed the unscoped core node listing instead of `/tickets`.

### How I Investigated
Checked site config (`drush config:get system.site page.front`); value was `/node`
despite install hook code existing. Module had been enabled before the install
hook was added; update 9001 had not run.

### How AI Helped
Recommended an idempotent update hook (`9002`), `hook_enable()` for re-enables,
and extending `FrontpageRedirectSubscriber` to catch `/` when front is still
`/node` — belt-and-suspenders until config is applied.

### What I Validated
`drush updb` ran update 9002; `page.front` is `/tickets`. Kernel:
`ModuleEnableTest` still passes.

### Final Fix
`support_ticket_update_9002()` + `hook_enable()` call `support_ticket_set_front_page()`.
`FrontpageRedirectSubscriber` redirects `/` and `/node` to `/tickets` when needed.

## Issue 6 — Comments not visible on ticket detail

### Problem
Manual review on ticket detail (`/node/{id}`): no comment thread or add-comment
form, despite ui-flow.md requiring inline comments on the detail page.

### How I Investigated
Checked module config: comment field YAML was under `config/optional/` and the
ticket view display (`core.entity_view_display.node.ticket.default`) had no
`comment` component — only ticket metadata fields.

### How AI Helped
Recommended moving comment field config to `config/install/`, adding the
`comment_default` formatter to the view display, and
`support_ticket_ensure_ticket_comments()` for existing databases via update 9002.

### What I Validated
After `drush updb`, comment component present on ticket view display. Kernel:
`ModuleEnableTest` asserts `comment` field and display component exist.

### Final Fix
Moved `field.storage.node.comment` and `field.field.node.ticket.comment` to install;
added `comment` to view display YAML; `support_ticket_ensure_ticket_comments()` in
install file for existing sites.

