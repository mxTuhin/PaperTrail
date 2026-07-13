# Business Doc Formatter — Plan

A browser-first tool: drop in Excel/CSV/TSV, auto-detect column types, reorder/hide columns, slap on a company letterhead, print to A4 PDF. No file ever leaves the browser. Server exists only to count usage.

---

## 1. Core architecture decision: thin backend, thick client

The natural shape of this app is **client-side everything**. Your files are read and processed in the browser via JS. The server never sees a file.

Why this is better than "upload → server processes":
- **No storage, no DB for files** — matches your goal, but stronger. There's literally nothing to save.
- **Privacy** — sensitive sales/financial data stays on the machine. Nothing to leak.
- **Cheap on shared cPanel** — no heavy parsing load on the server, no big PHP libraries, no memory limits blown by a 50k-row xlsx.
- **Printing is a browser thing anyway** — `window.print()` + print CSS is how you get clean A4 without wkhtmltopdf (which is usually missing on shared hosting).

Laravel's job shrinks to: serve one Blade page + assets, and expose one `POST /track` endpoint that bumps a counter. That's it.

> You *could* skip Laravel entirely (static HTML + one tiny PHP file for the counter). But since your ERP is already Laravel and cPanel deploy is muscle memory for you, staying on Laravel is the low-friction choice. Just know the backend is ~50 lines.

---

## 2. Stack

