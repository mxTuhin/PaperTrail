# Step 06 — Company Letterhead / Pad System

## Goal
Build a multi-profile letterhead system that is **compact by default** but fully controllable. A letterhead must earn its space on an A4 sheet — the first priority is that the data table has room to breathe. Letterheads are aesthetic _borders_, not landing pages.

## Core Principle: Space Budget

An A4 page at 15mm margins has ~267mm of usable height. The letterhead should occupy:
- **Compact** (default): ≤ 18mm — a single-line strip or thin two-column row
- **Standard**: ≤ 32mm — room for a logo + 2 lines of detail
- **Full**: ≤ 52mm — logo + full address block + document meta

The `size` field in the data model controls this. All layouts respect it.

---

## Data Model (localStorage)

```js
// Key: 'pt-letterheads'
[
    {
        id: 'uuid-or-timestamp',
        name: 'My Company Ltd.',          // profile display name in the switcher
        companyName: 'My Company Ltd.',
        tagline: '',                      // optional subtitle under company name
        address: '123 Main St, Dhaka',
        phone: '+880 1234 567890',
        email: 'info@mycompany.com',
        bin: 'BIN-123456789',             // BD business ID (shown only if filled)
        docTitle: 'Sales Statement',
        statementFor: '',                 // "Prepared for: Acme Corp" line
        date: '',                         // empty = auto-fill today in DD/MM/YYYY

        // Visual options
        logoBase64: '',                   // data:image/png;base64,...
        logoHeight: 36,                   // px — height (width auto-scales)
        layout: 'split-header',           // see layouts below
        size: 'compact',                  // 'compact' | 'standard' | 'full'
        showDivider: true,                // accent rule below the letterhead
        dividerWeight: 'medium',          // 'thin' | 'medium' | 'thick' | 'double'
    }
]

// Key: 'pt-active-letterhead'  → id string
// Key: 'pt-show-letterhead'    → 'true' | 'false'
```

---

## Size System

The `size` property maps to a CSS class on the letterhead wrapper. Each layout's internal HTML must respect these constraints.

```css
/* ─── Letterhead size constraints ──────────────────────── */

/* Compact: single row, logo max 28px tall, no address */
.lh-wrapper[data-lh-size="compact"] {
    --lh-padding-y:    6px;
    --lh-logo-height:  28px;
    --lh-font-name:    0.9375rem;   /* 15px */
    --lh-font-detail:  0.75rem;     /* 12px */
    --lh-gap:          0.5rem;
    --lh-max-height:   48px;
}

/* Standard: two-row layout, logo max 40px */
.lh-wrapper[data-lh-size="standard"] {
    --lh-padding-y:    10px;
    --lh-logo-height:  40px;
    --lh-font-name:    1.0625rem;   /* 17px */
    --lh-font-detail:  0.8125rem;   /* 13px */
    --lh-gap:          0.75rem;
    --lh-max-height:   80px;
}

/* Full: complete address block, logo max 56px */
.lh-wrapper[data-lh-size="full"] {
    --lh-padding-y:    14px;
    --lh-logo-height:  56px;
    --lh-font-name:    1.125rem;    /* 18px */
    --lh-font-detail:  0.875rem;    /* 14px */
    --lh-gap:          1rem;
    --lh-max-height:   140px;
}

/* ─── Shared wrapper ────────────────────────────────────── */
.lh-wrapper {
    padding-top: var(--lh-padding-y);
    padding-bottom: var(--lh-padding-y);
    margin-bottom: 4mm;
    max-height: var(--lh-max-height);
    overflow: hidden; /* enforce budget — nothing bleeds */
}

/* ─── Divider styles ────────────────────────────────────── */
.lh-divider[data-weight="thin"]   { border-bottom: 1px solid var(--accent); }
.lh-divider[data-weight="medium"] { border-bottom: 2px solid var(--accent); }
.lh-divider[data-weight="thick"]  { border-bottom: 3px solid var(--accent); }
.lh-divider[data-weight="double"] {
    border-bottom: 3px double var(--accent);
}

/* Logo scales by height, width is auto */
.lh-logo { height: var(--lh-logo-height); width: auto; display: block; }

/* Name + detail typography */
.lh-name   { font-size: var(--lh-font-name);   font-weight: 700; color: var(--ink);   line-height: 1.2; }
.lh-detail { font-size: var(--lh-font-detail);  color: var(--ink-muted); line-height: 1.3; }
.lh-title  { font-size: var(--lh-font-detail);  font-weight: 600; color: var(--accent); }
.lh-meta   { font-size: var(--lh-font-detail);  font-family: ui-monospace, monospace; }
```

