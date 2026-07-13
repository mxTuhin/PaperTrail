# Step 04 — File Ingestion & Type Detection Engine

## Goal
Build the client-side file parsing layer. The user drops a file; the browser reads it, picks the active sheet, detects column types via heuristics, and populates the reactive spreadsheet state — all without database storage or server round-trips.

## What to Build

### File Drop Zone (Alpine + HTML)

The drop zone is visually dynamic, responding to drag-and-drop actions by altering its background and border states.

```html
<div
    x-data="fileDropZone()"
    @drop.prevent="handleDrop($event)"
    @dragover.prevent="isDragging = true"
    @dragleave="isDragging = false"
    :class="isDragging ? 'border-[--accent] bg-[--accent-subtle]' : 'border-[--border]'"
    class="border-2 border-dashed rounded-lg p-12 text-center transition-all bg-[--surface]"
>
    <input type="file" accept=".xlsx,.xls,.csv,.tsv" @change="handleFile($event)" class="hidden" x-ref="fileInput">
    <p class="text-sm text-[--ink-2] mb-3">Drop your Excel (.xlsx, .xls) or CSV / TSV file here</p>
    <button @click="$refs.fileInput.click()" class="bg-[--btn-primary-bg] text-[--btn-primary-fg] px-4 py-2 rounded-md text-xs font-semibold hover:opacity-90 active:scale-[0.98] transition-all">
        Browse Files
    </button>
</div>
```

### Alpine Component: `fileDropZone()`

```js
function fileDropZone() {
    return {
        isDragging: false,
        
        handleDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            if (file) this.processFile(file);
        },
        
        handleFile(event) {
            const file = event.target.files[0];
            if (file) this.processFile(file);
        },
        
        processFile(file) {
            // Trigger usage event logging (non-blocking telemetry)
            trackEvent('upload');
            // Hand file parsing over to the reactive store
            Alpine.store('spreadsheet').loadFile(file);
        }
    }
}
```

### Alpine Store: `spreadsheet`

The central state manager for the spreadsheet data model.

```js
document.addEventListener('alpine:init', () => {
    Alpine.store('spreadsheet', {
        raw: null,          // Original SheetJS workbook object
        sheetNames: [],     // Array of available sheets in the workbook
        activeSheet: null,  // Currently active sheet name
        headers: [],        // Managed headers: [ { key, label, type, align, visible } ]
        rows: [],           // Row items: [ { col_0: value, col_1: value, ... } ]
        skipRows: 0,        // Custom row skip count override
        headerRow: 0,       // Calculated index of header row
        isLoaded: false,

        loadFile(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const data = new Uint8Array(e.target.result);
                this.raw = XLSX.read(data, { type: 'array' });
                this.sheetNames = this.raw.SheetNames;
                this.selectSheet(this.sheetNames[0]);
            };
            reader.readAsArrayBuffer(file);
        },

        selectSheet(name) {
            this.activeSheet = name;
            const ws = this.raw.Sheets[name];
            // Read spreadsheet matrix raw including empty cells
            const json = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
            this.parseData(json);
        },

        parseData(rows) {
            if (rows.length === 0) return;

            // Apply skip rows and auto-detect header row index
            const offsetRows = rows.slice(this.skipRows);
            this.headerRow = detectHeaderRow(offsetRows);
            
            const header = offsetRows[this.headerRow] || [];
            const dataRows = offsetRows.slice(this.headerRow + 1).filter(r => r.some(c => c !== ''));

            // Parse columns metadata & extract types
            this.headers = header.map((h, i) => {
                const columnValues = dataRows.map(r => r[i]);
                const detectionResult = detectColumnType(columnValues);
                
                return {
                    key: `col_${i}`,
                    label: String(h).trim() || `Column ${i + 1}`,
                    type: detectionResult.type,
                    confidence: detectionResult.confidence,
                    align: 'left', // will align based on type below
                    visible: true
                };
            });

            // Set column alignments: numbers/currencies right-aligned, strings left-aligned
            this.headers.forEach(h => {
                h.align = ['integer', 'decimal', 'currency', 'percentage', 'date', 'datetime'].includes(h.type)
                    ? 'right' : 'left';
            });

            // Convert raw matrices to dynamic object rows keyed by column codes
            this.rows = dataRows.map(r => {
                const obj = {};
                this.headers.forEach((h, i) => { 
                    obj[h.key] = r[i] ?? ''; 
                });
                return obj;
            });

            this.isLoaded = true;
            
            // Telemetry logging for processing rates
            trackEvent('process', { rows: this.rows.length, cols: this.headers.length });
        },

        // Helper method placeholder for UI custom configuration state saves
        saveState() {
            // Intentionally local/reactive for the session context
        }
    });
});
```

