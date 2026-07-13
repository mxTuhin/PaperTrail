# Step 02 — Design System & Theme Engine

## Goal
Build a production-quality, token-driven CSS design system with 7 themes, dark mode, 5 table styles, and a complete Alpine theme store. This is the visual DNA of everything else — get it right and the rest of the UI assembles itself.

## Design Principles

1. **Good by default, customisable by choice.** Indigo theme ships and looks great out of the box. Customisation is additive.
2. **Semantic tokens, not raw values.** Components consume `var(--accent)`, never `#5b21b6`. This makes theme switching instant.
3. **Three token layers:**
   - **Primitives** — raw HSL values (never used directly in UI)
   - **Semantic tokens** — role-based, what components consume
   - **Component tokens** — specific overrides for cards, inputs, etc.
4. **Accent tints everywhere.** Every accent color generates a full tint scale (`--accent-subtle`, `--accent-muted`, `--accent-emphasis`) so UI elements (badges, hover states, focus rings) always look intentional.
5. **Print is always white.** No theme bleeds into the PDF. The accent can tint the letterhead decorative elements, but body text and table cells stay on white.

---

## Token Architecture in `resources/css/app.css`

```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';

/* ─── Typography ─────────────────────────────────────────── */
@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif,
                 'Apple Color Emoji', 'Segoe UI Emoji';
}

/* ─── Base color-scheme declaration ─────────────────────── */
:root { color-scheme: light; }
[data-dark="true"] { color-scheme: dark; }

/* ═══════════════════════════════════════════════════════════
   LAYER 1 — PRIMITIVE PALETTE (never use these directly)
   These are per-theme, updated by [data-theme] selectors.
   ═══════════════════════════════════════════════════════════ */
:root {
    /* Accent primitive — set by each theme */
    --hue:        262;          /* indigo default */
    --chroma:     0.24;         /* oklch chroma */
}

/* ═══════════════════════════════════════════════════════════
   LAYER 2 — SEMANTIC TOKENS (what components consume)
   ═══════════════════════════════════════════════════════════ */
:root {
    /* ── Surfaces ──────────────────────────────────────────── */
    --bg:           #f6f7fb;     /* page background */
    --surface:      #ffffff;     /* card / panel background */
    --surface-2:    #f0f2f8;     /* secondary surface (table even rows, sidebar) */
    --surface-3:    #e8ebf4;     /* tertiary (active nav, selected row) */
    --overlay:      rgba(0 0 0 / 0.45);  /* modal backdrop */

    /* ── Border ────────────────────────────────────────────── */
    --border:       #dde1ee;     /* default border */
    --border-2:     #c8cde2;     /* stronger border (active state) */

    /* ── Ink (text) ────────────────────────────────────────── */
    --ink:          #141428;     /* headings, strong text */
    --ink-2:        #454570;     /* body text */
    --ink-muted:    #8a8ab0;     /* placeholders, captions */
    --ink-disabled: #b8b8d0;     /* disabled */

    /* ── Accent scale (derived from --hue / --chroma) ─────── */
    --accent:         oklch(50% var(--chroma) var(--hue));          /* primary CTA, active UI */
    --accent-2:       oklch(62% calc(var(--chroma) * 0.85) calc(var(--hue) + 14)); /* secondary accent */
    --accent-fg:      #ffffff;    /* text on --accent backgrounds */
    --accent-subtle:  oklch(96% calc(var(--chroma) * 0.12) var(--hue));  /* badge bg, hover state bg */
    --accent-muted:   oklch(88% calc(var(--chroma) * 0.25) var(--hue));  /* badge border, soft highlight */
    --accent-emphasis:oklch(42% var(--chroma) var(--hue));               /* darker accent for text on light bg */
    --accent-glow:    oklch(50% var(--chroma) var(--hue) / 0.20);        /* focus rings, shadows */

    /* ── Status colours ────────────────────────────────────── */
    --success:      #059669;
    --warning:      #d97706;
    --danger:       #dc2626;
    --info:         #0891b2;

    /* ── Shape ─────────────────────────────────────────────── */
    --radius-xs:    0.25rem;
    --radius:       0.5rem;
    --radius-md:    0.625rem;
    --radius-lg:    0.875rem;
    --radius-xl:    1.25rem;
    --radius-full:  9999px;

    /* ── Elevation / Shadow ────────────────────────────────── */
    --shadow-xs:    0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-sm:    0 1px 4px 0 rgb(0 0 0 / 0.08);
    --shadow:       0 2px 8px -1px rgb(0 0 0 / 0.10), 0 1px 3px -1px rgb(0 0 0 / 0.06);
    --shadow-md:    0 4px 16px -2px rgb(0 0 0 / 0.12), 0 2px 6px -2px rgb(0 0 0 / 0.06);
    --shadow-lg:    0 12px 32px -4px rgb(0 0 0 / 0.14), 0 4px 10px -4px rgb(0 0 0 / 0.08);
    --shadow-accent:0 4px 16px -2px var(--accent-glow);

    /* ── Spacing rhythm ─────────────────────────────────────── */
    --space-1:  0.25rem;
    --space-2:  0.5rem;
    --space-3:  0.75rem;
    --space-4:  1rem;
    --space-6:  1.5rem;
    --space-8:  2rem;
    --space-12: 3rem;

    /* ── Motion ─────────────────────────────────────────────── */
    --ease-out:     cubic-bezier(0.0, 0, 0.2, 1);
    --ease-spring:  cubic-bezier(0.34, 1.56, 0.64, 1);
    --duration-fast:    120ms;
    --duration:         200ms;
    --duration-slow:    350ms;
    --transition:       var(--duration) var(--ease-out);
}

/* ═══════════════════════════════════════════════════════════
   LAYER 3 — COMPONENT TOKENS
   Keep components self-describing; avoids magic values.
   ═══════════════════════════════════════════════════════════ */
:root {
    /* Inputs */
    --input-bg:        var(--surface);
    --input-border:    var(--border);
    --input-focus:     var(--accent);
    --input-ring:      var(--accent-glow);
    --input-placeholder: var(--ink-muted);

    /* Cards */
    --card-bg:         var(--surface);
    --card-border:     var(--border);
    --card-shadow:     var(--shadow);
    --card-radius:     var(--radius-lg);

    /* Buttons */
    --btn-primary-bg:  var(--accent);
    --btn-primary-fg:  var(--accent-fg);
    --btn-primary-shadow: var(--shadow-accent);

    /* Sidebar / Panel */
    --panel-bg:        var(--surface);
    --panel-border:    var(--border);
    --panel-width:     16rem;

    /* Toolbar */
    --toolbar-bg:      var(--surface);
    --toolbar-border:  var(--border);
    --toolbar-height:  3.5rem;
}
```

