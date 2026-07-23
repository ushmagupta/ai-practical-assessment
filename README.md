# AI Practical Assessment — Support Ticket Management System

Drupal 10 monolith for internal support ticket management. Custom module and theme live under `web/modules/custom/` and `web/themes/custom/`.

## Prerequisites

- [Lando](https://docs.lando.dev/) (recommended local runtime)
- PHP 8.3+ and Composer (if not using Lando tooling)
- Git

## Quick start (Lando)

```bash
# Start containers and install PHP dependencies
lando start
lando composer install

# Install Drupal (standard profile, site name: Support Ticket System)
lando site-install

# Enable custom module and theme
lando drush en support_ticket -y
lando drush theme:enable support_ticket_theme -y
lando drush config:set system.theme default support_ticket_theme -y

# Open the site
lando info
```

Default local URL is shown by `lando info` (typically `https://support-ticket.lndo.site`).

## Database setup

Lando provisions MariaDB automatically. Credentials are injected into the appserver; no manual DB creation is required for local development.

For non-Lando setups, copy `.env.example` to `.env` and configure your database connection in `web/sites/default/settings.php` or `settings.local.php`.

## Running locally (without Lando)

```bash
composer install
cp web/sites/default/default.settings.php web/sites/default/settings.php
# Edit settings.php with your database credentials, then:
vendor/bin/drush --root=web site:install standard \
  --site-name="Support Ticket System" \
  -y
vendor/bin/drush --root=web en support_ticket -y
vendor/bin/drush --root=web theme:enable support_ticket_theme -y
vendor/bin/drush --root=web config:set system.theme default support_ticket_theme -y
```

## Admin bootstrap (FR-10)

The first Admin account is created during `site:install`:

| Setting | Lando default | CI default |
|---------|---------------|------------|
| Site name | `Support Ticket System` | `Support Ticket System` |
| Username | `admin` | `admin` |
| Password | `admin` | `admin` |

**Change the password immediately** after first login in any non-throwaway environment.

For production or shared environments, create the Admin via install with explicit credentials:

```bash
lando drush site:install standard \
  --db-url=mysql://drupal10:drupal10@database/drupal10 \
  --site-name="Support Ticket System" \
  --account-name=YOUR_ADMIN \
  --account-pass=YOUR_SECURE_PASSWORD \
  -y
```

Never commit real credentials. Use `.env` / `settings.local.php` (both git-ignored).

## Secrets handling

- `.env` — local overrides (see `.env.example`); not committed
- `web/sites/default/settings.local.php` — Drupal settings overrides; not committed
- GitHub Actions uses ephemeral test credentials only

## Running tests

Prepare the Simpletest browser output directory once (Lando or local):

```bash
mkdir -p web/sites/simpletest/browser_output && chmod 777 web/sites/simpletest/browser_output
```

```bash
# Fast feedback — Kernel only (~2 min)
lando phpunit --testsuite kernel

# HTTP smoke tests (~4 min)
lando phpunit --testsuite smoke

# Full suite — Kernel + smoke (~6 min)
lando phpunit

# Without Lando (requires composer install)
vendor/bin/phpunit -c phpunit.xml --testsuite kernel
vendor/bin/phpunit -c phpunit.xml --testsuite smoke
vendor/bin/phpunit -c phpunit.xml
```

Smoke tests use the `standard` profile with merged HTTP flows in `TicketSmokeFunctionalTest.php`. Front page routing is applied by `support_ticket_install()`.

Kernel smoke test: `web/modules/custom/support_ticket/tests/src/Kernel/ModuleEnableTest.php`.

## CI

GitHub Actions workflow (`.github/workflows/ci.yml`) runs on push/PR to `main`:

1. `composer install`
2. `drush site:install standard`
3. Enable `support_ticket` module and `support_ticket_theme`
4. PHPUnit (Kernel + smoke suites)

## Project layout

```
composer.json          # Drupal 10 recommended-project
web/                   # Document root
  modules/custom/support_ticket/
  themes/custom/support_ticket_theme/
.lando.yml             # Local dev (PHP 8.3, MariaDB 11.4)
phpunit.xml            # Kernel test configuration
.github/workflows/ci.yml
```

Planning and design docs remain at the repo root (`requirements-analysis.md`, `data-model.md`, etc.).

## Milestone status

**M1–M5:** Config-as-code, domain services, Drupal integration, end-to-end flows, theme polish.

**M6 (ship-ready):** Complete — 38 tests green in CI; acceptance walkthrough in `test-results.md`; submission docs updated (`candidate-info.md`, `final-ai-usage-summary.md`).
