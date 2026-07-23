# AI Prompts — debugging

## July 22, 2026 — Reporter missing create-ticket link on list page

**Context:** Found during manual review during implementation.

**Prompt:** Fix issue #1 — Reporter has no create-ticket link/button on the ticket
list page. Add one per ui-flow.md using Drupal local action pattern.

**AI response summary:** Added `support_ticket.links.action.yml` declaring a local
action on `view.tickets.page_1` that links to `node.add` with `node_type: ticket`.
Permission is enforced by core route access (`create ticket content`). Added
`TicketCreateLinkFunctionalTest` to assert the link appears for reporters.

**Accepted:** Local action YAML approach (idiomatic Drupal, no custom Views plugin).

**Changed:**
`web/modules/custom/support_ticket/support_ticket.links.action.yml`,
`web/modules/custom/support_ticket/tests/src/Functional/TicketCreateLinkFunctionalTest.php`,
`web/modules/custom/support_ticket/tests/src/Functional/SupportTicketFunctionalTestBase.php`.

**Rejected:** Custom Views area plugin and static header HTML (unnecessary complexity).

## July 22, 2026 — Reporter can edit cancelled terminal tickets

**Context:** Found during manual review during implementation.

**Prompt:** Fix issue #2 — Reporter can edit a Cancelled ticket. Enforce terminal
read-only for Cancelled and Closed; do not change Resolved editability.

**AI response summary:** Server-side `canUpdate()` already denied terminal writes;
the Edit local task was still shown because `hook_menu_local_tasks_alter` only
hid the transition tab. Refactored local tasks to unset Edit and Change status
when `!canUpdate()`, and to hide transition alone when update is allowed but
transition is not. Added kernel test for cancelled reporter update denial and
functional tests for 403 + absent Edit tab on closed/cancelled tickets.

**Accepted:** Local task hiding driven by `TicketAccessService::canUpdate()`.

**Changed:**
`web/modules/custom/support_ticket/support_ticket.module`,
`web/modules/custom/support_ticket/tests/src/Kernel/TicketAccessServiceTest.php`,
`web/modules/custom/support_ticket/tests/src/Functional/TicketTerminalEditFunctionalTest.php`.

**Rejected:** Changing Resolved terminal semantics (explicitly out of scope).

## July 22, 2026 — hook_node_access returned neutral instead of forbidden

**Context:** Follow-up while verifying issue #2.

**Prompt:** Terminal edit still allowed despite canUpdate() returning false.

**AI response summary:** `AccessResult::allowedIf(false)` is neutral in Drupal 10, so
core `edit own ticket content` still granted update. Hooks now return explicit
`allowed()` / `forbidden()`.

**Accepted:** Explicit forbidden on deny for ticket access hooks.

**Changed:** `web/modules/custom/support_ticket/support_ticket.module`,
`web/modules/custom/support_ticket/tests/src/Kernel/TicketAccessServiceTest.php`.

## July 22, 2026 — Reporter sees assignee when ticket is unassigned

**Context:** Found during manual review during implementation.

**Prompt:** Fix issue #3 — Reporter sees assignedTo when null/unassigned. Strip from
render array by role via TicketAccessService::filterRenderedTicket(), not Twig.

**AI response summary:** Added `filterRenderedTicket()` to `TicketAccessService`
that unconditionally unsets `field_assigned_to` for reporters. Replaced
`hook_node_view` with `hook_entity_view_alter` delegating to the service.
Retained `hook_views_pre_render` for list column hiding. Added kernel and
functional tests for unassigned and assigned cases.

**Accepted:** Service-owned render filtering per design-notes.md.

**Changed:**
`web/modules/custom/support_ticket/src/TicketAccessService.php`,
`web/modules/custom/support_ticket/support_ticket.module`,
`web/modules/custom/support_ticket/tests/src/Kernel/TicketRenderFilterTest.php`,
`web/modules/custom/support_ticket/tests/src/Functional/TicketAssigneeRenderFunctionalTest.php`.

**Rejected:** Twig conditionals or isset()-guarded hook_node_view logic.

## July 22, 2026 — Reporter sees assignee on ticket edit form

**Context:** Manual review — assignee field visible at `/node/{id}/edit` for Reporter.

**Prompt:** assignedto still visible to reporter on edit form.

**AI response summary:** Form alter was registered as `hook_form_node_ticket_form_alter`,
which only applies to the add form. Edit uses base form `node_form`. Moved logic to
`hook_form_node_form_alter` with ticket bundle check.

**Accepted:** Single alter on `node_form` covering add and edit.

**Changed:** `web/modules/custom/support_ticket/support_ticket.module`,
`web/modules/custom/support_ticket/tests/src/Functional/TicketFormFunctionalTest.php`.

## July 22, 2026 — Login lands on unscoped /node instead of /tickets

**Context:** Found during manual review during implementation.

**Prompt:** Fix issue #4 — post-login lands on /node (unscoped). Set destination to
/tickets and prevent /node from leaking unfiltered tickets.