---

## 7 Theme Definitions

Themes override only `--hue`, `--chroma`, and optionally surface tones. The accent scale is derived automatically — no need to specify every shade.

```css
/* ─── Theme: Indigo (default) ───────────────────────────── */
[data-theme="indigo"], :root {
    --hue:    262;
    --chroma: 0.24;
}

/* ─── Theme: Emerald ────────────────────────────────────── */
[data-theme="emerald"] {
    --hue:    162;
    --chroma: 0.18;
    /* Emerald sits lighter in oklch — nudge for AA contrast */
    --accent: oklch(44% 0.18 162);
}

/* ─── Theme: Sunset ─────────────────────────────────────── */
[data-theme="sunset"] {
    --hue:    30;
    --chroma: 0.22;
    --accent: oklch(56% 0.22 30);      /* warm coral-amber */
    --accent-2: oklch(68% 0.20 60);    /* amber secondary */
}

/* ─── Theme: Mono Pro ───────────────────────────────────── */
[data-theme="mono-pro"] {
    --hue:    300;
    --chroma: 0.28;
    --accent: oklch(22% 0.0 0);        /* near-black primary */
    --accent-2: oklch(55% 0.28 300);   /* vivid magenta pop */
    --accent-fg: #ffffff;
    --accent-subtle: oklch(96% 0.02 0);
    --accent-glow: oklch(22% 0.0 0 / 0.15);
    /* Mono surface — crisp white with inky darks */
    --bg:      #f9f9f9;
    --ink:     #0d0d0d;
}

/* ─── Theme: Ocean ──────────────────────────────────────── */
[data-theme="ocean"] {
    --hue:    230;
    --chroma: 0.22;
    --accent-2: oklch(62% 0.18 200);   /* cyan secondary */
    /* Cool-tinted surfaces */
    --bg:      #f4f6fb;
    --surface-2: #eaeff8;
}

/* ─── Theme: Oxblood ────────────────────────────────────── */
[data-theme="oxblood"] {
    --hue:    14;
    --chroma: 0.20;
    --accent: oklch(34% 0.20 14);      /* deep oxblood */
    --accent-2: oklch(50% 0.08 60);    /* stone secondary */
    /* Warm parchment surfaces */
    --bg:      #faf8f5;
    --surface: #fffefb;
    --surface-2: #f2ede7;
    --border:   #e0d8ce;
    --ink:      #1c1410;
    --ink-2:    #4a3f34;
    --ink-muted:#8a7a6e;
}

/* ─── Theme: Grape ──────────────────────────────────────── */
[data-theme="grape"] {
    --hue:    290;
    --chroma: 0.30;
    --accent-2: oklch(62% 0.30 320);   /* magenta secondary */
}
```

