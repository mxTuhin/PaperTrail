# Project Brief — PaperTrail (Business Doc Formatter)

> **TL;DR** — A browser-first spreadsheet-to-PDF formatter. Files never leave the browser. Laravel is a thin host serving one Blade page + a usage counter endpoint. All heavy lifting happens in client-side JS.

---

## 1. Project Identity

| Field | Value |
|---|---|
| **Project Name** | PaperTrail |
| **App URL (local)** | `http://papertrail.test` (Laravel Herd) |
| **Purpose** | Drop in Excel/CSV/TSV → auto-detect column types → reorder/hide columns → apply company letterhead → print A4 PDF |
| **Key Promise** | "Your files never leave your device." |
| **Primary Author** | Tuhin |
| **Credit Footer** | `Built by Tuhin · © 2026` |

---

## 2. Architecture Philosophy

**Thin backend, thick client.** The server never sees spreadsheet data.

```
Browser (thick client)
  ├── SheetJS       → parse xlsx / xls / csv / tsv in-browser
  ├── Alpine.js     → reactivity (no build step, Blade-friendly)
  ├── SortableJS    → drag-to-reorder columns
  ├── Papa Parse    → robust CSV edge-case handling
  └── Paged.js      → (optional) real page numbers in print

Laravel (thin server)
  ├── GET  /        → landing page (Blade)
  ├── GET  /app     → the tool (Blade)
  └── POST /track   → bump usage counters only (SQLite)
```

The server only ever receives the `/track` ping — **never the file data.**

---

## 3. Technology Stack

### Backend
| Package | Version | Role |
|---|---|---|
| PHP | 8.4 | Runtime |
| Laravel Framework | v13 | Application skeleton |
| Laravel Tinker | ^3.0 | REPL / debugging |
| SQLite (via Laravel) | built-in | Usage counter store |

### Dev / Quality Tools
| Package | Version | Role |
|---|---|---|
| Laravel Boost | ^2.2 | MCP server / dev tools |
| Laravel Pail | ^1.2.5 | Log tail |
| Laravel Pint | ^1.27 | Code formatter (PSR-12 style) |
| PestPHP | ^4.7 | Testing framework (on top of PHPUnit v12) |
| Faker | ^1.23 | Test data generation |
| Mockery | ^1.6 | Test mocking |

### Frontend (Vite pipeline)
| Package | Version | Role |
|---|---|---|
| Vite | ^8.0.0 | Asset bundler |
| TailwindCSS | ^4.0.0 | Utility CSS |
| @tailwindcss/vite | ^4.0.0 | Tailwind Vite plugin |
| laravel-vite-plugin | ^3.1 | Laravel ↔ Vite bridge |
| concurrently | ^9.0.1 | Dev process manager |

### Frontend (CDN / committed libs — no build step)
| Library | Role |
|---|---|
| SheetJS (xlsx) | Parse xlsx/xls/csv/tsv in-browser |
| Alpine.js | Reactivity / state management |
| SortableJS | Drag-and-drop column reordering |
| Papa Parse (optional) | Robust CSV parsing fallback |
| Paged.js (optional) | Print page numbers + running headers |

### Typography
- **Instrument Sans** (400, 500, 600) — loaded via Bunny Fonts through the Vite plugin

---

## 4. Project Structure

