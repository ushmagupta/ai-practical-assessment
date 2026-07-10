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

## July 6, 2026 — Documented architecture deviation: no API layer exposed

**Prompt:**
Record architecture deviation across requirements-analysis.md, api-contract.md,
and acceptance-criteria.md: Drupal monolith only, no REST/JSON:API or OpenAPI;
add Architecture Deviation section after Selected Project Option; mark FR-48 and
FR-50 OUT OF SCOPE in place; replace api-contract.md with N/A placeholder;
descope FR-48/FR-50 acceptance criteria lines with reason. Do not touch
design-notes.md, ui-flow.md, or data-model.md.

**AI response summary:**
Added "Architecture Deviation from Source Document" section to
requirements-analysis.md; marked FR-48 and FR-50 OUT OF SCOPE in the Functional
Requirements list; replaced api-contract.md with placeholder pointing back to
requirements-analysis.md; converted FR-48 and FR-50 acceptance criteria from
checklist items to descoped notes. Fixed accidental line break in acceptance-criteria.md.

**Accepted:**
Architecture deviation documented across requirements-analysis.md,
api-contract.md, and acceptance-criteria.md; FR-48/FR-50 history preserved as
OUT OF SCOPE markers.

**Changed:**
Deliberate scope decision made after discussion — directed by me, not an
AI-driven change.

**Rejected:**
None.

## July 6, 2026 — Corrected and removed JSON error shape, now describes Drupal form/page-based error handling

**Prompt:**
Remove JSON error shape from requirements-analysis.md (FR-49) and replace with
Drupal Form API inline validation + access-denied/error pages; update
acceptance-criteria.md for JSON/API error response references; append planning log.

**AI response summary:**
Rewrote FR-49 and NFR-4 in requirements-analysis.md for Drupal-only error handling
(inline form errors, access-denied pages). Updated acceptance-criteria.md Error
Handling section — removed JSON error shape and API error response checklist items;
replaced with Drupal form validation and access-denied criteria (FR-49, FR-27,
FR-19, FR-29, FR-3, NFR-4, EC-* citations preserved).

**Accepted:**
FR-49 and NFR-4 describe Drupal form/page-based errors only; acceptance-criteria
Error Handling section aligned; JSON error shape removed entirely.

**Changed:**
Leftover from earlier API-based draft, caught during review after the architecture
deviation was documented — not something the AI flagged on its own.

**Rejected:**
None.

## July 7, 2026 — Reworded remaining API-response language to Drupal-native terms

**Prompt:**
Reword leftover API language in requirements-analysis.md (FR-3, FR-17, NFR-4,
EC-1–EC-16) to Drupal-native terms; terminology only, no behavior changes.

**AI response summary:**
Updated FR-3, FR-17, NFR-4, and all Edge Cases with 403/4xx/API wording replaced
by Drupal Form API validation errors, AccessResult::forbidden() / access-denied
pages, and rendered page output. Also reworded FR-19, FR-27, and FR-45 for
consistency.

**Accepted:**
All API-response terminology in requirements-analysis.md replaced with
Drupal-native equivalents; no rule or behavior changes.

**Changed:**
Additional leftover API language found beyond the earlier FR-49 fix, caught via a
full manual pass rather than generated correctly on the first architecture-deviation
pass.

**Rejected:**
None.

## July 10, 2026 — Resolved open design items C-2 and ISS-4 through ISS-8

**Prompt:**
Implement the resolve-open to-do: close C-2 and ISS-4 through ISS-8 before
implementation; fix stale API wording (ISS-1, ISS-2). Do not edit the plan file.

**AI response summary:**
Resolved all pre-implementation open items in requirements-analysis.md and
acceptance-criteria.md:

- **C-2:** Comment deletion out of scope; author edit-only (A-13).
- **ISS-4:** Concurrent modification via Drupal form token + `field_ticket_status`
  re-check on submit (NFR-6, EC-13).
- **ISS-5:** No comment edits on Closed/Cancelled tickets (FR-33, FR-34).
- **ISS-6:** Keyword search matches title and description (FR-36, A-14).
- **ISS-8:** Terminal tickets viewable read-only; server denies writes (FR-22,
  A-15).
- **ISS-1:** acceptance-criteria.md — "API responses" → "rendered page output".
- **ISS-2:** Clarifications table — API row updated to architecture deviation.

Removed the "Open items for confirmation" section from requirements-analysis.md.

**Accepted:**
All open items closed with documented decisions before implementation phase.

**Changed:**
None.

**Rejected:**
None.

## July 10, 2026 — Created implementation-plan.md

**Prompt:**
Read all finalized design docs and test-strategy.md as sole source of truth. Fill
implementation-plan.md keeping existing template headers; turn design into a
build-and-verify schedule with every build task paired to test-strategy
verification. Show full content before approval.

**AI response summary:**
Drafted initial implementation-plan.md (~200 lines): 11 milestones (M0–M10),
~45 FR/EC-cited build→verify task rows, per-milestone AI table, 10-item risks
matrix with mitigations.

**Accepted:**
Initial draft committed as first pass (verbose traceability-matrix style).

**Changed:**
None in first commit.

**Rejected:**
None.

## July 10, 2026 — Revised implementation-plan.md

**Prompt:**
Challenged initial draft: too long and granular — duplicated FR/EC citations and
architecture rationale already in other docs. Requested 5–6 milestones, grouped
checklists, 4–5 one-line risks, one-paragraph AI plan, unambiguous logging
instruction. Resolved empty-state scope: themed empty-state messages in scope;
custom breadcrumbs out of scope.

**AI response summary:**
Condensed implementation-plan.md (~100 lines): 6 milestones with one-line goal
and done-check; task breakdown as named checklists (state machine, assignment,
role visibility, etc.); build paired with test-strategy verification without
per-row FR citations; logging split (planning.md vs implementation.md); empty-state
in, breadcrumbs out.

**Accepted:**
Revised `implementation-plan.md` approved — ready to begin M1 implementation.

**Changed:**
Reduced from 11 milestones / ~45 cited rows to 6 milestones / grouped checklists;
removed duplicate traceability and per-milestone AI table; resolved presentation
scope (empty-state yes, breadcrumbs no).

**Rejected:**
Initial verbose format (FR/EC matrix per task, 10-milestone granularity, defer-if-
time-constrained language for empty-state).
