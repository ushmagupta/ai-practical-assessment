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
