# Test Strategy

**Status:** Draft — July 10, 2026.

Defines what "correct" means for the Support Ticket System and how that correctness is verified. Sources of truth: [`requirements-analysis.md`](requirements-analysis.md), [`acceptance-criteria.md`](acceptance-criteria.md), [`data-model.md`](data-model.md), [`design-notes.md`](design-notes.md), [`ui-flow.md`](ui-flow.md).

**Architecture note:** This is a Drupal 10 monolith with session auth and server-rendered UI only. There is no exposed REST/JSON:API layer (FR-48 descoped). Tests therefore target **domain services, validation constraints, form/route access, and browser-level flows** — not API contracts or a separate frontend.

**Drupal test layers used (FR-53, design-notes):**

| Layer | Drupal base | What it proves |
|-------|-------------|----------------|
| **Kernel** | `KernelTestBase` | Services, constraints, access logic with minimal bootstrap |
| **Functional** | `BrowserTestBase` / `WebTestBase` | End-to-end HTTP flows: login, forms, Views list, rendered output |

Strict PHPUnit unit tests (no Drupal bootstrap) are **not** a primary layer — business rules live in services and Form API paths that Kernel/Functional tests exercise.

**CI (FR-52):** The full Kernel + Functional suite runs on every push/PR via a CI workflow. Details belong in `implementation-plan.md` and `README.md`; this document defines *what* must pass, not *how* the pipeline is wired.

---

## Test Scope

### In scope — must be automated

| Area | Source | Primary layer |
|------|--------|---------------|
| Status state machine (valid + invalid transitions) | FR-27, FR-28, data-model transition map | Kernel + Functional |
| Role-scoped transition permission | FR-29, A-4 | Kernel + Functional |
| `TicketStatusService` — transitions, terminal states, stale-status detection | design-notes, NFR-6, ISS-4 | Kernel |
| `TicketAccessService` — view/update/delete/assign/comment per role and ticket state | design-notes, FR-43–FR-45 | Kernel |
| Custom validation constraints | data-model constraint table | Kernel |
| Field validation (length, required, whitespace) | FR-13, FR-23, FR-35, EC-11, EC-14 | Kernel + Functional |
| Assignment rules (Agent-only assignee, self-assign, Reporter denial) | FR-16–FR-19, EC-1, EC-2, EC-15 | Kernel + Functional |
| Terminal ticket write denial | FR-22, FR-34, ISS-8, A-15 | Functional |
| Comment add/edit rules; no delete | FR-30–FR-34, C-2, A-6, A-13 | Functional |
| List visibility by role (Admin all, Agent queue, Reporter own) | FR-43–FR-45, FR-44, A-4 | Functional |
| Keyword search scope (title + description only) | FR-36, A-14, ISS-6 | Functional |
| Filters, default sort, pagination | FR-37–FR-42, A-5, A-7 | Functional (representative cases) |
| User CRUD + default role Agent | FR-5–FR-7 | Functional |
| User delete blocking | FR-8, FR-9, EC-9, EC-10 | Functional |
| Admin ticket delete | FR-20, A-11 | Functional |
| Auth gate — anonymous redirect on protected routes | FR-2, FR-3, EC-12 | Functional |
| Authorization failures (403 / access denied) | FR-49, EC-6, EC-8 | Functional |
| Concurrent modification (status re-check on submit) | NFR-6, ISS-4, EC-13 | Functional |
| Reporter `assignedTo` omitted from rendered output | FR-19, FR-45, EC-7, ui-flow detail screen | Functional |
| Create defaults (Open, Medium, unassigned) | FR-14, FR-15, A-2, A-3 | Functional |

### In scope — manual or smoke only (limited budget)

| Area | Source | Rationale |
|------|--------|-----------|
| Login/logout happy path | FR-1 | Core Drupal; one smoke test sufficient |
| Empty-state copy | ui-flow Empty & Loading States | Presentation; not acceptance-critical |
| Breadcrumb trails | ui-flow Navigation | Core/theme behavior; spot-check if time allows |

### Explicitly out of scope for automated tests

