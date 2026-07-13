# Step 05 — Table Manipulation UI

## Goal
Build the interactive preview table where users show/hide columns, drag-to-reorder, rename headers, sort rows, and see a summary row. This is the core UX of the `/app` page, integrating directly with the token-based Design System (Step 02) and custom table styles.

## Layout of `/app` Page

The workspace divides into a side configuration panel and a print-preview simulator area:
```
┌───────────────────────────────────────────────────────────────┐
│  Toolbar: [Theme ▾] [Style ▾] [A4 Portrait/Landscape ▾] [Print]│
├──────────────┬────────────────────────────────────────────────┤
│  Left Panel  │  Preview Table (Print A4 Container Simulator)  │
│  (controls)  │                                                │
│  • Columns   │  [Letterhead wrapper block (Step 06)]          │
│  • Reorder   │                                                │
│  • Settings  │  [Table with class="doc-table" (Step 02)]      │
│              │  [Summary row / tfoot totals]                  │
└──────────────┴────────────────────────────────────────────────┘
```

---

## Column Control Panel (Left Panel)

Users control column visibility, naming, and cell types from a draggable column list.

```html
<div x-data="columnPanel()" class="w-64 shrink-0 p-4 border-r border-[--border] bg-[--surface-2] no-print">
    <h3 class="font-semibold text-sm mb-3 text-[--ink]">Columns & Formatting</h3>

    <!-- Sortable list (SortableJS) -->
    <ul x-ref="colList" id="column-list" class="space-y-1">
        <template x-for="col in $store.spreadsheet.headers" :key="col.key">
            <li class="flex items-center gap-2 p-2 rounded-md bg-[--surface] border border-[--border] cursor-grab active:cursor-grabbing">
                <!-- Drag handle -->
                <span class="drag-handle text-[--ink-muted] select-none cursor-move">⠿</span>

                <!-- Visible toggle -->
                <input type="checkbox" :checked="col.visible" @change="col.visible = !col.visible" class="rounded border-[--border] text-[--accent] focus:ring-[--accent-glow]">

                <!-- Rename input -->
                <input
                    type="text"
                    :value="col.label"
                    @blur="col.label = $event.target.value; Alpine.store('spreadsheet').saveState()"
                    class="text-xs flex-1 bg-transparent border-b border-transparent hover:border-[--border] focus:border-[--accent] outline-none font-medium text-[--ink]"
                >

                <!-- Type selector dropdown -->
                <select :value="col.type" @change="col.type = $event.target.value; col.align = ['integer', 'decimal', 'currency', 'percentage', 'date', 'datetime'].includes(col.type) ? 'right' : 'left'" class="text-[10px] bg-[--surface-2] border border-[--border] rounded px-1 py-0.5 text-[--ink-2]">
                    <option value="text">Text</option>
                    <option value="integer">Integer</option>
                    <option value="decimal">Decimal</option>
                    <option value="currency">Currency</option>
                    <option value="percentage">%</option>
                    <option value="date">Date</option>
                    <option value="datetime">Datetime</option>
                    <option value="boolean">Bool</option>
                    <option value="leading-zero-code">Code</option>
                </select>
            </li>
        </template>
    </ul>
</div>
```

### SortableJS Initialization

```js
function columnPanel() {
    return {
        init() {
            Sortable.create(this.$refs.colList, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'opacity-40',
                onEnd: (evt) => {
                    const headers = Alpine.store('spreadsheet').headers;
                    const [moved] = headers.splice(evt.oldIndex, 1);
                    headers.splice(evt.newIndex, 0, moved);
                    Alpine.store('spreadsheet').saveState(); // Persist layout change
                }
            });
        }
    }
}
```

---

## Preview Table Component

The table uses the `.doc-table` styling system set up in Step 02. Table styles are toggled instantly via the `:data-table-style` attribute.