---

## Alpine Store: `letterhead`

```js
document.addEventListener('alpine:init', () => {
    Alpine.store('letterhead', {
        profiles: [],
        activeId: null,
        showOnPrint: true,

        init() {
            const saved = localStorage.getItem('pt-letterheads');
            this.profiles = saved ? JSON.parse(saved) : [this.defaultProfile()];
            this.activeId = localStorage.getItem('pt-active-letterhead')
                ?? this.profiles[0].id;
            this.showOnPrint = localStorage.getItem('pt-show-letterhead') !== 'false';
        },

        get active() {
            return this.profiles.find(p => p.id === this.activeId) ?? this.profiles[0];
        },

        save() {
            localStorage.setItem('pt-letterheads', JSON.stringify(this.profiles));
            localStorage.setItem('pt-active-letterhead', this.activeId);
            localStorage.setItem('pt-show-letterhead', this.showOnPrint);
        },

        addProfile() {
            const p = this.defaultProfile();
            this.profiles.push(p);
            this.activeId = p.id;
            this.save();
        },

        duplicateProfile(id) {
            const src = this.profiles.find(p => p.id === id);
            if (!src) return;
            const copy = { ...src, id: Date.now().toString(), name: src.name + ' (copy)' };
            this.profiles.push(copy);
            this.activeId = copy.id;
            this.save();
        },

        removeProfile(id) {
            if (this.profiles.length === 1) return;
            this.profiles = this.profiles.filter(p => p.id !== id);
            this.activeId = this.profiles[0].id;
            this.save();
        },

        updateField(field, value) {
            this.active[field] = value;
            this.save();
        },

        defaultProfile() {
            return {
                id: Date.now().toString(),
                name: 'My Company',
                companyName: 'My Company Ltd.',
                tagline: '',
                address: '',
                phone: '',
                email: '',
                bin: '',
                docTitle: 'Statement',
                statementFor: '',
                date: '',
                logoBase64: '',
                logoHeight: 36,
                layout: 'split-header',
                size: 'compact',           // compact by default
                showDivider: true,
                dividerWeight: 'medium',
            };
        }
    });
});
```

---

## 7 Layout Designs

Every layout has three visual modes (`compact`, `standard`, `full`) controlled by the `size` CSS variables. The HTML structure is the same — only what content is *visible* changes per size.

### What each size shows

| Field | compact | standard | full |
|---|---|---|---|
| Logo | ✓ (small) | ✓ | ✓ |
| Company name | ✓ | ✓ | ✓ |
| Tagline | — | ✓ | ✓ |
| Address | — | — | ✓ |
| Phone / Email | — | — | ✓ |
| BIN | — | — | ✓ |
| Doc title | ✓ | ✓ | ✓ |
| Date | ✓ | ✓ | ✓ |
| Statement For | — | ✓ | ✓ |

Implemented by adding `x-show` guards keyed on `active.size`:

```js
const isStandard = () => ['standard', 'full'].includes(Alpine.store('letterhead').active.size);
const isFull     = () => Alpine.store('letterhead').active.size === 'full';
```

---

### Layout 1: `split-header` ⭐ (default)

**Left**: logo + name | **Right**: doc title + meta. Clean, professional, works at all sizes.

```html
<div class="lh-wrapper" :data-lh-size="active.size">
    <div class="flex items-center justify-between" style="gap: var(--lh-gap)">
        <!-- Left: identity -->
        <div class="flex items-center" style="gap: var(--lh-gap)">
            <img x-show="active.logoBase64"
                 :src="active.logoBase64"
                 class="lh-logo shrink-0">
            <div>
                <div class="lh-name" x-text="active.companyName"></div>
                <div class="lh-detail" x-show="active.tagline && isStandard()" x-text="active.tagline"></div>
                <div class="lh-detail" x-show="isFull()" x-text="[active.address, active.phone].filter(Boolean).join(' · ')"></div>
            </div>
        </div>
        <!-- Right: document meta -->
        <div class="text-right shrink-0">
            <div class="lh-title" x-text="active.docTitle"></div>
            <div class="lh-meta lh-detail" x-text="active.date || new Date().toLocaleDateString('en-GB')"></div>
            <div class="lh-detail" x-show="active.statementFor && isStandard()" x-text="'For: ' + active.statementFor"></div>
            <div class="lh-detail" x-show="active.bin && isFull()" x-text="'BIN: ' + active.bin"></div>
        </div>
    </div>
    <!-- Divider -->
    <div x-show="active.showDivider" class="lh-divider mt-2" :data-weight="active.dividerWeight"></div>
</div>
```