```
papertrail/
├── .claude/
│   ├── v1-project-plan.md      ← Master product plan
│   ├── project-brief.md        ← This file
│   ├── steps/                  ← Step-by-step build guides
│   └── skills/                 ← Domain-specific agent skills
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Controller.php  ← Base controller (empty)
│   ├── Models/
│   │   └── User.php            ← Auth model (PHP 8 attribute syntax)
│   └── Providers/
├── config/                     ← Standard Laravel configs (10 files)
├── database/
│   ├── database.sqlite         ← Single-file DB (SQLite)
│   ├── factories/
│   ├── migrations/             ← 3 default Laravel migrations
│   └── seeders/
├── resources/
│   ├── css/
│   │   └── app.css             ← TailwindCSS v4 entry (@import 'tailwindcss')
│   ├── js/
│   │   └── app.js              ← JS entry (minimal, currently empty)
│   └── views/
│       └── welcome.blade.php   ← Default Laravel welcome page
├── routes/
│   ├── web.php                 ← Single route: GET / → welcome view
│   └── console.php
├── tests/
│   ├── Feature/
│   ├── Unit/
│   ├── Pest.php
│   └── TestCase.php
├── public/                     ← Web root (build assets land here)
├── vite.config.js              ← Vite + TailwindCSS + Bunny Fonts
├── composer.json
├── package.json
├── AGENTS.md / CLAUDE.md       ← Agent guidelines (identical)
└── .env                        ← SQLite DB, Herd URL, debug on
```

---

## 5. Current State (Baseline)

The project is a **fresh Laravel 13 skeleton** — nothing custom built yet.

| Area | Status |
|---|---|
| Routes | Only `GET /` → `welcome` view |
| Controllers | Only base `Controller.php` (no logic) |
| Models | Only `User.php` (default auth model) |
| Views | Only `welcome.blade.php` (default Laravel page) |
| Database | 3 default Laravel migrations, `database.sqlite` present |
| Frontend | `app.css` = Tailwind import + font token; `app.js` = empty |
| Tests | Pest configured, no custom tests yet |

---

## 6. Routes to Build

| Route | Purpose |
|---|---|
| `GET /` | **Landing page** — marketing, CTA, how-it-works, theme showcase, footer credit |
| `GET /app` | **The tool** — file drop zone, type detection, column manipulation, letterhead, print |
| `POST /track` | **Usage counter** — receive event metadata, write to SQLite, return 204 |
| `GET /admin` | **Admin view** — basic-auth protected, totals + recent log |

---

## 7. Core Features to Build

### 7.1 File Ingestion (Client-Side)
- Drag-and-drop + click-to-open file picker
- SheetJS reads xlsx/xls/csv/tsv **in browser** (never uploaded)
- Multi-sheet workbook → sheet picker
- Skip-rows control + auto-detect header row (scan first ~10 rows)
- Bengali/UTF-8 BOM handling for CSV

### 7.2 Type Detection Engine
Sample-and-vote per column (≥0.9 match ratio = confident):

