# Step 08 — Print / PDF Output

## Goal
Polish the print pipeline so the A4 output is clean, pixel-perfect, and matches the on-screen preview. Integrates Alpine theme-store orientation states with standard browser print behaviors.

---

## Shared Print Style (in `app.css`)

Ensure the print media queries reset all token colors to absolute monochrome to prevent background shades or custom theme colors from bleeding onto standard paper pages (except for the letterhead accent rules/headers).

```css
@media print {
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

    @page {
        size: A4 portrait;
        margin: 12mm 15mm 15mm 15mm;
    }

    body {
        background: #ffffff;
        color: #000000;
        font-size: 10pt;
        line-height: 1.45;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Hide toolbar, column configurators, and file pickers */
    .no-print {
        display: none !important;
    }

    /* Enforce table pagination rules */
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
    tr    { break-inside: avoid; page-break-inside: avoid; }

    .doc-table {
        font-size: 9pt;
        width: 100%;
        border-collapse: collapse;
    }

    .doc-table th,
    .doc-table td {
        padding: 4pt 6pt;
    }
}
```

---

## Orientation Management

Orientation is handled dynamically by the `$store.theme` store (written in Step 02), which toggles the print behavior via style tag injections.

### Store Call Reference:
To update orientation:
```html
<select @change="$store.theme.setOrientation($event.target.value)" :value="$store.theme.orientation">
    <option value="portrait">A4 Portrait</option>
    <option value="landscape">A4 Landscape</option>
</select>
```

---

## Print Area HTML Structure

The print target matches the container `id="print-area"`. In non-print preview mode, it simulates an A4 page dimensions on screen.

```html
<div id="print-area" class="bg-white text-black shadow-lg mx-auto" style="min-height: 297mm; max-width: 210mm; padding: 15mm;">
    
    <!-- Letterhead Wrapper Block (Step 06) -->
    <div
        class="letterhead-wrapper"
        :class="{ 'no-print': !$store.letterhead.showOnPrint }"
        x-show="$store.letterhead.showOnPrint"
        style="print-color-adjust: exact; -webkit-print-color-adjust: exact;"
    >
        <!-- Renders active layout component template -->
        <div x-html="renderActiveLetterhead()"></div>
    </div>

    <!-- Structured Document Data -->
    <table class="doc-table">
        <thead>
            <!-- Headers render loops -->
        </thead>
        <tbody>
            <!-- Row render loops -->
        </tbody>
        <tfoot>
            <!-- Totals summaries -->
        </tfoot>
    </table>
</div>
```

---

## WYSIWYG Print Preview Simulation (Screen CSS)

The following styles align the editor container with standard print properties on screen:

```css
@media screen {
    #print-area {
        max-width: 210mm;
        margin: 2rem auto;
        padding: 15mm;
        background: white;
        box-shadow: var(--shadow-lg);
        min-height: 297mm;
        border: 1px solid var(--border);
        border-radius: var(--radius-xs);
    }
    
    /* Simulate landscape sizes on screen */
    :root:has(#pt-orientation-style) #print-area {
        max-width: 297mm;
        min-height: 210mm;
    }
}
```

---

## Print Execution Flow

```js
function handlePrint() {
    // 1. Submit telemetry log payload (non-blocking)
    trackEvent('print', {
        rows: Alpine.store('spreadsheet').rows.length,
        cols: Alpine.store('spreadsheet').headers.filter(h => h.visible).length,
    });

    // 2. Introduce brief delay to let outbound request begin sending
    setTimeout(() => {
        window.print();
    }, 80);
}
```

---

## Checklist
- [ ] Media print styles are synchronized in `app.css` according to Step 02 specifications.
- [ ] Toolbar controls invoke `$store.theme.setOrientation()` to switch layout formats.
- [ ] Orientation choices are written into style tags and synchronized to local storage.
- [ ] `#print-area` mirrors print dimensions on screen (210mm x 297mm portrait simulator).
- [ ] `.no-print` classes hide utility components from target PDF results.
- [ ] Table tags use class `.doc-table` for uniform design integration.
- [ ] Print processes submit logs to `POST /track` cleanly before printing.
- [ ] Letterheads hide cleanly during print operations when configured.