**AI response summary:** Added `support_ticket.install` and update hook to set
`system.site:page.front` to `/tickets`. Added `FrontpageRedirectSubscriber` to
redirect direct `/node` requests to `/tickets`, closing the backdoor around
`TicketAccessService` list scoping. Functional tests cover login redirect and
/node redirect without exposing other users' tickets.

**Accepted:** Config + request subscriber (belt and suspenders).

**Changed:**
`web/modules/custom/support_ticket/support_ticket.install`,
`web/modules/custom/support_ticket/src/EventSubscriber/FrontpageRedirectSubscriber.php`,
`web/modules/custom/support_ticket/support_ticket.services.yml`,
`web/modules/custom/support_ticket/tests/src/Functional/TicketFrontpageFunctionalTest.php`.

**Rejected:** Relying on front page config alone without blocking /node direct access.

## July 22, 2026 — Front page still /node on existing install

**Context:** Found during manual review.

**Prompt:** Front page still set to /node; it should be /tickets.

**AI response summary:** Install hook only ran on first enable; existing sites never
received `page.front` update. Added idempotent `support_ticket_update_9002()` and
`hook_enable()` calling `support_ticket_set_front_page()`. Extended
`FrontpageRedirectSubscriber` to redirect `/` when `page.front` is still `/node`,
not only direct `/node` requests.

**Accepted:** Config update + enable hook + subscriber fallback for stale config.

**Changed:**
`web/modules/custom/support_ticket/support_ticket.install`,
`web/modules/custom/support_ticket/support_ticket.module`,
`web/modules/custom/support_ticket/src/EventSubscriber/FrontpageRedirectSubscriber.php`.

**Rejected:** Relying on a one-time install hook without an update path for existing sites.

## July 22, 2026 — Comments not visible on ticket detail

**Context:** Found during manual review.

**Prompt:** Comments field not visible for users to comment on raised tickets.

**AI response summary:** Comment field config was in `config/optional/` (often not
imported) and `core.entity_view_display.node.ticket.default` had no `comment`
component, so the thread and add form never rendered. Moved field storage/instance
to `config/install/`, added `comment_default` formatter to the view display, and
added `support_ticket_ensure_ticket_comments()` for existing sites via update 9002
and `hook_enable()`.

**Accepted:** Ship comment field in install config; programmatic ensure for existing DBs.

**Changed:**
`web/modules/custom/support_ticket/config/install/field.storage.node.comment.yml`,
`web/modules/custom/support_ticket/config/install/field.field.node.ticket.comment.yml`,
`web/modules/custom/support_ticket/config/install/core.entity_view_display.node.ticket.default.yml`,
`web/modules/custom/support_ticket/support_ticket.install`,
`web/modules/custom/support_ticket/support_ticket.module`,
`web/modules/custom/support_ticket/tests/src/Kernel/ModuleEnableTest.php`.

**Rejected:** Leaving comment config optional and expecting manual admin field setup.

## July 23, 2026 — Anonymous 403 on site front page (`/`)

**Context:** Found during manual review while resuming M6 (opening Lando site URL
before login).

**Prompt:** Site URL giving 403 for anonymous users. Front page is set to `/tickets`
— diagnose and fix.

**AI response summary:** Diagnosed a gap in `FrontpageRedirectSubscriber`: anonymous
redirect to `/user/login` ran only when the path was `/tickets`, not `/`. With
`page.front` = `/tickets`, the homepage still served the protected tickets view at
`/` and returned 403. Fix: treat `/` as a protected path when front page is
`/tickets` (`isProtectedTicketsPath()`). Tightened smoke test to assert login
redirect for both `/` and `/tickets`.

**Accepted:** Request-subscriber redirect for anonymous on `/` and `/tickets`.

**Changed:**
`web/modules/custom/support_ticket/src/EventSubscriber/FrontpageRedirectSubscriber.php`,
`web/modules/custom/support_ticket/tests/src/Functional/TicketSmokeFunctionalTest.php`,
`debugging-notes.md`, `ai-prompts/debugging.md`.

**Rejected:** Accepting 403 as valid anonymous homepage behavior (FR-2 requires
login redirect or access denial; redirect is the intended UX).

## July 23, 2026 — Agent 403 on ticket detail and edit

**Context:** Found during manual review — Agent user could list tickets but not
open detail or edit.

**Prompt:** Diagnose why agents cannot view ticket detail or edit pages.

**AI response summary:** `TicketAccessService::canView()` / `canUpdate()` require
`manage scoped tickets`; transitions require `transition scoped tickets`. Agent
role config had both in `config/install`, but the live DB role was missing them.
List query only checks `hasRole('agent')`, so list worked while entity routes 403'd.

**Accepted:** `support_ticket_ensure_agent_permissions()` + `support_ticket_update_9004()`.

**Changed:**
`web/modules/custom/support_ticket/support_ticket.install`,
`web/modules/custom/support_ticket/tests/src/Kernel/ModuleEnableTest.php`,
`debugging-notes.md`, `ai-prompts/debugging.md`.
