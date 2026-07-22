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
