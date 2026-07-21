# AI Prompts — implementation

## July 21, 2026 — M1 scaffold (repo root)

**Prompt:**
Implement M1 scaffold at repo root (PHP 8.3, standard profile, Olivero base theme,
Lando, GitHub Actions CI; scaffold-only — no config-as-code or `/tickets`).

**AI response summary:**
Created Drupal 10 recommended-project at repo root: `composer.json`, `.lando.yml`,
`web/modules/custom/support_ticket/` (module skeleton + Kernel `ModuleEnableTest`),
`web/themes/custom/support_ticket_theme/` (Olivero sub-theme), `phpunit.xml`,
`.github/workflows/ci.yml`, `.env.example`, and `README.md`. Updated `.gitignore`,
`implementation-plan.md` (M1 split into M1a scaffold / M1b config), `candidate-info.md`
(Lando, `web/` paths), and `.cursor/rules/project-context.md` (`web/` not `src/`).
Ran `composer install` to generate `composer.lock`.

**Accepted:**
M1a scaffold deliverables — Composer project, Lando, module/theme skeletons, Kernel
smoke test, CI workflow, README with Admin bootstrap (FR-10).

**Changed:**
None.

**Rejected:**
None.

## July 21, 2026 — MariaDB instead of MySQL

**Prompt:**
Use latest MariaDB instead of MySQL and update implementation.md as per the headers
present in ai-prompts/planning.md.

**AI response summary:**
Switched database backend from MySQL 8 to MariaDB: `.lando.yml` uses `mariadb:11.4`
(latest Lando-supported version); `.github/workflows/ci.yml` uses `mariadb:11` service
with MariaDB healthcheck env vars. Updated `README.md` database references. Restructured
`ai-prompts/implementation.md` to match planning log format (**Prompt**, **AI response
summary**, **Accepted**, **Changed**, **Rejected**).

**Accepted:**
MariaDB 11.x for local (Lando) and CI; implementation log follows planning.md headers.

**Changed:**
`.lando.yml`, `.github/workflows/ci.yml`, `README.md`, `ai-prompts/implementation.md`.

**Rejected:**
None.

## July 21, 2026 — M1b config-as-code

**Prompt:**
Implement M1b: Add config/install (bundle, fields, roles, comment type, /tickets
View, menus) + verify clean module enable.

**AI response summary:**
Added `config/install` for ticket bundle, five field storages/instances, Agent and
Reporter roles, and `views.view.tickets` at `/tickets` (table, filters, sort,
5/page). Comment support uses `config/optional` for shared core comment config
(compatible with Standard profile) plus ticket comment field instance. Primary
menu links via `support_ticket.links.menu.yml`. Updated `support_ticket.info.yml`
dependencies and extended `ModuleEnableTest` to assert bundle, fields, roles, and
view after install. Verified clean enable on fresh Lando site-install and kernel
test pass via `lando phpunit`.

**Accepted:**
M1b config-as-code — module enable installs data model; `/tickets` route active.

**Changed:**
`web/modules/custom/support_ticket/` (config/install, config/optional,
support_ticket.info.yml, support_ticket.links.menu.yml,
tests/src/Kernel/ModuleEnableTest.php).

**Rejected:**
None.
