# Requirement Analysis

## Selected Project Option

**Support Ticket Management System** — internal tool for creating, updating, commenting on, searching, and progressing support tickets through a status lifecycle, with authentication, role-based access, and full user management.

## Architecture Deviation from Source Document

Doc 1's Common Technical Requirements list two separate mandatory deliverables:
"Frontend application" (item 1) and "Backend API" (item 2). This submission
deviates from that: the system is a fully server-rendered Drupal 10 monolith
(custom module + custom theme, Form API, Controllers, Views) with no exposed
REST/JSON:API layer and no separate frontend application. Drupal's own
rendering layer serves as the sole user interface; there is no API consumed by
a separate client.

This is a deliberate scope decision, not an oversight, made to keep the
submission focused on a single, cohesive Drupal architecture rather than
splitting effort across a decoupled frontend and an API contract that nothing
else would consume.

Consequence: FR-48 (REST/JSON:API exposure) and FR-50 (OpenAPI/Swagger
documentation) are considered out of scope for this submission, superseding
earlier drafts of this document that included them. api-contract.md is not
maintained as a live deliverable for this reason — see that file for a short
note pointing back here.

## My Understanding (in your own words)

This is an internal support ticket system built entirely on Drupal 10 — custom module, custom theme, Form API, Controllers, and Views under `src/`. There is no separate frontend application. Users authenticate via Drupal core session-based auth; anonymous users are redirected to login via route access control.

There is one **Admin** (Drupal default super admin) plus two sub-roles: **Agent** and **Reporter**. Admins manage users (full CRUD), see all tickets, and have full ticket/comment/transition/assignment access. Agents work a queue of assigned and unassigned tickets—they can create, update, comment, assign/reassign tickets, and perform status transitions, but cannot manage users. Reporters create tickets and comments on their own tickets and can update their own ticket fields (title, description, type, priority), but cannot change status, assignee, or see the `assignedTo` field at all.

Tickets are created **unassigned** (`assignedTo = null`). On creation, status defaults to **Open** and priority defaults to **Medium**. Only **Admin** and **Agent** may assign or reassign a ticket, and only to a user with the **Agent** role; **Agents may self-assign**. There is **no automatic assignment**. The backend must reject assignment attempts from Reporter sessions and reject any `assignedTo` value that is not an Agent-role user.

Tickets include a **`type`** field (e.g. Technical, Billing, Account, General) for categorization and search/filter. This field is an **assumption/addition—not part of the original source brief**—and does **not** drive assignment logic.

Status changes follow a strict server-side state machine. **Resolved** means the fix is verified; **Closed** means archived. Closed and cancelled tickets are read-only—no field edits and no new comments. Comments may be edited by their author only. Admins can delete tickets via Drupal; user deletion is blocked when the user is assigned to any ticket, and an Admin cannot delete themselves.

Search supports keyword search, filters (status, priority, assignee, type), sorting, and pagination (page size 5, default sort by `createdAt`). Cancelled tickets are available to Admin via a status filter; closed tickets appear in the default list.

## Functional Requirements

### Authentication & Authorization

- FR-1: Implement login/logout using **Drupal session-based authentication**.
- FR-2: Protect all Drupal routes via permissions and custom access checks; redirect anonymous users to login.
- FR-3: Enforce authorization on every Form submission handler and route access callback—verify role and resource access, not just session validity.
- FR-4: Roles: **Admin** (Drupal super admin, one account), **Agent**, **Reporter**. Enforce via Drupal permissions and custom access checks server-side.

### User Management

- FR-5: Admin can create, read, update, and delete users via the UI.
- FR-6: User fields: `id`, `name`, `email`, `role` (per original source brief—no `type` on User).
- FR-7: Default role when Admin creates a new user: **Agent**.
- FR-8: Block user deletion if the user is the assignee on any ticket.
- FR-9: Admin cannot delete their own account.
- FR-10: Bootstrap the initial Admin account via manual database setup.

### Ticket Management

- FR-11: Create, list, view detail, update, and delete tickets.
- FR-12: Ticket fields: `id`, `title`, `description`, **`type`**, `priority`, `status`, `assignedTo`, `createdBy`, `createdAt`, `updatedAt`.
- FR-13: **Mandatory on create:** `title`, `type`. All other fields optional or system-defaulted. *(Ticket `type` is an assumption/addition—not in original source brief.)*
- FR-14: On create: `status = Open`, `priority = Medium` (if not provided), `assignedTo = null` (unassigned).
- FR-15: **No automatic assignment.** Tickets remain unassigned until manually assigned by Admin or Agent.
- FR-16: Only **Admin** and **Agent** may assign or reassign `assignedTo`.
- FR-17: `assignedTo` must reference a user with the **Agent** role; any other value is rejected with a Drupal Form API validation error displayed inline on the `assignedTo` field.
- FR-18: **Agents may self-assign.**
- FR-19: **Reporter** must not see or set `assignedTo` in the UI or in rendered page output; server-side access checks reject assignment attempts from Reporter sessions.
- FR-20: **Admin** may delete tickets (via Drupal).
- FR-21: **Reporter** may update their own tickets (title, description, type, priority—fields permitted while ticket is editable; excludes status and assignee).
- FR-22: **No edits** permitted on **Closed** or **Cancelled** tickets (all roles).
- FR-23: Field length limits: `title` ≤ 100 characters; `description` ≤ 1000 characters.