---

## Dark Mode

Dark mode lightens ink, darkens surfaces, and slightly desaturates the accent to prevent eye-strain.

```css
/* ─── System preference ─────────────────────────────────── */
@media (prefers-color-scheme: dark) {
    :root { --dark-mode: 1; }
}

/* ─── Manual override (Alpine sets data-dark="true" on <html>) ── */
:root:where([data-dark="true"]),
@media (prefers-color-scheme: dark) {
    :root:not([data-dark="false"]) {
        /* Surfaces */
        --bg:           #0e0e1a;
        --surface:      #16162a;
        --surface-2:    #1e1e38;
        --surface-3:    #262644;
        --overlay:      rgba(0 0 0 / 0.65);

        /* Borders */
        --border:       #2c2c50;
        --border-2:     #3a3a62;

        /* Ink */
        --ink:          #eaeaf8;
        --ink-2:        #9090c0;
        --ink-muted:    #5858a0;
        --ink-disabled: #3a3a60;

        /* Accent — slightly dimmer in dark */
        --accent:         oklch(62% calc(var(--chroma) * 0.85) var(--hue));
        --accent-subtle:  oklch(20% calc(var(--chroma) * 0.20) var(--hue));
        --accent-muted:   oklch(35% calc(var(--chroma) * 0.30) var(--hue));
        --accent-emphasis:oklch(75% calc(var(--chroma) * 0.70) var(--hue));
        --accent-glow:    oklch(62% var(--chroma) var(--hue) / 0.25);

        /* Elevation — more pronounced in dark */
        --shadow-xs:    0 1px 2px 0 rgb(0 0 0 / 0.25);
        --shadow-sm:    0 1px 4px 0 rgb(0 0 0 / 0.30);
        --shadow:       0 2px 8px -1px rgb(0 0 0 / 0.40);
        --shadow-md:    0 4px 16px -2px rgb(0 0 0 / 0.45);
        --shadow-lg:    0 12px 32px -4px rgb(0 0 0 / 0.50);
    }
}
```

> **Note on specificity**: Use `[data-dark="true"]` on `<html>`. This selector wins over the media query, giving the user manual override power.

---

## Global Base Styles