| Type | Detection Logic |
|---|---|
| Leading-zero code | `^0\d+$` → force **text** (invoice #, phone) |
| Boolean | `true/false/yes/no/y/n` in small distinct sets |
| Integer | Digit-only with optional thousand commas / accounting parens |
| Decimal | Float-formatted numbers |
| Currency | Leading/trailing symbols (৳, $, Tk) + number |
| Percentage | `^-?\d+(\.\d+)?%$` |
| Date | Pattern-matched against known BD + international formats |
| Datetime | Date + time component |
| Text | Fallback — always matches |

- Confidence flag for low-confidence (mixed) columns
- Manual override: dropdown on each column header
- Auto-alignment: numbers/dates right, text left

### 7.3 Table Manipulation
- Show/hide columns (checkbox list)
- Drag-reorder columns (SortableJS)
- Rename column display labels
- Sort rows by any column (asc/desc)
- Summary row (totals/average) for numeric columns
- Optional: row filtering by value/range

### 7.4 Company Letterhead / Pad
- Header fields: logo, company name, address, phone, doc title, date, "statement for"
- **Persistence: localStorage** (not cookies — logo base64 blows cookie limit)
- Multiple letterhead profiles (saved/switched via dropdown)
- Toggle letterhead on/off per print
- 6 letterhead layout designs:
  - Sidebar band, Split header, Ribbon, Minimal serif, Seal/monogram, Centered (default)

### 7.5 Design System & Themes
- CSS custom properties token set: `--bg`, `--surface`, `--accent`, `--accent-2`, `--ink`, `--radius`, `--shadow`
- 7 built-in themes: Indigo (default), Emerald, Sunset, Mono Pro, Ocean, Oxblood, Grape
- Dark mode for app UI (print output stays white)
- Custom accent color picker → saved to localStorage
- 5 table styles: Clean, Ruled, Boxed, Striped, Shaded-header
- Live preview: theme change instantly re-skins UI + letterhead accent

### 7.6 Print / PDF
- `window.print()` + print CSS → "Save as PDF" in browser
- `@page { size: A4; margin: 15mm }`
- `thead { display: table-header-group }` (repeating headers)
- `tr { break-inside: avoid }` (no split rows)
- Portrait / Landscape toggle
- Optional Paged.js for page numbers + running headers

### 7.7 Usage Counter (Server-Side Only)
- `POST /track` → SQLite write: event type, timestamp, IP, user-agent, row/col count
- **Never** file contents — metadata only
- CSRF protected
- `GET /admin` → basic-auth, totals + recent log table

---

## 8. BD Localization Considerations
- Default date format: `DD/MM/YYYY`
- Default currency symbol: ৳
- Lakh/crore grouping option: `12,34,567` vs western `1,234,567`
- UTF-8 / Bengali text in CSV must not mojibake

---

## 9. Key Design Decisions (Already Made)

| Decision | Choice | Rationale |
|---|---|---|
| DB for files | None | Privacy — files never leave browser |
| Counter DB | SQLite file | Zero DB server, native Laravel support |
| Frontend reactivity | Alpine.js | No build step, tiny, Blade-compatible |
| CSS framework | TailwindCSS v4 | Already in project stack |
| File parsing | SheetJS | Handles xlsx/xls/csv/tsv client-side |
| PDF export | `window.print()` | Zero server dependency, works on cPanel |
| Persistence | localStorage | Larger than cookies, client-only |
| Routing | Two pages (`/` + `/app`) | Landing + tool separation |
| Fonts | Instrument Sans via Bunny | Already configured in Vite |

---

## 10. Build Phases

### Phase 1 — MVP (Get It Printing)
- File drop → SheetJS parse → sheet picker
- Type detection + alignment + manual override
- Show/hide + reorder columns
- Default letterhead (localStorage save)
- Browser print to A4

### Phase 2 — v1 (Make It Pleasant)
- Header-row / skip-rows detection
- Summary rows, row sort, column rename
- Multiple letterhead profiles
- Table styles, portrait/landscape
- Theme system (token-based) + 3–4 starter themes
- Landing page with footer credit
- `/track` counter + `/admin`

### Phase 3 — Nice-to-Have
- Paged.js page numbers/running headers
- BD lakh/crore formatting
- Export back to xlsx/csv
- Web Worker for large files
- Optional mPDF server PDF

---

## 11. Code Conventions

- **PHP**: 8.4, strict types, PHP 8 constructor property promotion, explicit return types
- **Models**: Use `#[Fillable]` / `#[Hidden]` PHP attributes (not `$fillable` array)
- **Tests**: Pest v4, feature tests preferred over unit
- **Formatter**: `vendor/bin/pint --dirty` after any PHP file change
- **Routes**: Named routes + `route()` helper for all links
- **Artisan**: Always `--no-interaction` flag
- **CSS**: TailwindCSS v4 utility classes, CSS custom properties for theming
- **JS**: Alpine.js for UI state, vanilla JS for logic, no framework build pipeline

---

## 12. Local Development

```bash
# Start everything (server + queue + logs + vite)
composer run dev

# Tests
php artisan test --compact

# Format PHP
vendor/bin/pint --dirty

# App URL
http://papertrail.test   (via Laravel Herd)
```

---

*Last updated: 2026-07-13 | Based on v1-project-plan.md*