```html
<div class="flex-1 overflow-auto p-8 bg-[--bg]">
    <!-- Simulated A4 print page container -->
    <div id="print-area" class="bg-white text-black shadow-lg mx-auto" style="min-height: 297mm; max-width: 210mm; padding: 15mm;">
        
        <!-- Letterhead Mount Block (Step 06) -->
        <div x-show="$store.letterhead.showOnPrint" class="mb-6">
            <!-- Render dynamic letterhead template based on active layout -->
        </div>

        <!-- Document Body Table -->
        <table class="doc-table">
            <thead>
                <tr>
                    <template x-for="col in $store.spreadsheet.headers.filter(c => c.visible)" :key="col.key">
                        <th
                            :class="col.align === 'right' ? 'align-right' : ''"
                            @click="sortBy(col.key)"
                            class="cursor-pointer select-none hover:bg-[--surface-2] transition-colors"
                        >
                            <div class="flex items-center gap-1" :class="col.align === 'right' ? 'justify-end' : 'justify-start'">
                                <span x-text="col.label"></span>
                                <span class="text-xs text-[--accent]" x-show="sortCol === col.key" x-text="sortDir === 'asc' ? '↑' : '↓'"></span>
                            </div>
                        </th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, idx) in sortedRows" :key="idx">
                    <tr class="hover:bg-[--accent-subtle]">
                        <template x-for="col in $store.spreadsheet.headers.filter(c => c.visible)" :key="col.key">
                            <td
                                :class="col.align === 'right' ? 'align-right' : ''"
                                x-text="formatCell(row[col.key], col.type)"
                            ></td>
                        </template>
                    </tr>
                </template>
            </tbody>
            <!-- Optional Summary/Totals Row -->
            <tfoot x-show="hasNumericColumns()">
                <tr>
                    <template x-for="(col, i) in $store.spreadsheet.headers.filter(c => c.visible)" :key="col.key">
                        <td
                            :class="col.align === 'right' ? 'align-right' : ''"
                            x-text="i === 0 ? 'Total' : summaryFor(col)"
                        ></td>
                    </template>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
```

---

## Sorting Logic (Alpine)

Wrap the main workspace view inside an Alpine component that handles local sorting:

```js
// Alpine component wrapper for app workspace
function appWorkspace() {
    return {
        sortCol: null,
        sortDir: 'asc',

        sortBy(key) {
            if (this.sortCol === key) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortCol = key;
                this.sortDir = 'asc';
            }
        },

        get sortedRows() {
            const rows = [...Alpine.store('spreadsheet').rows];
            if (!this.sortCol) return rows;
            
            return rows.sort((a, b) => {
                const va = a[this.sortCol];
                const vb = b[this.sortCol];
                
                // Numeric extraction for intelligent sorting
                const numA = parseFloat(String(va).replace(/[^\d.-]/g, ''));
                const numB = parseFloat(String(vb).replace(/[^\d.-]/g, ''));
                
                if (!isNaN(numA) && !isNaN(numB)) {
                    return this.sortDir === 'asc' ? numA - numB : numB - numA;
                }
                
                // Fallback to alphabetical sorting
                return this.sortDir === 'asc' 
                    ? String(va).localeCompare(String(vb))
                    : String(vb).localeCompare(String(va));
            });
        },

        hasNumericColumns() {
            return Alpine.store('spreadsheet').headers
                .filter(h => h.visible)
                .some(h => ['integer', 'decimal', 'currency'].includes(h.type));
        }
    }
}
```

---

## Cell Formatter (Core)

Formats cell outputs on screen and in print layouts. 
*(Note: Step 09 replaces this function to implement custom localization formatting, including Lakh/Crore grouping).*

