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

## Issue 2