| Layer | Choice | Why |
|---|---|---|
| Backend | Laravel (thin) | cPanel-friendly, your existing stack |
| Counter store | SQLite file | Zero DB server, single file, Laravel supports it natively. (Flat JSON log is the truly-zero-DB fallback if you're a purist, but SQLite handles concurrent writes better.) |
| Frontend reactivity | **Alpine.js** | No build step, drops into Blade, tiny. Perfect for cPanel. (You know React, but this is one page — a build pipeline is overkill.) |
| Spreadsheet parsing | **SheetJS (xlsx)** | Reads xlsx/xls/csv/tsv in-browser |
| CSV/TSV edge cases | **Papa Parse** (optional) | More robust pure-CSV parsing than SheetJS if you hit weird delimiters/quoting |
| Column drag reorder | **SortableJS** | Drag-and-drop, no framework lock-in |
| Print pagination (optional) | **Paged.js** | Gives real page numbers + repeating headers in browser print, which native print CSS can't do cleanly |

No node service, no Express, no build tooling required. All frontend libs load from CDN or a committed `/public/js` folder.

---

## 3. Data flow

```
User drops file
  → SheetJS reads it in-browser (never uploaded)
  → detect sheets → pick sheet
  → detect header row → detect column types
  → render editable preview table
  → user tweaks (hide/reorder cols, sort rows, rename headers, toggle letterhead)
  → Print → browser print dialog → Save as PDF
  → (async) ping POST /track to bump counters
```

The server only ever receives the `/track` ping. Never the data.

---

## 4. Type detection engine (the heuristic core)

Column-based, sample-and-vote. Per column:

1. **Sample** non-empty values (cap ~200 for speed on big files).
2. **Trim** whitespace.
3. **Run ordered matchers**, tally match ratio for each type:

| Type | Matcher notes |
|---|---|
| Leading-zero code | `^0\d+$` → **force text** (invoice #, account codes, phone). *This is the #1 Excel gotcha — don't let these become numbers.* |
| Boolean | `true/false/yes/no/y/n` — only if column is a small distinct set of these (avoid stealing 0/1 from integers) |
| Integer | `^-?\d{1,3}(,\d{3})*$` or `^-?\d+$`, plus accounting parens `(1,234)` = −1234 |
| Decimal | `^-?[\d,]*\.\d+$` |
| Currency | leading/trailing symbol (৳, $, Tk) + number |
| Percentage | `^-?\d+(\.\d+)?%$` |
| Date | try known formats, record which matched most (see §5 locale) |
| Datetime | date + time component |
| Text | fallback — always matches |

4. **Decide**: pick the most *specific* type whose match ratio ≥ threshold (~0.9). Text is the always-match fallback.
5. **Confidence** = match ratio. Flag low-confidence columns (mixed data) in the UI for review.
6. **Store per column**: detected type, the format string, and derived **alignment** (numbers/dates right, text left) — this alone makes output look "business-grade" for free.
7. **Manual override**: every column header shows its detected type as a dropdown. Change it, formatting + alignment re-apply. Low-confidence ones get a subtle highlight so you know where to look.

Default formatting that flows from type (all overridable):
- Numbers → thousand separators
- Currency → symbol + 2 decimals
- Dates → one consistent format
- Negatives → red or parens (accounting toggle)

---

## 5. Messy real-world files (the part most tools get wrong)

Business Excel files are rarely clean tables from row 1. Handle:

- **Title/blank rows above the table** → "Skip first N rows" control + auto-guess (scan first ~10 rows: header row is usually the first mostly-text row that has type-consistent data below it).
- **"Header is row N" selector** with best-guess default.
- **Multi-sheet workbooks** → sheet picker, default to first non-empty sheet.
- **Encoding / Bengali text** → CSV can be UTF-8 (with/without BOM) or legacy encodings. Bangla content will mojibake if mishandled. SheetJS handles xlsx fine; for raw CSV, detect BOM and default UTF-8, offer an encoding override.
- **Merged cells** → SheetJS flattens; just be aware the preview may show blanks where merges were.

---

## 5b. Landing page

A proper marketing/entry page, not just a bare tool. Two routes: `/` = landing, `/app` = the tool.

- **Hero**: one line on what it does ("Turn any spreadsheet into a print-ready business document — in your browser, nothing uploaded"), a big **"Open the tool"** CTA, and a live/animated preview of a sheet becoming a typeset A4 page.
- **Trust angle** (your strongest selling point): "Your files never leave your device." Say it loud — it's rare and it matters for financial data.
- **Feature strip**: auto type-detection · drag columns · letterheads · themes · one-click A4 print.
- **How it works** in 3 steps (drop → arrange → print).
- **Theme showcase**: a few frames showing the same document across different themes (sells §8 instantly).
- **Vibrant, modern styling** consistent with the app themes — this page sets the tone.
- **Footer with your credit**: `Built by Tuhin · © 2026` (link to your site/GitHub if you want). Present on landing *and* app footer.
- Fully static, so it's cheap and fast on cPanel.

---

## 6. Table manipulation features

- Show/hide columns (checkbox list)
- Drag-reorder columns (SortableJS)
- Rename column display labels (raw CSV headers are ugly; business docs need clean labels)
- Sort rows by any column, asc/desc
- **Summary row** (totals/subtotals/average) at the bottom for numeric columns — *standard in sales statements, you didn't mention it but you'll want it*
- (optional) Row filtering — hide rows by value/range

All non-destructive: you're editing a *view*, the parsed data underneath is untouched, so you can reset anytime.

---

## 7. Company letterhead / pad + persistence

- Header block: logo, company name, address, phone, doc title, date, "statement for" line, etc. — all optional fields, sensible defaults.
- **Persistence = localStorage, not cookies.** Cookies max out ~4KB (a base64 logo blows past that instantly) and get sent on every request for no reason. localStorage is client-only, bigger, and persists until cleared — better than your "1 year cookie." Keep the explicit **"Save this letterhead"** toggle you wanted; it just writes to localStorage.
- Logo stored as base64 — warn/compress if large.
- **Multiple letterhead profiles.** You said "family *businesses"* (plural) — let the user save several pads and switch between them via a dropdown. Small addition, big daily payoff.
- Toggle to enable/disable the pad on the printed output.
- **Non-typical letterhead designs** (not the boring centered-logo default). Ship a gallery of layouts with real character, each still print-clean on A4:
  - **Sidebar band** — a full-height colored/tinted strip down the left edge with company name rotated or stacked; table sits in the remaining width.
  - **Split header** — company block left, a monospaced meta stack right (BIN, volume, date) with a bold accent rule between (this is what the mockup uses).
  - **Ribbon** — a full-width accent band across the top with reversed (light-on-color) company name.
  - **Minimal serif** — no logo, just an oversized typeset wordmark and a hairline, for a refined "law-firm" feel.
  - **Seal/monogram** — circular monogram mark + address, editorial.
  - Each layout reads the active **theme** (below) for its accent, so a letterhead + theme combo gives dozens of looks from a few primitives.

---

## 8. Design system + themes (vibrant, modern — not old-school)

The first mockup leaned muted/stationery. **Ditch the old-school grayscale feel.** Two separate surfaces, styled differently:

**A) App UI (the tool itself) — vibrant, modern, confident.**
- Built on **CSS custom properties** so the whole UI re-skins from one token set (`--bg`, `--surface`, `--accent`, `--accent-2`, `--ink`, `--radius`, `--shadow`).
- Modern touches: soft gradients or a subtle mesh/aurora background, rounded cards, generous whitespace, real depth (layered shadows), springy micro-interactions, a crisp modern sans (Geist / Plex Sans / Satoshi).
- Colorful but controlled — one strong accent + one secondary, not a rainbow. Vibrant ≠ noisy.

**B) The A4 document — always stays print-clean.** Themes tint the letterhead accent, rules, and header shading, but the paper body stays high-contrast and readable. A neon UI must never produce an unreadable statement.