```js
function formatCell(value, type) {
    if (value === '' || value == null) return '';
    const str = String(value).trim();

    switch (type) {
        case 'integer': {
            const n = parseInt(str.replace(/,/g, ''));
            return isNaN(n) ? str : n.toLocaleString();
        }
        case 'decimal': {
            const n = parseFloat(str.replace(/,/g, ''));
            return isNaN(n) ? str : n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 });
        }
        case 'currency': {
            // Default symbol matches whatever currency is detected or chosen
            const symbol = str.match(/^[৳$£€¥Tk]+/)?.[0] || '$';
            const n = parseFloat(str.replace(/[^\d.-]/g, ''));
            return isNaN(n) ? str : `${symbol}${n.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
        }
        case 'percentage': {
            const n = parseFloat(str.replace(/[^\d.-]/g, ''));
            return isNaN(n) ? str : `${n}%`;
        }
        case 'leading-zero-code':
            return str; // Preserve telephone numbers or product codes exactly as-is
        case 'date':
            return str; // Normalized to formatted date string
        case 'boolean':
            return /^(true|yes|y|1)$/i.test(str) ? '✓' : '✗';
        default:
            return str;
    }
}
```

---

## Column Summaries

Calculates columns' grand totals automatically for the table footer:

```js
function summaryFor(col) {
    if (!['integer', 'decimal', 'currency'].includes(col.type)) return '';
    
    const values = Alpine.store('spreadsheet').rows
        .map(r => parseFloat(String(r[col.key]).replace(/[^\d.-]/g, '')))
        .filter(n => !isNaN(n));
        
    if (values.length === 0) return '';
    const total = values.reduce((sum, val) => sum + val, 0);
    
    return formatCell(total, col.type);
}
```

---

## Dynamic Workspace Toolbar

A sleek sticky toolbar anchors the preview view controls. Buttons map directly to the `theme` store written in Step 02.

```html
<div class="flex items-center gap-3 px-6 h-[--toolbar-height] border-b border-[--border] bg-[--surface] no-print">
    
    <!-- Theme picker -->
    <div class="flex items-center gap-1.5 text-xs font-medium">
        <span class="text-[--ink-muted]">Theme</span>
        <select @change="$store.theme.setTheme($event.target.value)" :value="$store.theme.current" class="bg-[--surface-2] border border-[--border] rounded px-2 py-1 text-[--ink]">
            <option value="indigo">Indigo</option>
            <option value="emerald">Emerald</option>
            <option value="sunset">Sunset</option>
            <option value="mono-pro">Mono Pro</option>
            <option value="ocean">Ocean</option>
            <option value="oxblood">Oxblood</option>
            <option value="grape">Grape</option>
        </select>
    </div>

    <!-- Table style -->
    <div class="flex items-center gap-1.5 text-xs font-medium">
        <span class="text-[--ink-muted]">Style</span>
        <select @change="$store.theme.setTableStyle($event.target.value)" :value="$store.theme.tableStyle" class="bg-[--surface-2] border border-[--border] rounded px-2 py-1 text-[--ink]">
            <option value="clean">Clean</option>
            <option value="ruled">Ruled</option>
            <option value="boxed">Boxed</option>
            <option value="striped">Striped</option>
            <option value="shaded-header">Shaded Header</option>
        </select>
    </div>

    <!-- Page orientation (toggles print styling layout in step 02) -->
    <div class="flex items-center gap-1.5 text-xs font-medium">
        <span class="text-[--ink-muted]">Page</span>
        <select @change="$store.theme.setOrientation($event.target.value)" :value="$store.theme.orientation" class="bg-[--surface-2] border border-[--border] rounded px-2 py-1 text-[--ink]">
            <option value="portrait">A4 Portrait</option>
            <option value="landscape">A4 Landscape</option>
        </select>
    </div>

    <!-- Spacer -->
    <div class="flex-1"></div>

    <!-- Dark Mode Button Toggle -->
    <button @click="$store.theme.toggleDark()" class="p-1.5 rounded-md hover:bg-[--surface-2] text-[--ink-2] transition-colors" title="Toggle Dark/Light Mode">
        <span x-show="!$store.theme.dark">🌙</span>
        <span x-show="$store.theme.dark">☀️</span>
    </button>

    <!-- Print / Export trigger -->
    <button @click="handlePrint()" class="bg-[--btn-primary-bg] text-[--btn-primary-fg] px-4 py-1.5 rounded-md text-xs font-semibold hover:opacity-90 active:scale-[0.98] transition-all shadow-[--shadow-sm]">
        🖨️ Print / Save PDF
    </button>
</div>
```

---

## Print Handler

Tracks telemetry before engaging standard system print operations.

```js
function handlePrint() {
    trackEvent('print');
    
    // Tiny delay allows trackEvent fetch request to begin sending
    setTimeout(() => {
        window.print();
    }, 80);
}
```

---

## Checklist
- [ ] Table preview container matches class `.doc-table` defined in Step 02.
- [ ] Toolbar controls use direct `$store.theme` calls for theme setting, table styles, dark mode, and page orientation.
- [ ] Columns can be dragged to reorder; layout order changes trigger state saves.
- [ ] Columns show/hide instantly via `visible` boolean filter flags.
- [ ] Header labels remain editable on focus loss (`@blur` triggers save).
- [ ] User can override column data types; alignment shifts automatically on change.
- [ ] Column header clicks sort records in asc/desc cycles.
- [ ] Columns footers compute totals/averages automatically for numeric columns.
- [ ] System prints cleanly without toolbar components (`.no-print` classes configured).
- [ ] A4 layout simulator scales cleanly across display formats.
