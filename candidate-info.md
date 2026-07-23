# Candidate Information

Name: Ushma Gupta / Role: Full Stack Developer / Primary Technology Stack: Drupal 10, PHP, MySQL/MariaDB, Twig, PHPUnit

Primary AI Tool Used: Cursor / Project Option Selected: Support Ticket Management System

Assessment Start Date: July 6, 2026 / Submission Date: July 23, 2026

## Project Summary

Internal **Support Ticket Management System** built as a Drupal 10 monolith (custom module + theme under `web/`). Session-based auth with three roles (Admin, Agent, Reporter); ticket lifecycle with manual assignment; comments; user CRUD; and a Views-driven ticket list with search, filters, sort, and pagination.

Architecture decision: fully server-rendered Drupal; no exposed REST/JSON:API layer.

## Tools Used

- **Cursor** — primary AI assistant for requirements, design docs, test strategy, implementation planning, debugging, code review, and prompt logging (`ai-prompts/`)
- **Git** — version control; design iterations captured in commit history
- **Markdown** — all planning and design documentation
- **Lando** — local Drupal 10 + MariaDB runtime (see `README.md`)
- **GitHub Actions** — CI workflow (`.github/workflows/ci.yml`)
- **PHPUnit** — Kernel + smoke HTTP tests (see `test-results.md`)

## Setup Summary

- **Local runtime:** Lando — Drupal 10 + MariaDB (see `README.md`).
- **Application:** Custom module `support_ticket` and theme `support_ticket_theme` under `web/`; enable both after install.
- **Initial Admin:** Bootstrap via `lando site-install` (see `README.md` Admin bootstrap).
- **Secrets:** `.env` / `settings.local.php` (git-ignored); not committed to the repo.
- **Tests:** `lando phpunit` — 38 tests (~6 min); see `test-results.md` for acceptance mapping.