```css
/* ─── Smooth theme transitions (only on intentional changes) */
.theme-transitioning,
.theme-transitioning *,
.theme-transitioning *::before,
.theme-transitioning *::after {
    transition: background-color var(--duration-slow) var(--ease-out),
                border-color var(--duration-slow) var(--ease-out),
                color var(--duration-fast) var(--ease-out) !important;
}

/* ─── Global reset extras ───────────────────────────────── */
body {
    background-color: var(--bg);
    color: var(--ink-2);
    font-size: 0.9375rem;       /* 15px — slightly smaller than 16px default, cleaner for dense UI */
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
}

/* ─── Focus ring — consistent, accessible ───────────────── */
:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
    border-radius: var(--radius-xs);
}

/* ─── Scrollbar — subtle, on-brand ─────────────────────── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb {
    background: var(--border-2);
    border-radius: var(--radius-full);
}
::-webkit-scrollbar-thumb:hover { background: var(--ink-muted); }
```

---

## 5 Table Styles

Table styles are scoped to `[data-table-style]` on the `<table>` element. They compose on top of a shared table base class.

```css
/* ─── Shared table base ─────────────────────────────────── */
.doc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;  /* 14px — readable but space-efficient */
}

.doc-table th {
    font-weight: 600;
    letter-spacing: 0.01em;
    text-align: left;
    white-space: nowrap;
    padding: 0.5rem 0.75rem;
    color: var(--ink);
}

.doc-table td {
    padding: 0.4375rem 0.75rem;   /* 7px 12px — tight but not cramped */
    vertical-align: top;
    color: var(--ink-2);
}

.doc-table tr { transition: background-color var(--duration-fast) var(--ease-out); }

/* Number / date columns align right */
.doc-table td.align-right,
.doc-table th.align-right { text-align: right; }

/* ─── 1. Clean (default) ─────────────────────────────────
   Minimal look. A faint bottom border on the header row,
   alternating rows with a very subtle tint.             */
[data-table-style="clean"] .doc-table thead tr {
    border-bottom: 2px solid var(--border);
}
[data-table-style="clean"] .doc-table tbody tr:nth-child(even) {
    background-color: var(--surface-2);
}
[data-table-style="clean"] .doc-table tbody tr:hover {
    background-color: var(--surface-3);
}

/* ─── 2. Ruled ───────────────────────────────────────────
   Classic ledger look. Horizontal rules only, no stripes.
   Strong header rule, lighter row rules.                */
[data-table-style="ruled"] .doc-table thead tr {
    border-bottom: 2px solid var(--accent-muted);
}
[data-table-style="ruled"] .doc-table tbody tr {
    border-bottom: 1px solid var(--border);
}
[data-table-style="ruled"] .doc-table tbody tr:last-child {
    border-bottom: none;
}
[data-table-style="ruled"] .doc-table tbody tr:hover {
    background-color: var(--accent-subtle);
}

/* ─── 3. Boxed ───────────────────────────────────────────
   Full grid. Every cell has a border.
   Good for dense data tables and financial statements.  */
[data-table-style="boxed"] .doc-table th,
[data-table-style="boxed"] .doc-table td {
    border: 1px solid var(--border);
}
[data-table-style="boxed"] .doc-table thead th {
    border-color: var(--border-2);
    background-color: var(--surface-2);
}
[data-table-style="boxed"] .doc-table tbody tr:hover td {
    background-color: var(--accent-subtle);
}

/* ─── 4. Striped ─────────────────────────────────────────
   Strong alternating bands in the accent tint.
   High visual rhythm, ideal for long lists.            */
[data-table-style="striped"] .doc-table thead tr {
    border-bottom: 2px solid var(--border);
}
[data-table-style="striped"] .doc-table tbody tr:nth-child(odd) {
    background-color: var(--accent-subtle);
}
[data-table-style="striped"] .doc-table tbody tr:hover {
    background-color: var(--accent-muted);
}

/* ─── 5. Shaded Header ───────────────────────────────────
   Accent-filled header. The most "branded" style.
   Pairs beautifully with a matching letterhead accent.  */
[data-table-style="shaded-header"] .doc-table thead tr {
    background-color: var(--accent);
    color: var(--accent-fg);
    border-bottom: none;
}
[data-table-style="shaded-header"] .doc-table thead th {
    color: var(--accent-fg);
}
[data-table-style="shaded-header"] .doc-table tbody tr:nth-child(even) {
    background-color: var(--surface-2);
}
[data-table-style="shaded-header"] .doc-table tbody tr:hover {
    background-color: var(--surface-3);
}

/* ─── Shared: summary / tfoot row ───────────────────────── */
.doc-table tfoot tr td {
    border-top: 2px solid var(--accent-muted);
    font-weight: 700;
    color: var(--ink);
}
```

