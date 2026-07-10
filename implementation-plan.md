# Implementation Plan

**Status:** Draft — July 10, 2026.

Build-and-verify schedule for the Support Ticket System. Sources of truth: [`requirements-analysis.md`](requirements-analysis.md), [`acceptance-criteria.md`](acceptance-criteria.md), [`data-model.md`](data-model.md), [`design-notes.md`](design-notes.md), [`ui-flow.md`](ui-flow.md), [`test-strategy.md`](test-strategy.md).

No new architectural decisions or test scope beyond those documents.

---

## Overview

**What we're building:** A Drupal 10 monolith under `src/` — custom module `support_ticket`, custom theme `support_ticket_theme`, config-as-code install, two domain services (`TicketStatusService`, `TicketAccessService`), server-rendered screens per [`ui-flow.md`](ui-flow.md), and automated Kernel + Functional tests per [`test-strategy.md`](test-strategy.md).

**Build philosophy:**

1. **Config-as-code first** — ticket bundle, fields, roles, permissions, comment type, and tickets View ship in `config/install`; no manual Field UI setup (design-notes).
2. **Services before UI** — business rules live in services; hooks and forms delegate to them (design-notes).
3. **Build paired with verify** — every task below is written as *build X, verified by Y*; tests are not a late bolt-on phase (test-strategy P0→P3).
4. **Thin integration layer** — `.module` file, access callbacks, form alters, and Views query alter wire services to Drupal; no custom tables (data-model).
5. **CI from early milestone** — PHPUnit runs on push/PR once the first Kernel tests exist (FR-52, test-strategy).

**Out of scope for this plan:** REST/JSON:API (FR-48), OpenAPI (FR-50), comment deletion (C-2), auto-assignment (FR-15). Manual Admin bootstrap documented in README, not automated (FR-10).

## Task Breakdown

Tasks are ordered by dependency. Each row: **build** → **verified by** (test-strategy reference).

### Milestone 0 — Environment & scaffold

| # | Build | Verified by |
|---|-------|-------------|
| 0.1 | Docker Compose + Drupal 10 app skeleton under `src/` (FR-51) | Local `docker compose up` brings site to installable state; documented in README |
| 0.2 | Custom module skeleton `support_ticket` (`.info.yml`, `services.yml`, PSR-4 `src/`) | Module enables without error |
| 0.3 | Custom theme skeleton `support_ticket_theme` | Theme enables without error |
| 0.4 | CI workflow stub running PHPUnit (FR-52) | Pipeline executes (may pass with zero tests initially) |
| 0.5 | README: prerequisites, local run, test command, Admin bootstrap note (FR-10, NFR-3) | Manual/doc review (test-strategy: not PHPUnit) |

### Milestone 1 — Config-as-code (data model materialized)

| # | Build | Verified by |
|---|-------|-------------|
| 1.1 | `node.type.ticket` + field storage/instances per data-model (`field_ticket_type`, `field_ticket_status`, `field_description`, `field_priority`, `field_assigned_to`) | Module reinstall: ticket bundle and fields exist; create form shows expected fields |
| 1.2 | Roles `agent`, `reporter` + permissions for ticket/comment operations (FR-4) | Role assignment works; coarse permissions present |
| 1.3 | `comment.type` on ticket bundle (data-model Comment entity) | Comments attachable to ticket nodes |
| 1.4 | Exported View `views.view.tickets` — page at `/tickets`, columns/filters/sort/pagination per data-model list defaults (FR-37–FR-42, ui-flow list) | `/tickets` renders table after enable |
| 1.5 | Menu links: Tickets (`/tickets`), People (`/admin/people`) per ui-flow | Primary menu items visible per role |

### Milestone 2 — Domain services (P0 Kernel)

| # | Build | Verified by |
|---|-------|-------------|
| 2.1 | `TicketStatusService` — transition map, terminal states, role-scoped transition permission, stale-status check (data-model, NFR-6) | Kernel: all `TicketStatusService` targets in test-strategy Unit Tests § TicketStatusService |
| 2.2 | `TicketAccessService` — view/update/delete/assign/comment per role, terminal guard, queue scope (FR-43–FR-45, A-4) | Kernel: all `TicketAccessService` targets in test-strategy Unit Tests § TicketAccessService |