**Many page themes (the part you asked for).** Ship a **theme gallery**, switchable in one click, each a full token set:

| Theme | Vibe | Accent direction |
|---|---|---|
| Indigo (default) | Modern SaaS, trustworthy | electric indigo + violet |
| Emerald | Fresh, finance-positive | emerald + teal |
| Sunset | Warm, energetic | coral → amber gradient |
| Mono Pro | Sharp editorial | near-black + one vivid pop |
| Ocean | Calm corporate | deep blue + cyan |
| Oxblood | Classic ledger (the mockup) | oxblood + stone |
| Grape | Bold, distinctive | purple + magenta |

- **Live preview** — switching theme instantly re-skins UI + letterhead accent.
- **Custom accent** — a color picker so a business can match its brand exactly; custom themes save to localStorage alongside letterhead profiles.
- **Table styles** (independent of theme): Clean · Ruled · Boxed · Striped · Shaded-header.
- **Dark mode** for the app UI (paper preview stays white).
- Everything ships with a working default (Indigo). Customization is opt-in and never blocks fast output.

---

## 9. Print / A4 / PDF

- **Primary path**: `window.print()` + print CSS → user picks "Save as PDF". Zero server dependency.
- Print CSS: `@page { size: A4; margin: 15mm }`, `thead { display: table-header-group }` so headers repeat on every page, `tr { break-inside: avoid }` so rows don't split.
- **Portrait/Landscape toggle** for wide tables.
- **Print preview = the actual on-screen view** (WYSIWYG), so no surprises.
- **Page numbers + running headers**: native print CSS can't do these reliably. If you want them, **Paged.js** (client-side) solves it.
- *Optional* server PDF via **mPDF** (composer, works on shared hosting) only if you ever need headless/batch PDF. Skip for MVP — browser print covers 95%.
- (optional) Export modified table back to Excel/CSV, not just print.

---

## 10. Counter + logging (the only server code)

`POST /track` with `{ event: upload|process|print }` → write a row to SQLite:

- event type, timestamp, IP, user-agent
- file *metadata* only: row count, col count, maybe filename — **never contents**
- simple `/admin` view (basic-auth) showing totals + recent log

CSRF-protect the endpoint. Low stakes since it's a family tool, but don't leave it fully open if the URL is public.

---

## 11. Things you missed / worth adding

1. **Leading-zero / ID columns → force text** (invoice, account, phone). Biggest correctness win.
2. **BD localization defaults**: `DD/MM/YYYY` dates, ৳ currency, and lakh/crore grouping (`12,34,567`) as an option alongside western `1,234,567`.
3. **Summary/total rows** for sales statements.
4. **Multiple letterhead profiles** for your multiple businesses.
5. **localStorage over cookies** (see §7).
6. **Header-row / skip-rows detection** for messy Excel (§5).
7. **Multi-sheet picker.**
8. **Bengali/encoding handling** for CSV.
9. **Portrait/landscape + repeating headers** for print.
10. **Column rename** to clean labels.
11. **Big-file safety**: parse in a Web Worker (or cap rows) so a huge xlsx doesn't freeze the tab.

---

## 12. Build phases

**MVP (get it printing):**
- File drop → SheetJS parse → sheet picker
- Type detection + alignment + manual override
- Show/hide + reorder columns
- Default letterhead (localStorage save)
- Browser print to A4

**v1 (make it pleasant):**
- Header-row/skip-rows detection
- Summary rows, row sort, column rename
- Multiple letterhead profiles
- Table styles, portrait/landscape
- **Theme system (token-based) + 3–4 starter themes**
- **Landing page with your footer credit**
- `/track` counter + `/admin`

**Nice-to-have:**
- Paged.js page numbers/running headers
- BD lakh/crore formatting
- Export back to xlsx/csv
- Web Worker for large files
- Optional mPDF server PDF

---

## 13. Open decisions for you

- **SQLite vs flat JSON** for the counter — SQLite recommended, flat file if you want literally zero DB.
- **Log filename or not?** (metadata, low risk, sometimes useful.)
- **Paged.js worth it?** Only if you actually need page numbers in the PDF.
- **Multi-business from day one, or single letterhead first?**
- **Bengali content expected?** If yes, encoding handling moves up to MVP.