---

## Print CSS

Print always resets to neutral — no theme leaks into the PDF body. Only the letterhead accent elements retain colour.

```css
@media print {
    /* Reset all surface tokens to white/black */
    :root {
        --bg:           #ffffff !important;
        --surface:      #ffffff !important;
        --surface-2:    #f4f4f4 !important;
        --surface-3:    #ebebeb !important;
        --border:       #cccccc !important;
        --border-2:     #aaaaaa !important;
        --ink:          #000000 !important;
        --ink-2:        #222222 !important;
        --ink-muted:    #555555 !important;
    }

    /* Page geometry — default portrait, overridden by JS landscape toggle */
    @page {
        size: A4 portrait;
        margin: 12mm 15mm 15mm 15mm;   /* tighter top for letterhead room */
    }

    /* Reset body */
    body {
        background: #ffffff;
        color: #000000;
        font-size: 10pt;
        line-height: 1.45;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;      /* preserve accent colors in letterhead */
    }

    /* Hide UI chrome */
    .no-print { display: none !important; }

    /* Show print-only elements (e.g. page numbers if not using Paged.js) */
    .print-only { display: block !important; }

    /* Table rules */
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
    tr    { break-inside: avoid; page-break-inside: avoid; }

    .doc-table {
        font-size: 9pt;
    }

    .doc-table th,
    .doc-table td {
        padding: 4pt 6pt;
    }

    /* Suppress all box-shadows */
    * { box-shadow: none !important; }
}
```

---

## Alpine.js Theme Store

The store is the single source of truth. It applies attributes to `<html>` and reads from localStorage. Initialize it **before** Alpine starts (in a `<script>` tag in `<head>`) to prevent flash-of-wrong-theme.

