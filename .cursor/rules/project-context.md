# Project Context

## What This Is
Support Ticket Management System — internal tool for creating, updating, commenting
on, searching, and progressing support tickets through a status lifecycle.

## Stack
- Backend: Drupal 10, custom module, REST/JSON:API — src/backend
- Frontend: React (Vite) — src/frontend
- Database: MySQL/MariaDB

## Entities
- User (seeded only): id, name, email, role
- Ticket: id, title, description, priority, status, assignedTo, createdBy, createdAt, updatedAt
- Comment: id, ticketId, message, createdBy, createdAt

## Status State Machine (must be enforced server-side)
Open -> In Progress
In Progress -> Resolved
Resolved -> Closed
Open -> Cancelled
In Progress -> Cancelled
All other transitions rejected with a clear 4xx error.

## Working Style
- Plan before code; flag assumptions; wait for confirmation before scaffolding.
- Backend validation is the source of truth; frontend validation is UX only.
- No secrets in code.
- Structured API error shape: { "error": { "code", "message", "field" } }