| Area | Source | Reason |
|------|--------|--------|
| REST/JSON:API endpoints | FR-48 descoped | No API layer |
| OpenAPI/Swagger | FR-50 descoped | Nothing to document or test |
| Comment deletion | C-2, A-13 | Feature not built — nothing to test |
| Initial Admin DB bootstrap | FR-10, A-9 | Manual setup; covered by README, not CI |
| Docker image correctness | FR-51 | Infrastructure; verified by running locally, not PHPUnit |
| Secrets / `.env` handling | NFR-3 | Process/doc check, not a functional test |
| Client-side-only validation | NFR-1 | Server-side is authoritative; no test priority |
| Type → assignment side effects | FR-25, EC-16 | Negative test (no side effect) optional; low priority |

### Priority tiers (limited time budget)

When time is constrained, implement in this order:

1. **P0 — Blockers:** State machine + invalid transitions (FR-27, FR-53); `TicketStatusService` and `TicketAccessService` Kernel coverage; EC-1–EC-8, EC-13.
2. **P1 — Core acceptance:** Role flows (create, edit, transition, comment); list scoping; terminal read-only (ISS-8); validation (EC-11, EC-14); user delete blocks (EC-9, EC-10).
3. **P2 — Listing:** Search, filters, sort, pagination (FR-36–FR-42) — representative cases, not full combinatorial matrix.
4. **P3 — Defer:** Empty-state copy, breadcrumbs, exhaustive invalid-transition permutations, multi-browser matrix.

---

## Unit Tests (PHPUnit, Drupal Kernel)

*Template header retained. In this project, "unit" scope is implemented as **Drupal Kernel tests** — services and constraints with entity storage, without full HTTP.*

### `TicketStatusService`

| Test target | Traces to |
|-------------|-----------|
| Each allowed transition succeeds | FR-27, data-model allowed transitions |
| Rejected transitions (e.g. `open` → `closed`, `resolved` → `in_progress`, any from terminal) | FR-27, EC-3 |
| Terminal states have no outgoing transitions | data-model |
| Admin may transition any non-terminal ticket | FR-29 |
| Agent may transition assigned-to-self and unassigned tickets | FR-29, A-4, FR-44 |
| Agent denied on ticket assigned to another Agent | FR-29, A-4, EC-8 |
| Reporter denied all transitions | FR-29, EC-4 |
| Stale-status detection when storage status differs from form build | NFR-6, ISS-4, EC-13 |

### `TicketAccessService`

| Test target | Traces to |
|-------------|-----------|
| Admin: view/update/delete/assign/comment on any ticket | FR-43, FR-20 |
| Agent: access assigned-to-self + unassigned; deny other Agent's tickets | FR-44, A-4, EC-8 |
| Reporter: own tickets only; deny others | FR-45, EC-6 |
| Terminal ticket: deny field update, transition, comment create/edit | FR-22, FR-34, ISS-8 |
| Reporter: deny assign; deny status change | FR-19, FR-29, EC-1, EC-4 |
| Comment access per role rules | FR-30–FR-32 |

### Validation constraints

Per data-model custom constraints — each constraint has at least one pass and one fail case:

| Constraint | Traces to |
|------------|-----------|
| `TicketTitleLength` | FR-23, EC-11 |
| `TicketDescriptionLength` | FR-23, EC-11 |
| `CommentMessageLength` | FR-35, EC-11 |
| `TicketAssigneeIsAgent` | FR-17, EC-2 |
| `TicketStatusTransition` | FR-27, EC-3 |
| `TicketNotTerminal` | FR-22, FR-34, EC-5 |

### User delete guards

| Test target | Traces to |
|-------------|-----------|
| Delete blocked when user is assignee on any ticket | FR-8, EC-9 |
| Admin self-delete blocked | FR-9, EC-10 |

---

## Functional / Browser Tests

*End-to-end tests via Drupal's browser test API — session auth, form submit, page assertions. Maps to acceptance-criteria **Core**, **Validation**, and **Error Handling** sections.*

### Authentication & routes

| Scenario | Traces to |
|----------|-----------|
| Anonymous user hitting `/tickets` redirected to login | FR-2, EC-12 |
| Authenticated user reaches ticket list | FR-2, ui-flow Entry |

