# Step 03 — Landing Page (`GET /`)

## Goal
Build a vibrant, modern marketing page that sells the tool's unique privacy angle and feature set. Two CTAs funnel users into `/app`.

## Route
`GET /` → `LandingController@index` → `resources/views/landing/index.blade.php`

## Sections to Build (in order)

### 1. Hero Section
- **Headline**: "Turn any spreadsheet into a print-ready business document — in your browser, nothing uploaded."
- **Sub-headline**: Privacy angle: "Your files never leave your device. No uploads, no servers, no risk."
- **CTA button**: "Open the Tool →" → links to `route('app')`
- **Animated preview**: CSS/JS animation showing a raw table transforming into a formatted A4 document (can be a before/after slide or animated mockup)
- Background: subtle mesh/aurora gradient using `--accent` colors

### 2. Trust Strip
Full-width band (no background), 3 trust badges inline:
- 🔒 "Zero uploads — data stays in your browser"
- ⚡ "No signup — works instantly"
- 🖨️ "Clean A4 PDF in one click"

### 3. Feature Strip
4-column icon + label cards:
1. Auto type-detection
2. Drag-to-reorder columns
3. Company letterhead / multiple profiles
4. One-click A4 print

### 4. How It Works (3 Steps)
Numbered horizontal steps with icons:
1. **Drop** — drag your Excel/CSV file in
2. **Arrange** — pick columns, fix labels, choose your letterhead
3. **Print** — click print, save as PDF

### 5. Theme Showcase
A grid of 3–4 cards, each showing the same sample table rendered in a different theme (Indigo, Emerald, Sunset, Oxblood). Clicking a card switches the card's active state — this is a pure CSS/Alpine demo, not functional.

### 6. Privacy Deep-Dive Block
- Larger callout section
- Explain the technical truth: SheetJS reads the file in the browser, server only sees a usage ping
- "Even we can't see your data — because it never arrives."

### 7. Footer
```
PaperTrail · Built by Tuhin · © 2026
[optional: GitHub link]
```

## Design Notes
- Font: Instrument Sans (already loaded)
- Colors: Use CSS custom property tokens from Step 02 (`var(--accent)`, `var(--surface)`, etc.)
- TailwindCSS v4 utilities for layout/spacing
- Hero background: `background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 12%, var(--bg)), var(--bg))`
- Use `@starting-style` + `transition` for entry animations if supported
- All interactive bits (theme card toggle) use `x-data` Alpine components

## Checklist
- [ ] `LandingController@index` returns `landing.index` view
- [ ] Hero section with animated preview placeholder
- [ ] Trust strip (3 badges)
- [ ] Feature strip (4 cards)
- [ ] How-it-works (3 steps)
- [ ] Theme showcase (4 cards, Alpine toggle)
- [ ] Privacy callout block
- [ ] Footer with credit
- [ ] Fully responsive (mobile stacks to 1-col)
- [ ] Links to `/app` use `route('app')` named route
- [ ] Page title tag: "PaperTrail — Browser-First Business Doc Formatter"
- [ ] Meta description: "Turn any spreadsheet into a print-ready A4 PDF. Files never leave your browser. Free, fast, private."
