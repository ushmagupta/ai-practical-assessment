# Requirement Analysis

## Selected Project Option

**Support Ticket Management System** — internal tool for creating, updating, commenting on, searching, and progressing support tickets through a status lifecycle, with authentication, role-based access, and full user management.

## My Understanding (in your own words)

This is an internal support ticket system built with Drupal 10 (backend) and React/Vite (frontend). Users authenticate via Drupal session-based auth; the React app uses protected routes and redirects unauthenticated users.

There is one **Admin** (Drupal default super admin) plus two sub-roles: **Agent** and **Reporter**. Admins manage users (full CRUD), see all tickets, and have full ticket/comment/transition access. Agents work a queue of assigned and unassigned tickets—they can create, update, comment, change assignee, and perform status transitions, but cannot manage users. Reporters create tickets and comments on their own tickets, can update their own ticket fields, but cannot change status or assignee.

Tickets have a **type** field (mandatory, alongside title). On creation, status defaults to **Open**, priority defaults to **Medium**, and the system **auto-assigns** an Agent whose user-level **type** matches the ticket type (e.g. ticket `type = IT` → Agent with `type = IT`). Assignee is always required (no null); only Agents may be assigned; self-assignment is not allowed.

Status changes follow a strict server-side state machine. **Resolved** means the fix is verified; **Closed** means archived. Closed and cancelled tickets are read-only—no field edits and no new comments. Comments may be edited by their author only. Admins can delete tickets via Drupal; user deletion is blocked when the user is assigned to any ticket, and an Admin cannot delete themselves.

Search supports keyword search, filters (status, priority, assignee, type), sorting, and pagination (page size 5, default sort by `createdAt`). Cancelled tickets are available to Admin via a status filter; closed tickets appear in the default list.

## Functional Requirements

### Authentication & Authorization

- FR-1: Implement login/logout using **Drupal session-based authentication**.
- FR-2: Protect all React routes; redirect unauthenticated users to login.
- FR-3: Enforce authorization on every write API endpoint—verify role and resource access, not just session validity.
- FR-4: Roles: **Admin** (Drupal super admin, one account), **Agent**, **Reporter**. Enforce via Drupal permissions and custom access checks server-side.

### User Management

- FR-5: Admin can create, read, update, and delete users via the UI.
- FR-6: User fields: `id`, `name`, `email`, `role`, **`type`** (Agent only—used for auto-assignment matching).
- FR-7: Default role when Admin creates a new user: **Agent**.
- FR-8: Block user deletion if the user is the assignee on any ticket.
- FR-9: Admin cannot delete their own account.
- FR-10: Bootstrap the initial Admin account via manual database setup.

### Ticket Management

- FR-11: Create, list, view detail, update, and delete tickets.
- FR-12: Ticket fields: `id`, `title`, `description`, **`type`**, `priority`, `status`, `assignedTo`, `createdBy`, `createdAt`, `updatedAt`.
- FR-13: **Mandatory on create:** `title`, `type`. All other fields optional or system-defaulted.
- FR-14: On create: `status = Open`, `priority = Medium` (if not provided).
- FR-15: **Auto-assign** `assignedTo` to an Agent whose `type` matches the ticket `type`. Assignee is required—null assignee is not allowed.
- FR-16: Only **Agents** may be assigned. Self-assignment is not allowed.
- FR-17: Only **Admin** and **Agent** may change assignee.
- FR-18: **Admin** may delete tickets (via Drupal).
- FR-19: **Reporter** may update their own tickets (title, description, type, priority—fields permitted while ticket is editable).
- FR-20: **No edits** permitted on **Closed** or **Cancelled** tickets (all roles).
- FR-21: Field length limits: `title` ≤ 100 characters; `description` ≤ 1000 characters.

### Priority

- FR-22: Valid priority values: **Low**, **Medium**, **High**, **Critical**.

### Status Lifecycle

- FR-23: Enforce state machine server-side; reject invalid transitions with structured 4xx error regardless of role:

  | From        | To          |
  |-------------|-------------|
  | Open        | In Progress |
  | In Progress | Resolved    |
  | Resolved    | Closed      |
  | Open        | Cancelled   |
  | In Progress | Cancelled   |

- FR-24: **Resolved** = fix verified. **Closed** = archived (terminal).
- FR-25: Apply **role-specific transition rules**:
  - **Admin:** may perform any valid transition on any ticket.
  - **Agent:** may perform valid transitions on tickets in their queue (assigned to them or unassigned).
  - **Reporter:** may not perform any status transition.

### Comments

- FR-26: Add comments to tickets.
- FR-27: **Reporter:** may comment only on their own tickets.
- FR-28: **Agent / Admin:** may comment on any ticket they can access.
- FR-29: Any user may **edit their own** comments.
- FR-30: Comments **not allowed** on **Cancelled** or **Closed** tickets.
- FR-31: Comment `message` ≤ 1000 characters.

### Search, Filter, Sort & Pagination

- FR-32: Keyword search across tickets.
- FR-33: Filters: status, priority, assignee, type.
- FR-34: **Admin** list: show all tickets; include **Cancelled** as a status filter option (cancelled tickets surfaced via filter).
- FR-35: **Closed** tickets appear in the default list.
- FR-36: Default sort: `createdAt` (descending assumed unless specified otherwise).
- FR-37: Sortable fields: `createdAt`, `updatedAt`, `priority`, `status`.
- FR-38: Pagination with **page size = 5**.