### Ticket Type (Assumption / Addition)

- FR-24: Ticket `type` values include **Technical**, **Billing**, **Account**, **General** (or equivalent enum).
- FR-25: Ticket `type` is used for **categorization and search/filter only**—it does **not** drive assignment logic.

### Priority

- FR-26: Valid priority values: **Low**, **Medium**, **High**, **Critical**.

### Status Lifecycle

- FR-27: Enforce state machine server-side; reject invalid transitions with a form-level validation error regardless of role:

  | From        | To          |
  |-------------|-------------|
  | Open        | In Progress |
  | In Progress | Resolved    |
  | Resolved    | Closed      |
  | Open        | Cancelled   |
  | In Progress | Cancelled   |

- FR-28: **Resolved** = fix verified. **Closed** = archived (terminal).
- FR-29: Apply **role-specific transition rules**:
  - **Admin:** may perform any valid transition on any ticket.
  - **Agent:** may perform valid transitions on tickets **assigned to them** and on **unassigned tickets in the global queue**; may **not** transition tickets assigned to a different Agent.
  - **Reporter:** may not perform any status transition.

### Comments

- FR-30: Add comments to tickets.
- FR-31: **Reporter:** may comment only on their own tickets.
- FR-32: **Agent / Admin:** may comment on any ticket they can access.
- FR-33: Any user may **edit their own** comments.
- FR-34: Comments **not allowed** on **Cancelled** or **Closed** tickets.
- FR-35: Comment `message` ≤ 1000 characters.

### Search, Filter, Sort & Pagination

- FR-36: Keyword search across tickets.
- FR-37: Filters: status, priority, assignee, type.
- FR-38: **Admin** list: show all tickets; include **Cancelled** as a status filter option (cancelled tickets surfaced via filter).
- FR-39: **Closed** tickets appear in the default list.
- FR-40: Default sort: `createdAt` (descending assumed unless specified otherwise).
- FR-41: Sortable fields: `createdAt`, `updatedAt`, `priority`, `status`.
- FR-42: Pagination with **page size = 5**.

### Visibility by Role

- FR-43: **Admin:** all tickets (including assignee visibility).
- FR-44: **Agent:** assigned tickets and unassigned tickets only (work queue).
- FR-45: **Reporter:** own tickets only (created by them); `assignedTo` omitted from rendered views and page output.

### UI (Drupal)

- FR-46: Deliver all user-facing screens via Drupal — custom theme (Twig), Form API, Controllers, and Views (no separate SPA).
- FR-47: Ticket list, detail, create/edit forms, comment forms, user management pages, and search/filter UI rendered server-side with role-appropriate elements shown or hidden (access still enforced server-side). Assignee field visible only to Admin and Agent.

### API & Documentation

- FR-48: Expose APIs via **both REST and JSON:API** from Drupal as appropriate per resource (optional for integrations; primary UI is Drupal-rendered). **OUT OF SCOPE — see Architecture Deviation note above**
- FR-49: **Validation errors** are surfaced via Drupal Form API **inline field-level messages** on forms (error text displayed adjacent to the offending field). **Authorization failures** use Drupal's standard **access-denied and error pages** (e.g. 403 Access Denied for insufficient permissions; login redirect for anonymous users on protected routes).
- FR-50: Provide API documentation (OpenAPI/Swagger). **OUT OF SCOPE — see Architecture Deviation note above**

### Infrastructure & Testing

- FR-51: Docker setup for local/runtime environment.
- FR-52: CI workflow.
- FR-53: Integration tests for the status state machine; unit tests and edge-case/failure tests (Drupal Kernel/Functional tests).

## Non-Functional Requirements

- NFR-1: Server-side validation and authorization are the source of truth; client-side/form validation is UX-only.
- NFR-2: Never rely on hiding UI elements alone for access control—all rules enforced server-side.
- NFR-3: No secrets in source code; use `.env` / `settings.local.php` (git-ignored).
- NFR-4: Clear validation messages and access-denied handling for field validation failures, authorization failures, and invalid state transitions (form-level rejection with explanatory message).
- NFR-5: Stack: Drupal 10 monolith — custom module + custom theme (`src/`), MySQL/MariaDB. No separate frontend app.

## Assumptions

