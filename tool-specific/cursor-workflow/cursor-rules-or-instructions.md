# Project Context

## What This Is
Support Ticket Management System — internal tool for creating, updating, commenting
on, searching, and progressing support tickets through a status lifecycle, with
authentication, role-based access, and full user management.

## Stack
- Backend: Drupal 10, custom module, REST/JSON:API — src/backend
- Frontend: React (Vite) — src/frontend
- Database: MySQL/MariaDB

## Entities
- User: id, name, email, role — full CRUD required (create/edit/delete users via UI)
- Ticket: id, title, description, priority, status, assignedTo, createdBy, createdAt, updatedAt
- Comment: id, ticketId, message, createdBy, createdAt

## Roles & Permissions
- Admin: full access — manage users (CRUD), all tickets, all transitions, all comments
- Agent: create/update/comment/transition tickets; cannot manage users
- Reporter: create tickets/comments, view own tickets, no status/assign rights
Enforce all of this server-side via Drupal permissions + custom access checks — never
rely on hiding UI elements alone.

## Authentication
- Login/logout via Drupal session-based auth (or JWT if decoupling is preferred)
- Protected routes on the React side (redirect unauthenticated users)
- API authorization checks on every write endpoint — verify role, not just session validity

## Features
1. Create / list / view detail / update / comment on tickets
2. Status changes only via the state machine below, enforced server-side
3. Keyword search, plus filters by status, priority, and assignee; sorting; pagination
4. Full user management (create/edit/delete users, assign roles)
5. Authentication, protected routes, and per-endpoint authorization
6. Automated tests: integration tests for the state machine, plus unit tests and
   edge-case/failure tests
7. API documentation (OpenAPI/Swagger)
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
- Backend validation and authorization are the source of truth; frontend is UX only.
- No secrets in code — use .env / settings.local.php, git-ignored.
- Structured API error shape: { "error": { "code", "message", "field" } }
- Cite which feature/requirement above any generated code addresses.