---

### Layout 2: `inline-strip`

**Entire header in one horizontal line.** Ultra compact. Best for `compact` size only — at `full` size, it wraps.

```html
<div class="lh-wrapper" :data-lh-size="active.size">
    <div class="flex items-center justify-between flex-wrap" style="gap: 0.5rem var(--lh-gap)">
        <div class="flex items-center" style="gap: var(--lh-gap)">
            <img x-show="active.logoBase64" :src="active.logoBase64" class="lh-logo shrink-0">
            <span class="lh-name" x-text="active.companyName"></span>
            <span class="lh-detail" x-show="active.address && isFull()" x-text="' · ' + active.address"></span>
        </div>
        <div class="flex items-center lh-detail" style="gap: 1rem">
            <span class="lh-title" x-text="active.docTitle"></span>
            <span class="lh-meta" x-text="active.date || new Date().toLocaleDateString('en-GB')"></span>
            <span x-show="active.statementFor && isStandard()" x-text="active.statementFor"></span>
        </div>
    </div>
    <div x-show="active.showDivider" class="lh-divider mt-1" :data-weight="active.dividerWeight"></div>
</div>
```

---

### Layout 3: `accent-band`

**A filled accent-colored band** spanning the full header. White text on accent. Attention-grabbing without being excessive.

```html
<div class="lh-wrapper" :data-lh-size="active.size">
    <div class="flex items-center justify-between px-4 rounded-md"
         style="background: var(--accent); color: var(--accent-fg); padding-top: var(--lh-padding-y); padding-bottom: var(--lh-padding-y); gap: var(--lh-gap)">
        <div class="flex items-center" style="gap: 0.625rem">
            <!-- Logo gets an inverted/white filter if on dark band -->
            <img x-show="active.logoBase64" :src="active.logoBase64" class="lh-logo shrink-0"
                 style="filter: brightness(0) invert(1)">
            <div>
                <div class="lh-name" style="color: var(--accent-fg)" x-text="active.companyName"></div>
                <div x-show="isFull()" class="lh-detail" style="color: var(--accent-fg); opacity: 0.75"
                     x-text="active.address"></div>
            </div>
        </div>
        <div class="text-right">
            <div class="lh-title" style="color: var(--accent-fg); opacity: 0.85" x-text="active.docTitle"></div>
            <div class="lh-meta lh-detail" style="color: var(--accent-fg); opacity: 0.70"
                 x-text="active.date || new Date().toLocaleDateString('en-GB')"></div>
            <div x-show="active.statementFor && isStandard()" class="lh-detail"
                 style="color: var(--accent-fg); opacity: 0.75" x-text="active.statementFor"></div>
        </div>
    </div>
    <!-- No divider — the band is the divider -->
    <div style="margin-bottom: 4px"></div>
</div>
```

> **Print note**: Add `print-color-adjust: exact` to `.lh-wrapper` so the accent band prints in colour.

---

### Layout 4: `left-accent-bar`

**Thin vertical accent bar** on the left edge (4px wide), content flush right of it. Sophisticated, editorial. Works at all sizes.

```html
<div class="lh-wrapper" :data-lh-size="active.size">
    <div class="flex" style="border-left: 4px solid var(--accent); padding-left: 0.75rem; gap: var(--lh-gap)">
        <img x-show="active.logoBase64" :src="active.logoBase64" class="lh-logo shrink-0">
        <div class="flex-1 flex items-center justify-between">
            <div>
                <div class="lh-name" x-text="active.companyName"></div>
                <div x-show="isFull()" class="lh-detail" x-text="[active.address, active.phone].filter(Boolean).join(' · ')"></div>
            </div>
            <div class="text-right">
                <div class="lh-title" x-text="active.docTitle"></div>
                <div class="lh-meta lh-detail" x-text="active.date || new Date().toLocaleDateString('en-GB')"></div>
                <div x-show="active.statementFor && isStandard()" class="lh-detail" x-text="'For: ' + active.statementFor"></div>
            </div>
        </div>
    </div>
    <div x-show="active.showDivider" class="lh-divider mt-2" :data-weight="active.dividerWeight"></div>
</div>
```

---

### Layout 5: `monogram-inline`

**Auto-generated circular monogram** (first 2 letters of company name) inline with the name. No logo needed. Clean and always looks intentional.

