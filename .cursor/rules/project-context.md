# Project Context

## What This Is
Support Ticket Management System — internal tool for creating, updating, commenting
on, searching, and progressing support tickets through a status lifecycle, with
authentication, role-based access, and full user management.

## Stack
- Application: Drupal 10 — custom module, custom theme, Form API, Controllers, Views — web/
- Database: MySQL/MariaDB
- UI: Drupal-rendered pages (Twig templates, Forms, Views) — no separate frontend app

## Entities
- User: id, name, email, role — full CRUD required (create/edit/delete users via UI)
- Ticket: id, title, description, type*, priority, status, assignedTo, createdBy, createdAt, updatedAt
  (* type: assumption/addition — categorization & filter only; not in original brief)
- Comment: id, ticketId, message, createdBy, createdAt

## Roles & Permissions
- Admin: full access — manage users (CRUD), all tickets, all transitions, all comments, assign/reassign
- Agent: create/update/comment/transition/assign tickets; cannot manage users; may self-assign
- Reporter: create tickets/comments, view own tickets, update own ticket fields; no status/assign rights; cannot see or set assignedTo
Enforce all of this server-side via Drupal permissions + custom access checks — never
rely on hiding UI elements alone.

## Assignment
- Tickets created unassigned (assignedTo = null)
- Only Admin and Agent may assign/reassign; target must be Agent-role user
- Agents may self-assign
- Reporters cannot see or set assignedTo; backend rejects Reporter assignment attempts
- No automatic assignment; ticket type does not drive assignment

## Authentication
- Login/logout via Drupal core session-based auth
- Route-level access control (_permission, _custom_access); anonymous users redirected to login
- Form and API authorization checks on every write — verify role, not just session validity

## Features
1. Create / list / view detail / update / comment on tickets (Drupal forms and pages)
2. Status changes only via the state machine below, enforced server-side
3. Keyword search, plus filters by status, priority, assignee, and type; sorting; pagination
4. Full user management (create/edit/delete users, assign roles)
5. Authentication, route access control, and form/API authorization
6. Automated tests: integration tests for the state machine, plus unit tests and
   edge-case/failure tests
7. API documentation (OpenAPI/Swagger) for REST/JSON:API endpoints exposed from Drupal
8. Docker setup and a CI workflow

## Status State Machine (must be enforced server-side)
Open -> In Progress
In Progress -> Resolved
Resolved -> Closed
Open -> Cancelled
In Progress -> Cancelled
All other transitions rejected with a clear 4xx error, regardless of role.

## Working Style
- Plan before code; flag assumptions; wait for confirmation before scaffolding.
- Server-side validation and authorization are the source of truth; client-side/form validation is UX only.
- No secrets in code — use .env / settings.local.php, git-ignored.
- Structured API error shape: { "error": { "code", "message", "field" } }
- Cite which feature/requirement above any generated code addresses.
- Flag additions not in the original source brief (e.g. ticket type field) as assumptions.