### Milestone 3 — Validation constraints (P0 Kernel)

| # | Build | Verified by |
|---|-------|-------------|
| 3.1 | Six constraints: `TicketTitleLength`, `TicketDescriptionLength`, `CommentMessageLength`, `TicketAssigneeIsAgent`, `TicketStatusTransition`, `TicketNotTerminal` (data-model) | Kernel: pass + fail case per constraint (test-strategy Unit Tests § Validation constraints) |
| 3.2 | User delete guards — assignee block (FR-8), Admin self-delete block (FR-9) | Kernel: test-strategy Unit Tests § User delete guards (EC-9, EC-10) |

### Milestone 4 — Access integration & transition form (P0)

| # | Build | Verified by |
|---|-------|-------------|
| 4.1 | `hook_node_access` + route access callbacks delegating to `TicketAccessService` (design-notes) | Kernel: access service cases; Functional: EC-6, EC-8 (denied access) |
| 4.2 | Views query alter for list row scoping — Agent queue, Reporter author filter (design-notes, FR-44–FR-45) | Functional: test-strategy API/Integration § Views list + access alter |
| 4.3 | Transition form + route `/ticket/{node}/transition` — sole status-change path (ui-flow, design-notes) | Kernel: `TicketStatusTransition` constraint; Functional: valid paths FR-27, invalid EC-3, Reporter EC-4, Agent EC-8 |
| 4.4 | Status re-check on transition/edit/comment submit (ISS-4, NFR-6) | Functional: EC-13 (concurrent status change) |
| 4.5 | Anonymous redirect on protected routes (FR-2) | Functional: EC-12 |

### Milestone 5 — Ticket forms & lifecycle (P1 Functional)