```html
<div class="lh-wrapper" :data-lh-size="active.size">
    <div class="flex items-center justify-between" style="gap: var(--lh-gap)">
        <div class="flex items-center" style="gap: 0.625rem">
            <!-- Monogram circle — shown only when no logo -->
            <div x-show="!active.logoBase64"
                 class="shrink-0 flex items-center justify-center rounded-full font-black"
                 style="width: var(--lh-logo-height); height: var(--lh-logo-height);
                        background: var(--accent-subtle); color: var(--accent-emphasis);
                        font-size: calc(var(--lh-logo-height) * 0.38);"
                 x-text="active.companyName.trim().slice(0,2).toUpperCase()">
            </div>
            <img x-show="active.logoBase64" :src="active.logoBase64" class="lh-logo shrink-0">
            <div>
                <div class="lh-name" x-text="active.companyName"></div>
                <div x-show="isStandard()" class="lh-detail"
                     x-text="[active.tagline, active.address].filter(Boolean).join(' — ')"></div>
            </div>
        </div>
        <div class="text-right">
            <div class="lh-title" x-text="active.docTitle"></div>
            <div class="lh-meta lh-detail" x-text="active.date || new Date().toLocaleDateString('en-GB')"></div>
            <div x-show="active.statementFor && isStandard()" class="lh-detail" x-text="active.statementFor"></div>
        </div>
    </div>
    <div x-show="active.showDivider" class="lh-divider mt-2" :data-weight="active.dividerWeight"></div>
</div>
```

---

### Layout 6: `minimal-rule`

**Name + title only, with a ruled line.** Maximum restraint. Every pixel is the data. Best for users who want a branded PDF but want the table to dominate.

```html
<div class="lh-wrapper" :data-lh-size="active.size">
    <div class="flex items-baseline justify-between">
        <div class="flex items-center" style="gap: 0.5rem">
            <img x-show="active.logoBase64" :src="active.logoBase64" class="lh-logo shrink-0">
            <span class="lh-name" x-text="active.companyName"></span>
            <span x-show="active.tagline && isStandard()" class="lh-detail">·
                <span x-text="active.tagline"></span>
            </span>
        </div>
        <div class="flex items-center lh-detail" style="gap: 0.75rem">
            <span class="lh-title" x-text="active.docTitle"></span>
            <span class="lh-meta" x-text="active.date || new Date().toLocaleDateString('en-GB')"></span>
        </div>
    </div>
    <!-- Always shows the rule — it IS the design -->
    <div class="lh-divider mt-1" :data-weight="active.dividerWeight"></div>
</div>
```

---

### Layout 7: `two-row`

**Two distinct rows**: top for identity, bottom for document meta. Only useful at `standard` or `full` size — at `compact` it collapses to one row.

```html
<div class="lh-wrapper" :data-lh-size="active.size">
    <!-- Row 1: identity -->
    <div class="flex items-center" style="gap: var(--lh-gap); margin-bottom: 4px">
        <img x-show="active.logoBase64" :src="active.logoBase64" class="lh-logo shrink-0">
        <div>
            <div class="lh-name" x-text="active.companyName"></div>
            <div x-show="isFull()" class="lh-detail"
                 x-text="[active.address, active.phone, active.email].filter(Boolean).join(' · ')"></div>
        </div>
    </div>
    <!-- Row 2: doc meta (hidden in compact) -->
    <div x-show="isStandard()"
         class="flex items-center justify-between lh-detail"
         style="padding-top: 4px; border-top: 1px solid var(--border)">
        <span class="lh-title" x-text="active.docTitle"></span>
        <div class="flex" style="gap: 1rem">
            <span x-show="active.statementFor" x-text="'For: ' + active.statementFor"></span>
            <span x-show="active.bin && isFull()" x-text="'BIN: ' + active.bin"></span>
            <span class="lh-meta" x-text="active.date || new Date().toLocaleDateString('en-GB')"></span>
        </div>
    </div>
    <!-- Compact fallback: all in one line -->
    <div x-show="!isStandard()"
         class="flex items-center justify-between lh-detail">
        <span class="lh-title" x-text="active.docTitle"></span>
        <span class="lh-meta" x-text="active.date || new Date().toLocaleDateString('en-GB')"></span>
    </div>
    <div x-show="active.showDivider" class="lh-divider mt-1" :data-weight="active.dividerWeight"></div>
</div>
```

---

## Letterhead Editor UI

The editor is a panel tab (not a full page). Keep it scannable.