---

## Type Detection Heuristic Engine

A sampling algorithm inspects cell entries to vote on the most accurate format type.

```js
/**
 * Detect the most specific type for a column of values.
 * @param {Array} samples - raw cell values
 * @returns {{ type: string, confidence: number }}
 */
function detectColumnType(samples) {
    // Filter empty values and limit sampling size for performance
    const nonEmpty = samples.filter(v => v !== '' && v != null).slice(0, 200);
    if (nonEmpty.length === 0) return { type: 'text', confidence: 1 };

    const matchers = [
        { type: 'leading-zero-code', test: v => /^0\d+$/.test(String(v).trim()) },
        { type: 'boolean',           test: v => /^(true|false|yes|no|y|n)$/i.test(String(v).trim()) },
        { type: 'integer',           test: v => /^-?(\d{1,3}(,\d{3})*|\d+)$/.test(String(v).trim().replace(/[()]/g, '-')) },
        { type: 'decimal',           test: v => /^-?[\d,]*\.\d+$/.test(String(v).trim()) },
        { type: 'currency',          test: v => /^[৳$£€¥Tk][-\d,.]+$|^[-\d,.]+[৳$£€¥]$/.test(String(v).trim()) },
        { type: 'percentage',        test: v => /^-?\d+(\.\d+)?%$/.test(String(v).trim()) },
        { type: 'date',              test: v => isLikelyDate(String(v).trim()) },
        { type: 'datetime',          test: v => isLikelyDatetime(String(v).trim()) },
        { type: 'text',              test: () => true },
    ];

    const THRESHOLD = 0.90; // 90% sample compliance required

    for (const matcher of matchers) {
        const matchCount = nonEmpty.filter(v => matcher.test(v)).length;
        const confidence = matchCount / nonEmpty.length;
        
        if (confidence >= THRESHOLD) {
            return { type: matcher.type, confidence };
        }
    }

    return { type: 'text', confidence: 1 };
}

function isLikelyDate(str) {
    const formats = [
        /^\d{2}\/\d{2}\/\d{4}$/,     // DD/MM/YYYY (South Asian default)
        /^\d{4}-\d{2}-\d{2}$/,        // ISO 8601
        /^\d{2}-\d{2}-\d{4}$/,        // DD-MM-YYYY
        /^\d{1,2}\s\w{3}\s\d{4}$/,    // 12 Jan 2024
    ];
    return formats.some(re => re.test(str)) && !isNaN(Date.parse(str));
}

function isLikelyDatetime(str) {
    return str.includes(':') && !isNaN(Date.parse(str));
}

function detectHeaderRow(rows) {
    // Heuristic: identify first row where the majority of values are text strings
    for (let i = 0; i < Math.min(10, rows.length); i++) {
        const row = rows[i] || [];
        const textCells = row.filter(c => c !== '' && isNaN(Number(c))).length;
        const totalValued = row.filter(c => c !== '').length;
        
        if (totalValued > 0 && (textCells / totalValued > 0.6)) {
            return i;
        }
    }
    return 0;
}
```

---

## Edge Case Implementations

### Large Document Handlers
- Row sampling caps calculations to 200 entries.
- If row lengths exceed 10,000 items, render a notification modal: *"Large spreadsheet detected. Rendering the first 3,000 rows in live preview. All records will be formatted during print/export."*

### Bengali Character Sets (CSVs)
- When reading raw comma/tab-separated sheets containing Bengali characters, pass the correct UTF-8 codepage parameters.

```js
// SheetJS UTF-8 support mapping
const wb = XLSX.read(data, { type: 'array', codepage: 65001 });
```

---

## Checklist
- [ ] Drag-and-drop container supports classes connected to theme accent tokens (e.g. `bg-[--accent-subtle]`).
- [ ] `spreadsheet` store processes raw documents into rows and header structures.
- [ ] `headers` entries assign `type` correctly as a string (`detectionResult.type`) rather than the raw result object.
- [ ] Column configurations fallback to correct text/numerical alignments on state shifts.
- [ ] Store implements `saveState()` helper block to prevent undefined reference errors when called in subsequent UI steps.
- [ ] Sampling counts limit calculations to prevent UI thread blocking.
- [ ] Character parser handles Bengali UTF-8 codepages.
- [ ] Telemetry events track upload steps cleanly.