### Visibility by Role

- FR-39: **Admin:** all tickets.
- FR-40: **Agent:** assigned tickets and unassigned tickets only (work queue).
- FR-41: **Reporter:** own tickets only (created by them).

### API & Documentation

- FR-42: Expose APIs via **both REST and JSON:API** as appropriate per resource.
- FR-43: Structured error shape: `{ "error": { "code", "message", "field" } }`.
- FR-44: Provide API documentation (OpenAPI/Swagger).

### Infrastructure & Testing

- FR-45: Docker setup for local/runtime environment.
- FR-46: CI workflow.
- FR-47: Integration tests for the status state machine; unit tests and edge-case/failure tests.

## Non-Functional Requirements

- NFR-1: Backend validation and authorization are the source of truth; frontend validation is UX-only.
- NFR-2: Never rely on hiding UI elements alone for access control—all rules enforced server-side.
- NFR-3: No secrets in source code; use `.env` / `settings.local.php` (git-ignored).
- NFR-4: Clear 4xx errors for validation, authorization, and invalid state transitions.
- NFR-5: Stack: Drupal 10 custom module (`src/backend`), React/Vite (`src/frontend`), MySQL/MariaDB.

## Assumptions

- A-1: When multiple Agents share the same `type`, auto-assignment picks one deterministically (e.g. lowest user id or round-robin)—exact strategy to be defined during implementation.
- A-2: If no Agent exists for a given ticket `type`, ticket creation fails with a clear validation error.
- A-3: "Unassigned" in the Agent queue means tickets with no matching assignee yet or tickets awaiting assignment after type-based routing failure is handled at create time—since null assignee is disallowed, auto-assign must succeed at creation or creation is rejected.
- A-4: Agent "assigned/unassigned list" means tickets assigned to that Agent plus tickets in the unassigned pool relevant to their `type` (or global unassigned queue)—exact scoping to be confirmed during API design.
- A-5: Default sort direction for `createdAt` is descending (newest first).
- A-6: Comment edit applies to `message` only; no comment deletion unless implied by edit-only scope.
- A-7: Admin cancelled-ticket visibility: all non-cancelled tickets shown by default; Admin uses status filter to include/view cancelled tickets.
- A-8: Reporter "update own ticket" excludes status, assignee, and applies only while ticket is not Closed or Cancelled.
- A-9: The single Admin maps to Drupal's built-in super-administrator (uid 1 or equivalent).
- A-10: JWT decoupled auth is **not** used; session auth only per decision.

## Clarifications (questions for a product owner)

The following were resolved during requirements gathering:

| Topic | Decision |
|-------|----------|
| Auth mechanism | Drupal session auth |
| Mandatory ticket fields | `title`, `type` |
| Priority enum | Low, Medium, High, Critical; default Medium |
| Auto-assignment | Match ticket `type` to Agent user `type` |
| Assignee rules | Agents only; required; no self-assignment; Admin/Agent change assignee |
| Ticket deletion | Admin only |
| Closed/Cancelled edits | No field edits; no new comments |
| Comment edits | Author may edit own comment |
| Visibility | Admin: all; Agent: assigned + unassigned queue; Reporter: own only |
| Cancelled visibility | Admin filter dropdown; closed in default list |
| Pagination / sort | Page size 5; default sort `createdAt`; sort by createdAt, updatedAt, priority, status |
| User deletion | Blocked if user assigned to any ticket; Admin cannot self-delete |
| API style | REST and JSON:API |
| Bootstrap Admin | Manual DB |
| Roles | One Admin (super admin) + Agent + Reporter sub-roles |

**Open items for confirmation during implementation:**

- C-1: Auto-assign tie-breaker when multiple Agents share the same `type`.
- C-2: Exact definition of Agent "unassigned" queue scope (by Agent type vs global).
- C-3: Specific Agent transition permissions—can an Agent transition any ticket in their queue, or only tickets assigned to them (excluding unassigned)?
- C-4: Can Reporters change `type` on an existing ticket after creation (triggers re-assignment?) or only at create time?
- C-5: Comment deletion—edit-only confirmed; is delete out of scope?

## Edge Cases

- EC-1: Ticket created with a `type` that has no matching Agent → creation fails with field-level error on `type`.
- EC-2: Multiple Agents with same `type` → need deterministic assignee selection.
- EC-3: Invalid status transition (e.g. Open → Closed, Resolved → In Progress) → 4xx with state machine error regardless of role.
- EC-4: Reporter attempts status change or assignee change → 403/4xx authorization error.
- EC-5: Agent attempts self-assignment → rejected.
- EC-6: Edit or comment on Closed/Cancelled ticket → rejected.
- EC-7: Reporter attempts to view or comment on another user's ticket → denied.
- EC-8: Agent requests ticket outside assigned/unassigned queue → denied.
- EC-9: Admin deletes user who is assignee on open tickets → blocked.
- EC-10: Admin attempts self-deletion → blocked.
- EC-11: Field length exceeded (title > 100, description/comment > 1000) → validation error with `field` in error payload.
- EC-12: User without session calls write endpoint → 401/403.
- EC-13: Ticket update after concurrent status change to Closed → second write rejected.
- EC-14: Auto-assign when sole Agent for a type is inactive/deleted → creation or reassignment failure handling required.
- EC-15: Empty or whitespace-only title/type on create → validation error.
