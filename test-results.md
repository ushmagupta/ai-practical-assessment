# Test Results

## Summary

| Layer | Count | Result | Runtime (Lando) |
|-------|-------|--------|-----------------|
| Kernel | 30 tests | **Pass** | ~2 min |
| Smoke (HTTP) | 8 tests | **Pass** | ~4 min |
| **Total** | **38 tests, 470 assertions** | **Pass** | **~6 min** |

CI (`.github/workflows/ci.yml`): `composer install` → `drush site:install` → enable module/theme → Kernel suite → smoke suite.

Last verified locally: July 23, 2026 (`lando phpunit`).

## Manual smoke (M6)

| Check | Result | Notes |
|-------|--------|-------|
| Login / logout (FR-1) | Pass | Core Drupal session; login redirects to `/tickets`; logout via user menu |
| Anonymous `/` and `/tickets` (FR-2) | Pass | Redirect to `/user/login` (Issue 7 fix) |
| Site loads after `lando site-install` + module enable | Pass | See `README.md` |

## Acceptance walkthrough

Legend: **Met** = implemented and verified (automated and/or manual). **Deferred** = out of smoke/Kernel scope per `test-strategy.md` P3 or descoped in requirements.

### Core

| Criterion | Status | Evidence |
|-----------|--------|----------|
| FR-1 Login/logout | Met | Manual smoke; smoke test covers login → `/tickets` |
| FR-2 Anonymous protected route | Met | Smoke `testAuthGateAndListAccess` (`/` + `/tickets`) |
| FR-4 Role enforcement server-side | Met | Kernel `TicketAccessServiceTest`; smoke list scope |
| FR-5–FR-6 Admin user CRUD | Met | Manual review; create covered in module hooks; smoke delete guard |
| FR-7 New user defaults to Agent | Met | `support_ticket_form_user_register_form_alter` |
| FR-11, FR-20 Admin ticket CRUD | Met | Smoke lifecycle + list; Kernel access |
| FR-14–FR-15 Create defaults (Open, Medium, unassigned) | Met | Smoke `testCreateAndEditTicket` |
| FR-16–FR-18 Assignment rules | Met | Kernel access; manual Agent assign |
| FR-19 Reporter omit assignee | Met | Smoke `testReporterAssigneeHidden`; Kernel render filter |
| FR-21 Reporter update own ticket | Met | Smoke `testCreateAndEditTicket` |
| FR-22 Terminal read-only | Met | Smoke `testTerminalTicketReadOnly`; Kernel terminal denial |
| FR-27 Valid transitions | Met | Kernel `TicketStatusServiceTest`; smoke lifecycle |
| FR-29 Role transition rules | Met | Kernel status + access tests |
| FR-30–FR-32 Comments by role | Met | Smoke `testAddComment`; Kernel comment access |
| FR-33 Edit own comment | Met | Kernel `testCommentAccess`; manual spot-check |
| FR-34 No comments on terminal | Met | Kernel `TicketNotTerminal`; smoke terminal test |
| FR-36–FR-42 List search/filters/sort/pagination | Deferred | View config shipped; P2 not in smoke suite (Kernel list scope only) |
| FR-43–FR-45 List visibility by role | Met | Smoke `testListScopeByRole`; Kernel `TicketListScopeTest` |
| FR-46–FR-47 Drupal UI + assignee visibility | Met | Theme + module; smoke assignee hidden |
| FR-48 REST/API | Descoped | requirements-analysis.md |
| FR-24–FR-26 Type/priority values | Met | Config + manual create |

### Validation

| Criterion | Status | Evidence |
|-----------|--------|----------|
| FR-13, EC-14 Required title/type | Met | Kernel constraints; form required fields |
| FR-23, FR-35, EC-11 Length limits | Met | Kernel `TicketConstraintTest` |
| FR-17, EC-2 Assignee must be Agent | Met | Kernel `testTicketAssigneeIsAgent` |
| FR-8–FR-9 User delete guards | Met | Kernel `UserDeleteGuardTest`; smoke assignee block |
| NFR-1 Server-side authoritative | Met | Constraints + access services |

### Error handling

| Criterion | Status | Evidence |
|-----------|--------|----------|
| FR-49 Validation / auth errors | Met | Form API + access hooks |
| EC-1–EC-8, EC-13 Edge cases | Met | Kernel primary; smoke representative paths |
| EC-5 Terminal comment/edit denial | Met | Kernel + smoke terminal |

### Testing

| Criterion | Status | Evidence |
|-----------|--------|----------|
| FR-52 CI on push/PR | Met | `.github/workflows/ci.yml` |
| FR-53 Automated coverage | Met | 30 Kernel + 8 smoke; transitions, access, constraints |

### Documentation

| Criterion | Status | Evidence |
|-----------|--------|----------|
| FR-50 OpenAPI | Descoped | No API layer |
| FR-51 Local run instructions | Met | `README.md`, `.lando.yml` |
| FR-10 Admin bootstrap | Met | `README.md` Admin bootstrap section |
| NFR-3 Secrets | Met | `.env.example`, `.gitignore`, README |

## Deferred / not automated (documented)

Per `test-strategy.md` P3 and smoke-suite trim (July 23, 2026):

- Theme empty-state copy (ui-flow) — manual/theme only
- List search, filters, sort, pagination — View config present; not in smoke tests
- Exhaustive invalid-transition HTTP matrix — Kernel covers logic
- Comment delete UI — feature not built (C-2)
- Whitespace-only title rejection — partial; core required field behavior
- Concurrent status re-check (EC-13) — Kernel stale detection; HTTP path not in smoke
- Breadcrumb trails — core/theme

## Details

### Kernel suite (`--testsuite kernel`)

- `ModuleEnableTest` — config-as-code install
- `TicketStatusServiceTest` — transitions, terminal, stale status, role permissions
- `TicketAccessServiceTest` — role matrix, terminal, comments
- `TicketListScopeTest` — Admin / Agent queue / Reporter own
- `TicketConstraintTest` — six custom constraints
- `TicketRenderFilterTest` — assignee stripped for Reporter
- `UserDeleteGuardTest` — FR-8, FR-9

### Smoke suite (`--testsuite smoke`)

`TicketSmokeFunctionalTest.php` — eight merged HTTP flows: auth gate, list scope, create/edit, lifecycle transitions, terminal read-only, assignee hidden, add comment, user delete blocked.