### Ticket lifecycle (by role)

| Scenario | Traces to |
|----------|-----------|
| Create ticket: defaults Open, Medium, unassigned | FR-14, FR-15, AC Core |
| Create rejects missing/whitespace title and type | FR-13, EC-14 |
| Admin: full ticket CRUD including delete | FR-11, FR-20 |
| Reporter: update own ticket fields; cannot access other's ticket | FR-21, A-8, EC-6 |
| Reporter: change type without assignment side effect | EC-16, FR-25 |
| Admin/Agent assign to Agent user; reject non-Agent | FR-16, FR-17, EC-2 |
| Agent self-assign unassigned ticket | FR-18, EC-15 |
| Reporter: `assignedTo` absent from detail HTML | FR-19, FR-45, EC-7 |

### Status transitions (UI path)

Via `/ticket/{node}/transition` (ui-flow) — complements Kernel service tests:

| Scenario | Traces to |
|----------|-----------|
| Valid paths: Open→In Progress→Resolved→Closed; Open/In Progress→Cancelled | FR-27, AC Core |
| Invalid transition shows form-level error | FR-27, NFR-4, EC-3 |
| Reporter cannot reach transition form | FR-29, EC-4 |
| Agent denied transition on another Agent's ticket | A-4, EC-8 |

### Terminal & concurrent behavior

| Scenario | Traces to |
|----------|-----------|
| Closed/Cancelled detail viewable; edit/transition/comment forms absent or rejected | FR-22, ISS-8, A-15, EC-5 |
| Submit on stale form after status changed elsewhere → form error | NFR-6, ISS-4, EC-13 |

### Comments

| Scenario | Traces to |
|----------|-----------|
| Add comment per role scope | FR-30–FR-32 |
| Edit own comment on non-terminal ticket | FR-33, A-6 |
| No comment delete control for any role | C-2, A-13 |
| No add/edit on terminal ticket | FR-34, ISS-5, EC-5 |

### Ticket list (`/tickets`)

| Scenario | Traces to |
|----------|-----------|
| Admin sees all tickets; Cancelled via status filter; Closed in default list | FR-38, FR-39, A-7 |
| Agent sees assigned + unassigned only | FR-44, A-4 |
| Reporter sees own tickets only | FR-45 |
| Keyword matches title and description; not other fields | FR-36, A-14 |
| Filters: status, priority, assignee, type (at least one positive case each) | FR-37 |
| Default sort `createdAt` desc; sortable fields work | FR-40, FR-41, A-5 |
| Page size 5 | FR-42 |
| Assignee column visible Admin/Agent only | FR-47, ui-flow list columns |

### User management

| Scenario | Traces to |
|----------|-----------|
| Admin creates user with default Agent role | FR-7 |
| Admin CRUD fields: name, email, role | FR-5, FR-6 |
| Delete blocked for assignee and self | FR-8, FR-9, EC-9, EC-10 |

### Error presentation

| Scenario | Traces to |
|----------|-----------|
| Field validation → inline message on offending field | FR-49 |
| Authorization → 403 or login redirect | FR-49, FR-3 |

---

## API / Integration Tests

*Template header **adapted**: there is no API layer (FR-48 descoped). This section covers **workflow integration** — state machine and write-path behavior exercised through forms and services together, as required by FR-53 ("integration tests for the status state machine").*

| Integration scenario | Traces to | Layer |
|---------------------|-----------|-------|
| Full happy-path lifecycle: create → assign → Open→In Progress→Resolved→Closed | FR-27, FR-28 | Functional |
| Cancellation path: create → Open→Cancelled (terminal, read-only) | FR-27 | Functional |
| Transition form is sole status-change path (not general edit form) | design-notes Forms and routes, ui-flow Edit screen | Functional |
| Status change persists `field_ticket_status`; node publish `status` stays published | data-model node base-field disambiguation | Kernel or Functional |
| Write-path: route access → form build → submit validation → persist | design-notes write-path flow | Functional (spot-check) |
| Views list + access alter returns role-scoped rows | design-notes TicketAccessService list scoping | Functional |