```
┌─────────────────────────────────────┐
│  Profile  [My Company ▾] [+] [⋯]   │
├─────────────────────────────────────┤
│  Layout   [●] Split  [○] Strip ...  │
│  Size     [Compact] [Standard] [Full]│
│  Divider  [✓] Show  Weight [────▾] │
├─────────────────────────────────────┤
│  Company Name  ________________     │
│  Tagline       ________________     │
│  Doc Title     ________________     │
│  Date          [DD/MM/YYYY]         │
│  Statement For ________________     │
├── Advanced (collapsed by default) ──┤
│  Address / Phone / Email / BIN      │
├─────────────────────────────────────┤
│  Logo  [Upload] [Clear]             │
│        (preview if uploaded)        │
├─────────────────────────────────────┤
│  [✓] Show letterhead on print       │
└─────────────────────────────────────┘
```

### Size picker UI (3-button toggle)

```html
<div class="flex rounded-md overflow-hidden border border-[--border]">
    <template x-for="sz in ['compact', 'standard', 'full']">
        <button
            @click="updateField('size', sz)"
            :class="active.size === sz ? 'bg-[--accent] text-[--accent-fg]' : 'bg-[--surface] text-[--ink-2] hover:bg-[--surface-2]'"
            class="flex-1 px-3 py-1.5 text-xs font-medium capitalize transition-colors"
            x-text="sz"
        ></button>
    </template>
</div>
```

### Divider weight picker

```html
<div class="flex items-center gap-2 mt-2">
    <input type="checkbox" :checked="active.showDivider" @change="updateField('showDivider', $event.target.checked)">
    <span class="text-sm">Divider</span>
    <select x-show="active.showDivider" @change="updateField('dividerWeight', $event.target.value)" class="ml-auto text-xs ...">
        <option value="thin">Thin (1px)</option>
        <option value="medium" selected>Medium (2px)</option>
        <option value="thick">Thick (3px)</option>
        <option value="double">Double</option>
    </select>
</div>
```

### Logo upload → base64

```js
function handleLogoUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    if (file.size > 300_000) {
        // Warn but don't block — user decides
        console.warn('Logo > 300KB — consider compressing for smaller PDFs');
    }
    const reader = new FileReader();
    reader.onload = (e) => {
        Alpine.store('letterhead').updateField('logoBase64', e.target.result);
    };
    reader.readAsDataURL(file);
}
```

---

## Print Integration

```html
<!-- In the print area (before the table) -->
<div
    id="letterhead-print-area"
    x-show="$store.letterhead.showOnPrint"
    style="print-color-adjust: exact; -webkit-print-color-adjust: exact;"
>
    <!-- Dynamic layout switcher -->
    <div
        class="lh-wrapper"
        :data-lh-size="$store.letterhead.active.size"
        x-html="getLetterheadHTML($store.letterhead.active)"
    ></div>
</div>
```

> The `getLetterheadHTML()` JS function returns the correct layout HTML string based on `active.layout`. Keep each layout as a JS template string or use Alpine `x-if` blocks with `template` tags.

---

## Print-specific CSS for Letterhead

```css
@media print {
    /* Enforce compact budget even harder in print */
    .lh-wrapper[data-lh-size="compact"] { max-height: 40px; }
    .lh-wrapper[data-lh-size="standard"] { max-height: 72px; }
    .lh-wrapper[data-lh-size="full"]    { max-height: 120px; }

    /* Preserve accent color in letterhead bands / bars */
    #letterhead-print-area {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    /* Margin below letterhead before the table */
    #letterhead-print-area { margin-bottom: 6mm; }
}
```

---

## Checklist

- [ ] `size` field added to data model (`compact` | `standard` | `full`)
- [ ] `--lh-*` size variables defined in CSS for all 3 sizes
- [ ] `overflow: hidden` on `.lh-wrapper` enforces height budget
- [ ] `showDivider` + `dividerWeight` fields + 4 CSS divider styles
- [ ] All 7 layout HTML templates written
- [ ] Each layout uses `x-show="isStandard()"` / `x-show="isFull()"` guards
- [ ] `accent-band` layout has logo `filter: brightness(0) invert(1)` for white-on-color
- [ ] `logoHeight` (not width) controls logo size (auto-width scales naturally)
- [ ] Monogram circle auto-generates from first 2 chars when no logo
- [ ] Editor panel: profile switcher, 7 layout picker, 3-button size toggle, divider control
- [ ] "Advanced" section collapsed by default (address, phone, email, BIN)
- [ ] Logo upload → base64 with `> 300KB` warning (non-blocking)
- [ ] `showOnPrint` toggle visible and saved to localStorage
- [ ] Print CSS: `max-height` enforced, `print-color-adjust: exact` set
- [ ] Date defaults to today in BD format `DD/MM/YYYY` when field is empty
