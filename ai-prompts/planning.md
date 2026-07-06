# AI Prompts — planning
## july 6 — Requirement analysis for Support Ticket Management System

**Prompt:**
Read .cursor/rules/project-context.md.
Fill requirements-analysis.md using its existing section headers exactly:
## Selected Project Option / ## My Understanding / ## Functional Requirements /
## Non-Functional Requirements / ## Assumptions / ## Clarifications / ## Edge Cases
Do not restructure the template. Do not generate code.

**AI response summary:**
Cursor asked clarifying questions about comment edit rights, assignee role
restriction, and cancelled-ticket visibility before drafting. After I answered,
it filled in requirements-analysis.md following the existing template headers.

**Accepted:**
All sections like Functional requirements, Non functional requirements, clarifications, edge cases section

**Commit:**

## july 6 — Architecture decision: Drupal-only monolith

**Prompt:**
I have decided to keep the entire setup in Drupal only — both frontend and backend.
Update all associated files.

**AI response summary:**
Updated project-context.md (all copies), requirements-analysis.md, README.md,
design-notes.md, test-strategy.md, ui-flow.md, and .gitignore to remove React/Vite
decoupled frontend references. Stack is now Drupal 10 custom module + custom theme
under src/, with Drupal-rendered UI (Forms, Twig, Views) and session auth only.

**Accepted:**
Drupal-only architecture across all project documentation.

**Commit:**

## july 6 — Assignment logic and ticket type field updates

**Prompt:**
Apply assignment and type field updates: tickets created unassigned, manual
assignment by Admin/Agent only, self-assign allowed, Reporter cannot see/set
assignedTo, no auto-assignment. Ticket type (Technical/Billing/Account/General)
is categorization/filter only—assumption/addition, not original brief.

**AI response summary:**
Updated requirements-analysis.md and all project-context.md copies. Removed User
type field and auto-assignment logic. Added Assignment section, flagged ticket
type as assumption/addition, rewrote FRs/assumptions/edge cases.

**Accepted:**
Manual assignment rules and ticket type as categorization-only across project docs.

**Commit:**

## july 6 — Acceptance criteria from requirements analysis

**Prompt:**
Read requirements-analysis.md — it's now finalized with both cited requirements
and clearly marked assumptions. Fill acceptance-criteria.md using existing section
headers (## Core / ## Validation / ## Error Handling / ## Testing / ## Documentation).
Every checklist item should trace back to a specific requirement in that file.
Don't invent new scope; ask about gaps instead of guessing.

**AI response summary:**
Filled acceptance-criteria.md with 66 checklist items across five sections, each
traced to FR-*, NFR-*, A-*, or EC-* IDs from requirements-analysis.md. Flagged
open item C-2 (comment deletion) — asked whether delete is out of scope, author-only,
or admin-capable before adding criteria.

**Accepted:**
Core, Validation, Error Handling, Testing, and Documentation sections populated
with FR-traced checklist items.

**Commit:**

## July 6, 2026 — Resolved C-1, added missing assumptions

**Prompt:**
Make corrections to requirements-analysis.md in order: (1) resolve C-1 — Agent
may transition tickets assigned to them and unassigned tickets in the global queue,
but not tickets assigned to another Agent; update FR-29 and A-4, remove C-1 from
open items; (2) add A-11 (Admin ticket deletion as addition) and A-12 (user
deletion blocking rules as addition), following A-1 format; (3) log this in
ai-prompts/planning.md.

**AI response summary:**
Updated requirements-analysis.md: FR-29 and A-4 now explicitly state Agent
transition scope; C-1 removed from open items and recorded in the resolved
clarifications table; A-11 (Admin-only ticket deletion) and A-12 (user deletion
blocking rules) added as additions not cited from the source brief.

**Accepted:**
C-1 resolved in FR-29, A-4, and clarifications table; A-11 and A-12 added;
planning log entry at bottom of ai-prompts/planning.md.

**Changed:**
C-1 was previously left in the "Open items" list while acceptance-criteria.md
already cited Agent transition scope as settled fact (FR-29, A-4) — this
inconsistency was caught during manual review, not generated correctly the first
time.

**Rejected:**
None.

**Commit:**