```js
// ── Anti-FOUT: apply theme immediately before paint ─────────
(function () {
    const theme = localStorage.getItem('pt-theme') || 'indigo';
    const dark  = localStorage.getItem('pt-dark') === 'true';
    const orientation = localStorage.getItem('pt-orientation') || 'portrait';

    document.documentElement.setAttribute('data-theme', theme);
    if (dark) document.documentElement.setAttribute('data-dark', 'true');
    if (orientation === 'landscape') {
        const s = document.createElement('style');
        s.id = 'pt-orientation-style';
        s.textContent = '@page { size: A4 landscape; }';
        document.head.appendChild(s);
    }
})();

// ── Alpine theme store ───────────────────────────────────────
document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        current:      localStorage.getItem('pt-theme')       || 'indigo',
        dark:         localStorage.getItem('pt-dark')         === 'true',
        tableStyle:   localStorage.getItem('pt-table-style')  || 'clean',
        orientation:  localStorage.getItem('pt-orientation')  || 'portrait',
        customAccent: localStorage.getItem('pt-custom-accent') || null,

        themes: [
            { id: 'indigo',    label: 'Indigo',    swatch: 'oklch(50% 0.24 262)' },
            { id: 'emerald',   label: 'Emerald',   swatch: 'oklch(44% 0.18 162)' },
            { id: 'sunset',    label: 'Sunset',    swatch: 'oklch(56% 0.22 30)'  },
            { id: 'mono-pro',  label: 'Mono Pro',  swatch: '#0d0d0d'             },
            { id: 'ocean',     label: 'Ocean',     swatch: 'oklch(48% 0.22 230)' },
            { id: 'oxblood',   label: 'Oxblood',   swatch: 'oklch(34% 0.20 14)'  },
            { id: 'grape',     label: 'Grape',     swatch: 'oklch(50% 0.30 290)' },
        ],

        tableStyles: [
            { id: 'clean',         label: 'Clean'         },
            { id: 'ruled',         label: 'Ruled'         },
            { id: 'boxed',         label: 'Boxed'         },
            { id: 'striped',       label: 'Striped'       },
            { id: 'shaded-header', label: 'Shaded Header' },
        ],

        /** Apply a named theme, with a brief transition flash */
        setTheme(name) {
            document.documentElement.classList.add('theme-transitioning');
            this.current = name;
            this.customAccent = null;
            localStorage.setItem('pt-theme', name);
            localStorage.removeItem('pt-custom-accent');
            document.documentElement.setAttribute('data-theme', name);
            // Remove inline accent override if previously set
            document.documentElement.style.removeProperty('--accent');
            document.documentElement.style.removeProperty('--accent-2');
            setTimeout(() => document.documentElement.classList.remove('theme-transitioning'), 400);
        },

        /** Apply a custom hex color as the accent */
        setCustomAccent(hex) {
            this.customAccent = hex;
            localStorage.setItem('pt-custom-accent', hex);
            // Override accent directly on :root (bypasses theme blocks)
            document.documentElement.style.setProperty('--accent', hex);
            document.documentElement.style.setProperty('--accent-subtle', hex + '18');
        },

        /** Toggle dark mode */
        toggleDark() {
            document.documentElement.classList.add('theme-transitioning');
            this.dark = !this.dark;
            localStorage.setItem('pt-dark', this.dark);
            document.documentElement.setAttribute('data-dark', this.dark);
            setTimeout(() => document.documentElement.classList.remove('theme-transitioning'), 400);
        },

        /** Set table style */
        setTableStyle(style) {
            this.tableStyle = style;
            localStorage.setItem('pt-table-style', style);
        },

        /** Set print orientation */
        setOrientation(value) {
            this.orientation = value;
            localStorage.setItem('pt-orientation', value);
            let styleTag = document.getElementById('pt-orientation-style');
            if (!styleTag) {
                styleTag = document.createElement('style');
                styleTag.id = 'pt-orientation-style';
                document.head.appendChild(styleTag);
            }
            styleTag.textContent = value === 'landscape'
                ? '@page { size: A4 landscape; }'
                : '@page { size: A4 portrait; }';
        }
    });
});
```

---

## Blade Layout: Apply Theme Attributes

In `resources/views/layouts/app.blade.php`, the `<html>` tag must apply stored attributes synchronously (the inline script above handles this), and the `<body>` should receive the `[data-table-style]` binding:

```html
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="indigo">  {{-- overwritten instantly by inline script --}}
<head>
    <!-- Inline anti-FOUT script goes here, before any CSS load -->
    <script>
        (function(){
            const t=localStorage.getItem('pt-theme')||'indigo';
            const d=localStorage.getItem('pt-dark')==='true';
            document.documentElement.setAttribute('data-theme',t);
            if(d) document.documentElement.setAttribute('data-dark','true');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data
      :data-table-style="$store.theme.tableStyle">
```

---

## Checklist
- [ ] `app.css`: All 3 token layers (primitives, semantic, component) written
- [ ] All 7 `[data-theme]` blocks use `--hue` / `--chroma` driven accent scale
- [ ] Accent tint scale (`--accent-subtle`, `--accent-muted`, `--accent-emphasis`, `--accent-glow`) defined
- [ ] Dark mode: `[data-dark="true"]` selector written (not just media query)
- [ ] `color-scheme` declared on `:root` and `[data-dark]`
- [ ] Global base styles: `body`, `:focus-visible`, scrollbar
- [ ] `.theme-transitioning` transition class for smooth switches
- [ ] All 5 table styles written using `.doc-table` base class
- [ ] `tfoot` shared summary row style
- [ ] Print CSS: token reset + typography + table rules
- [ ] `-webkit-print-color-adjust: exact` for letterhead accent preservation
- [ ] Alpine `theme` store: `setTheme`, `setCustomAccent`, `toggleDark`, `setTableStyle`, `setOrientation`
- [ ] Anti-FOUT inline script in `<head>` (before CSS)
- [ ] `<html data-theme>` and `<body :data-table-style>` wired up
- [ ] `npm run build` passes