- A-1: **Addition—not cited from original source brief:** Ticket `type` field (e.g. Technical, Billing, Account, General) added for categorization and search/filter. Mandatory on create alongside `title`. Does **not** drive assignment and is **not** on the User entity.
- A-2: Tickets are created with `assignedTo = null`. Assignment is manual only, performed by Admin or Agent, targeting Agent-role users only (self-assignment permitted for Agents).
- A-3: "Unassigned" means `assignedTo` is null; ticket remains unassigned until Admin or Agent assigns it.
- A-4: Agent queue and transition scope = tickets assigned to that Agent plus all unassigned tickets (global unassigned pool). Agent may **not** perform status transitions on tickets assigned to a different Agent.
- A-5: Default sort direction for `createdAt` is descending (newest first).
- A-6: Comment edit applies to `message` only; no comment deletion unless implied by edit-only scope.
- A-7: Admin cancelled-ticket visibility: all non-cancelled tickets shown by default; Admin uses status filter to include/view cancelled tickets.
- A-8: Reporter "update own ticket" excludes status and assignee; applies only while ticket is not Closed or Cancelled. Changing `type` does not trigger reassignment.
- A-9: The single Admin maps to Drupal's built-in super-administrator (uid 1 or equivalent).
- A-10: Drupal-only monolith — no decoupled SPA; session auth via Drupal core.
- A-11: **Addition—not cited from original source brief:** Admin-only ticket deletion (FR-11, FR-20). Core features cover create/list/view/update/comment/status/search only — delete is not included.
- A-12: **Addition—not cited from original source brief:** User deletion blocking rules (FR-8: cannot delete a user who is assignee on any ticket; FR-9: Admin cannot self-delete). Not specified in the source brief.

## Clarifications (questions for a product owner)

The following were resolved during requirements gathering:

| Topic | Decision |
|-------|----------|
| Auth mechanism | Drupal session auth |
| Mandatory ticket fields | `title`, `type` (type is assumption/addition—not original brief) |
| Ticket `type` purpose | Categorization and search/filter only; no assignment logic |
| Priority enum | Low, Medium, High, Critical; default Medium |
| Assignment | Manual only; tickets created unassigned; no auto-assignment |
| Assignee rules | Agent-role users only; Admin/Agent assign; self-assign allowed; nullable on create |
| Reporter assignee access | Cannot see or set `assignedTo`; backend rejects assignment attempts |
| Ticket deletion | Admin only |
| Closed/Cancelled edits | No field edits; no new comments |
| Comment edits | Author may edit own comment |
| Agent status transitions | Assigned to self + global unassigned queue only; not tickets assigned to another Agent |
| Visibility | Admin: all; Agent: assigned + unassigned queue; Reporter: own only |
| Cancelled visibility | Admin filter dropdown; closed in default list |
| Pagination / sort | Page size 5; default sort `createdAt`; sort by createdAt, updatedAt, priority, status |
| User deletion | Blocked if user assigned to any ticket; Admin cannot self-delete |
| Architecture | Drupal-only monolith (module + theme); no React/Vite SPA |
| API style | REST and JSON:API (from Drupal) |
| Bootstrap Admin | Manual DB |
| Roles | One Admin (super admin) + Agent + Reporter sub-roles |

**Open items for confirmation during implementation:**

- C-2: Comment deletion—edit-only confirmed; is delete out of scope?

## Edge Cases

- EC-1: Reporter submits `assignedTo` in form → access denied (`AccessResult::forbidden()` / access-denied page) or inline form validation error.
- EC-2: Admin or Agent assigns ticket to non-Agent user → inline Form API validation error on the `assignedTo` field.
- EC-3: Invalid status transition (e.g. Open → Closed, Resolved → In Progress) → form-level validation error with state machine message regardless of role.
- EC-4: Reporter attempts status change → access denied (`AccessResult::forbidden()` / access-denied page) or form validation error.
- EC-5: Edit or comment on Closed/Cancelled ticket → rejected with form validation error or access denied.
- EC-6: Reporter attempts to view or comment on another user's ticket → access denied (`AccessResult::forbidden()` / access-denied page).
- EC-7: Reporter views ticket detail → `assignedTo` field omitted from rendered page output.
- EC-8: Agent requests ticket outside assigned/unassigned queue → access denied (`AccessResult::forbidden()` / access-denied page).
- EC-9: Admin deletes user who is assignee on open tickets → blocked.
- EC-10: Admin attempts self-deletion → blocked.
- EC-11: Field length exceeded (title > 100, description/comment > 1000) → inline form validation error on the offending field.
- EC-12: Anonymous user submits a protected form or accesses a protected route → login redirect or access-denied page.
- EC-13: Ticket update after concurrent status change to Closed → second form submission rejected with validation error.
- EC-14: Empty or whitespace-only title/type on create → inline form validation error.
- EC-15: Agent self-assigns unassigned ticket → allowed.
- EC-16: Reporter changes `type` on own ticket → allowed (categorization only; no assignment side effect).
