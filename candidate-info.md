# Candidate Information

Name: Ushma Gupta / Role: Full Stack Developer / Primary Technology Stack: Drupal 10, PHP, MySQL/MariaDB, Twig, PHPUnit

Primary AI Tool Used: Cursor / Project Option Selected: Support Ticket Management System

Assessment Start Date: July 6, 2026 / Submission Date: TBD

## Project Summary

Internal **Support Ticket Management System** built as a Drupal 10 monolith (custom module + theme under `web/`). Session-based auth with three roles (Admin, Agent, Reporter); ticket lifecycle with manual assignment; comments; user CRUD; and a Views-driven ticket list with search, filters, sort, and pagination.

Architecture decision: fully server-rendered Drupal; no exposed REST/JSON:API layer.

## Tools Used

- **Cursor** — primary AI assistant for requirements, design docs, test strategy, implementation planning, and prompt logging (`ai-prompts/`)
- **Git** — version control; design iterations captured in commit history (e.g. two-pass `implementation-plan.md`)
- **Markdown** — all planning and design documentation
- **Lando** — local Drupal 10 + MariaDB runtime (see `README.md`)
- **GitHub Actions** — CI workflow (`.github/workflows/ci.yml`)
- **PHPUnit** — Kernel + Functional tests (per `test-strategy.md`)

## Setup Summary

- **Local runtime:** Lando — Drupal 10 + MariaDB (see `README.md`).
- **Application:** Custom module `support_ticket` and theme `support_ticket_theme` under `web/`; enable both after install.
- **Initial Admin:** Bootstrap via manual database setup (see `README.md`).
- **Secrets:** `.env` / `settings.local.php` (git-ignored); not committed to the repo.
