# PaperTrail — Build Steps

Each file in this directory is a self-contained, implementation-ready guide for one phase of the build. Work through them in order, or jump to any specific area.

## Step Index

| Step | File | Phase | What It Builds |
|------|------|-------|----------------|
| 01 | [step-01-laravel-scaffold.md](step-01-laravel-scaffold.md) | MVP | Routes, controllers, layouts, migration scaffold |
| 02 | [step-02-design-system.md](step-02-design-system.md) | MVP | CSS token system, 7 themes, dark mode, 5 table styles, print CSS |
| 03 | [step-03-landing-page.md](step-03-landing-page.md) | v1 | Marketing landing page at `GET /` |
| 04 | [step-04-file-ingestion.md](step-04-file-ingestion.md) | MVP | File drop, SheetJS parsing, type detection engine |
| 05 | [step-05-table-ui.md](step-05-table-ui.md) | MVP | Column panel, preview table, sort, rename, summary row |
| 06 | [step-06-letterhead.md](step-06-letterhead.md) | v1 | Letterhead profiles, 6 layouts, localStorage persistence |
| 07 | [step-07-backend-counter.md](step-07-backend-counter.md) | v1 | `POST /track`, `GET /admin`, UsageEvent model |
| 08 | [step-08-print-pdf.md](step-08-print-pdf.md) | MVP | Print CSS, WYSIWYG A4 preview, landscape toggle |
| 09 | [step-09-bd-localization.md](step-09-bd-localization.md) | v1 | Lakh/crore numbers, ৳ Taka, DD/MM/YYYY, Bengali CSV |
| 10 | [step-10-testing.md](step-10-testing.md) | v1 | Pest feature/unit tests for server endpoints |

## Recommended Build Order

### Phase 1 — MVP (core functionality)
1. **Step 01** → scaffold routes + layouts
2. **Step 02** → design tokens + themes
3. **Step 04** → file parsing + type detection
4. **Step 05** → table UI
5. **Step 08** → print/PDF

### Phase 2 — v1 (complete product)
6. **Step 03** → landing page
7. **Step 06** → letterhead
8. **Step 07** → usage counter + admin
9. **Step 09** → BD localization
10. **Step 10** → tests

## Key Principles (reminders)

- **Files never leave the browser.** The server receives only a usage ping from `POST /track`.
- **Alpine.js** handles all UI state — no Vue/React/build pipeline needed.
- **CSS custom properties** (`--accent`, `--surface`, etc.) power the theme system.
- **localStorage** stores letterhead profiles, themes, and settings — never cookies.
- **SQLite** is the only database (already configured in `.env`).
- Run `vendor/bin/pint --dirty` after every PHP file change.
- Use `php artisan make:` for all new PHP files.

## Reference

See [project-brief.md](../project-brief.md) for the complete project overview.
See [v1-project-plan.md](../v1-project-plan.md) for the original product design decisions.