No REST, JSON:API, or HTTP contract tests — intentionally absent per architecture deviation.

---

## Edge Case Tests

Explicit coverage for **EC-1 through EC-16** (requirements-analysis). Each maps to Kernel, Functional, or both as noted in sections above.

| ID | Scenario | Test layer |
|----|----------|------------|
| EC-1 | Reporter submits `assignedTo` → denied | Functional |
| EC-2 | Assign to non-Agent → inline field error | Kernel + Functional |
| EC-3 | Invalid transition → form-level error | Kernel + Functional |
| EC-4 | Reporter status change → denied | Kernel + Functional |
| EC-5 | Write on Closed/Cancelled ticket → rejected | Functional |
| EC-6 | Reporter views/comments other's ticket → denied | Functional |
| EC-7 | Reporter detail omits `assignedTo` from output | Functional |
| EC-8 | Agent accesses ticket outside queue → denied | Kernel + Functional |
| EC-9 | Delete user who is assignee → blocked | Functional |
| EC-10 | Admin self-delete → blocked | Functional |
| EC-11 | Length exceeded → inline field error | Kernel + Functional |
| EC-12 | Anonymous protected route → login redirect | Functional |
| EC-13 | Concurrent status change → form error on re-check | Functional |
| EC-14 | Whitespace-only title/type on create → rejected | Functional |
| EC-15 | Agent self-assign unassigned → allowed | Functional |
| EC-16 | Reporter changes type → allowed, no assignment effect | Functional |

**P3 deferral under time pressure:** EC-16 and non-critical EC-11 variants (e.g. boundary exactly at 100/1000 chars) may be covered by Kernel only, skipping duplicate Functional runs.

---

## Tests Not Covered (and why)

| Not covered | Why |
|-------------|-----|
| **REST/JSON:API** | FR-48 descoped — no API exists |
| **OpenAPI/Swagger** | FR-50 descoped |
| **Comment deletion** | C-2 — feature not built; AC explicitly expects absence |
| **Auto-assignment** | FR-15 — feature does not exist; negative path N/A |
| **Content Moderation / node_access grants** | design-notes deliberate non-choices — not used |
| **Unpublished/draft tickets** | data-model — out of scope |
| **Admin bootstrap via manual DB** | FR-10 — operational procedure, documented in README |
| **Docker/CI infrastructure** | FR-51/FR-52 — pipeline verified by running CI, not PHPUnit |
| **Secrets in repo** | NFR-3 — manual/doc review |
| **Full filter × sort × role combinatorial matrix** | Limited budget; P2 uses representative cases (FR-37–FR-42) |
| **All invalid transition permutations** | P0 covers representative invalid cases; exhaustive matrix deferred |
| **Multi-browser / visual regression** | Not in requirements; single browser driver sufficient |
| **Performance / load** | Not in NFRs |
| **Empty-state exact copy strings** | ui-flow presentation; P3 manual/smoke |
| **Password reset / account recovery** | Core Drupal; not a ticket workflow requirement |
| **Reporter tamper via direct POST bypassing hidden fields** | Covered implicitly by server-side denial (NFR-1, NFR-2); no separate security-penetration suite |

---

## Traceability summary

| Acceptance-criteria section | Primary test layer |
|----------------------------|-------------------|
| Core | Functional (+ Kernel for services) |
| Validation | Kernel + Functional |
| Error Handling | Functional (+ Kernel for transition/access) |
| Testing (FR-52, FR-53) | CI runs full suite; this document defines coverage |
| Documentation (FR-51, FR-10, NFR-3) | Manual/README — not PHPUnit |

---

## Ambiguities — none blocking

All test targets trace to the five source documents. No open design items remain (C-2, ISS-4–ISS-8 resolved in data-model).

**One judgment call documented here (not invented scope):** FR-53 says "unit tests" and "integration tests" while design-notes specifies Kernel + Functional. This strategy treats **Kernel tests as the unit-equivalent layer** for Drupal services/constraints and **Functional tests as integration + acceptance** for HTTP flows — consistent with FR-53 intent without adding a separate API integration suite.