| # | Build | Verified by |
|---|-------|-------------|
| 5.1 | Node form alters — create/edit field visibility per data-model role matrix; status off edit form; Reporter `assignedTo` hidden (ui-flow, FR-19) | Functional: create defaults FR-14/FR-15; Reporter EC-7 (`assignedTo` absent from HTML) |
| 5.2 | Create validation — required title/type, whitespace rejection (FR-13, EC-14) | Functional: EC-14; Kernel+Functional: EC-11 length on title |
| 5.3 | Assignment on edit — Agent-only assignee, self-assign (FR-16–FR-18) | Kernel+Functional: EC-2, EC-15; Functional: EC-1 (Reporter assign denied) |
| 5.4 | Terminal ticket read-only — edit route denied/disabled, no write affordances (ISS-8) | Functional: EC-5, terminal & concurrent § Closed/Cancelled detail viewable |
| 5.5 | Admin ticket delete (FR-20) | Functional: Admin full CRUD (test-strategy Ticket lifecycle) |
| 5.6 | Reporter own-ticket update; type change without assignment side effect (FR-21, FR-25) | Functional: EC-16; EC-6 (other user's ticket denied) |
| 5.7 | Full lifecycle integration path | Functional: test-strategy API/Integration § happy-path + cancellation path |

### Milestone 6 — Comments (P1 Functional)

| # | Build | Verified by |
|---|-------|-------------|
| 6.1 | Comment form access + `TicketNotTerminal` on parent ticket (FR-30–FR-34) | Functional: add per role scope; no add/edit on terminal EC-5 |
| 6.2 | Author edit own comment on non-terminal ticket (FR-33, A-6) | Functional: Comments § edit own |
| 6.3 | No comment delete UI or route (C-2) | Functional: Comments § no delete control |

### Milestone 7 — Ticket list & search (P2 Functional)

| # | Build | Verified by |
|---|-------|-------------|
| 7.1 | Keyword search — title + description only (FR-36, A-14) | Functional: Ticket list § keyword scope |
| 7.2 | Exposed filters: status, priority, assignee, type (FR-37) | Functional: at least one positive case per filter |
| 7.3 | Role-scoped list rows + Admin Cancelled filter / Closed in default list (FR-38, FR-39, A-7) | Functional: Admin/Agent/Reporter list scenarios |
| 7.4 | Sort (`createdAt` desc default, sortable fields) + pagination page size 5 (FR-40–FR-42, A-5) | Functional: Ticket list § sort and pagination |
| 7.5 | Assignee column visibility Admin/Agent only (FR-47, ui-flow) | Functional: Ticket list § assignee column |

### Milestone 8 — User management (P1 Functional)

| # | Build | Verified by |
|---|-------|-------------|
| 8.1 | User form alters — default role Agent on create (FR-7); Admin-only access | Functional: User management § create default Agent, CRUD fields |
| 8.2 | Delete blocking — assignee on any ticket (FR-8), Admin self-delete (FR-9) | Functional: EC-9, EC-10 |

### Milestone 9 — Theme & presentation (P1 + P3)

| # | Build | Verified by |
|---|-------|-------------|
| 9.1 | Twig overrides — ticket detail, list; assignee block omitted for Reporter (FR-19, design-notes Frontend) | Functional: EC-7 |
| 9.2 | Terminal ticket presentation — read-only fields, hidden write tasks (ISS-8, ui-flow detail) | Functional: terminal § forms absent or rejected |
| 9.3 | Local tasks: View, Edit, Change status, Delete per ui-flow | Functional: transition form sole path (API/Integration §); Reporter cannot reach transition EC-4 |
| 9.4 | Empty-state copy (ui-flow) | Manual/smoke only (test-strategy P3 — defer if time-constrained) |

### Milestone 10 — CI hardening & acceptance pass

| # | Build | Verified by |
|---|-------|-------------|
| 10.1 | CI runs full Kernel + Functional suite on push/PR (FR-52) | Green pipeline; test-strategy Traceability § Testing |
| 10.2 | Login/logout smoke (FR-1) | Functional smoke (test-strategy: manual/smoke) |
| 10.3 | Error presentation — inline field errors, 403/login redirect (FR-49) | Functional: Error presentation § |
| 10.4 | Acceptance-criteria walkthrough — Core, Validation, Error Handling checklists | All P0 + P1 automated; P2 representative; P3 deferred items documented |

## Milestones

| Milestone | Done when (verifiable) |
|-----------|------------------------|
| **M0 — Scaffold** | Docker/README runnable; module + theme enable; CI executes PHPUnit |
| **M1 — Config** | `support_ticket` enable installs full data model; `/tickets` View page loads |
| **M2 — Services** | All Kernel tests for `TicketStatusService` + `TicketAccessService` pass (test-strategy P0) |
| **M3 — Constraints** | All six constraint Kernel tests + user-delete guard tests pass (test-strategy P0) |
| **M4 — Access & transitions** | EC-3, EC-4, EC-6, EC-8, EC-12, EC-13 covered; Views alter Functional passes |
| **M5 — Ticket lifecycle** | P1 Functional: create/edit/delete, assignment, terminal read-only, EC-1/14/15/16; integration lifecycle tests pass |
| **M6 — Comments** | P1 Functional: FR-30–34, C-2 absence, EC-5 comment paths pass |
| **M7 — Listing** | P2 Functional: search, filters, sort, pagination, role-scoped rows pass (representative cases) |
| **M8 — Users** | P1 Functional: FR-5–9, EC-9/10 pass |
| **M9 — Theme** | EC-7 passes; terminal presentation passes; P3 empty-state optional |
| **M10 — Ship-ready** | CI green on full suite; acceptance-criteria Core + Validation + Error Handling automated coverage complete per test-strategy priority tiers |

## AI Usage Plan

Cursor used per milestone, consistent with project rhythm: **scaffold → review → correct → commit → log**.

| Milestone | Cursor role | Human role |
|-----------|-------------|------------|
| **M0** | Generate Docker/README/CI skeleton from design docs; flag assumptions | Review secrets handling (NFR-3); verify local boot |
| **M1** | Generate `config/install` YAML from data-model field/bundle definitions | Review config dependency order; test clean module enable |
| **M2–M3** | Scaffold services + constraints + Kernel test classes from test-strategy tables | Review service API against data-model transition/access rules; run Kernel suite |
| **M4** | Scaffold hooks, access callbacks, transition form, route | Review thin-hook pattern; verify denied paths match EC matrix |
| **M5–M8** | Scaffold form alters + Functional tests per ui-flow screens | Review role matrix edge cases; run Functional suite incrementally |
| **M7** | Adjust View config + query alter; Functional list tests | Spot-check filter UX; confirm keyword scope (title+description only) |
| **M9** | Twig overrides from ui-flow presentation rules | Visual check Reporter assignee hidden; terminal read-only |
| **M10** | Fix CI failures; gap-fill missing tests against acceptance-criteria | Final AC walkthrough; log in `ai-prompts/implementation.md` |

**Per-task rhythm:**

1. **Prompt** — cite FR/EC and design doc section; request minimal diff.
2. **Scaffold** — Cursor generates code/tests.
3. **Review** — human or Cursor bugbot-style review against data-model + test-strategy.
4. **Correct** — fix failures; re-run PHPUnit.
5. **Commit** — only when user requests; one logical unit per commit.
6. **Log** — append dated entry to `ai-prompts/implementation.md` (and `planning.md` for planning artifacts).

**Context files to attach:** `project-context.md`, relevant design doc, `test-strategy.md` section for current milestone.

## Risks

| Risk | Impact |
|------|--------|
| **Config install order** — field storage before instances before View dependencies | Module enable fails or incomplete schema |
| **`field_ticket_status` vs node `status` confusion** — workflow vs publish flag (data-model disambiguation) | Wrong field updated; transitions break |
| **Views query alter + `hook_node_access` drift** — list shows rows detail denies (or vice versa) | Agent/Reporter see wrong tickets (FR-44, FR-45) |
| **Transition form bypass** — status changeable via general edit form | Violates design-notes write-path; FR-27 untestable as specified |
| **Reporter assignee leak** — theme or render exposes `assignedTo` | FR-19 / EC-7 failure despite server-side deny |
| **Concurrent submit race** — stale form after another session closes ticket | EC-13 / ISS-4 failure if re-check missing on all write forms |
| **Functional test runtime** — full browser suite slow in CI | FR-52 pipeline timeout; pressure to skip P2 |
| **Comment module config** — comment type not bound to ticket bundle | Comments unusable; FR-30 blocked |
| **Agent queue boundary** — ticket assigned to other Agent partially accessible | EC-8 false positive/negative |
| **Time budget** — P2 listing + P3 presentation deferred silently | Acceptance gaps without explicit deferral |

## Mitigation

| Risk | Mitigation |
|------|------------|
| Config install order | Follow data-model config artifact list; enable module on clean DB in CI; fix dependency errors before proceeding |
| Status field confusion | Use only `field_ticket_status` in services/constraints; Kernel test that node publish `status` stays `1` (test-strategy API/Integration) |
| Views + access drift | Both delegate to `TicketAccessService`; Functional tests for list scope AND detail access per role (M4 + M7) |
| Transition bypass | Omit status from node edit form; Functional test that edit form cannot change status (API/Integration §) |
| Reporter assignee leak | Module enforces access; theme omits block; Functional asserts HTML absence (EC-7) |
| Concurrent submit | Re-check `field_ticket_status` on every write form in M4.4; Functional EC-13 |
| Functional test runtime | Implement P0 Kernel first for fast feedback; P2 representative cases only per test-strategy; parallelize CI if needed |
| Comment config | Milestone 1.3 gate before M6; smoke comment create in M6.1 |
| Agent queue boundary | Kernel tests for assigned-to-other-Agent deny; Functional EC-8 on transition + view |
| Time budget | Track P0→P3 explicitly in M10.4; document deferred items in `test-results.md`, not silent omission |
