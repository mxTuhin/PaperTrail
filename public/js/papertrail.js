/*
 * PaperTrail — client-side engine
 * ────────────────────────────────────────────────────────────────────────
 * Loaded as a classic (non-deferred) script in the layout <head>, BEFORE the
 * Alpine CDN bundle, so:
 *   1. helper functions below are global before Alpine evaluates expressions;
 *   2. the `alpine:init` store registrations attach before Alpine boots.
 *
 * Covers Steps 04 (ingestion + type detection), 05 (cell formatting +
 * summaries), 06 (letterhead store), 07 (trackEvent), 09 (BD localization).
 */

/* ═══════════════════════════════════════════════════════════════════════
   USAGE TELEMETRY (Step 07) — best-effort, never blocks the UI.
   ═══════════════════════════════════════════════════════════════════════ */
async function trackEvent(event, meta = {}) {
    try {
        const tokenEl = document.querySelector('meta[name="csrf-token"]');
        await fetch('/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': tokenEl ? tokenEl.content : '',
            },
            body: JSON.stringify({
                event,
                row_count: meta.rows ?? null,
                col_count: meta.cols ?? null,
                filename_hash: meta.filenameHash ?? null,
            }),
        });
    } catch {
        /* silently ignore — telemetry is non-critical */
    }
}

/* ═══════════════════════════════════════════════════════════════════════
   TYPE DETECTION ENGINE (Step 04)
   Sample-and-vote: pick the most specific type meeting the threshold.
   ═══════════════════════════════════════════════════════════════════════ */
function detectColumnType(samples) {
    const nonEmpty = samples.filter((v) => v !== '' && v != null).slice(0, 200);
    if (nonEmpty.length === 0) {
        return { type: 'text', confidence: 1 };
    }

    const matchers = [
        { type: 'leading-zero-code', test: (v) => /^0\d+$/.test(String(v).trim()) },
        { type: 'boolean', test: (v) => /^(true|false|yes|no|y|n)$/i.test(String(v).trim()) },
        { type: 'integer', test: (v) => /^-?(\d{1,3}(,\d{3})*|\d+)$/.test(String(v).trim().replace(/[()]/g, '-')) },
        { type: 'decimal', test: (v) => /^-?[\d,]*\.\d+$/.test(String(v).trim()) },
        { type: 'currency', test: (v) => /^[৳$£€¥]|Tk/i.test(String(v).trim()) && /\d/.test(String(v)) },
        { type: 'percentage', test: (v) => /^-?\d+(\.\d+)?%$/.test(String(v).trim()) },
        { type: 'date', test: (v) => isLikelyDate(String(v).trim()) },
        { type: 'datetime', test: (v) => isLikelyDatetime(String(v).trim()) },
        { type: 'text', test: () => true },
    ];

    const THRESHOLD = 0.9;

    for (const matcher of matchers) {
        const matchCount = nonEmpty.filter((v) => matcher.test(v)).length;
        const confidence = matchCount / nonEmpty.length;
        if (confidence >= THRESHOLD) {
            return { type: matcher.type, confidence };
        }
    }

    return { type: 'text', confidence: 1 };
}

function isLikelyDate(str) {
    const formats = [
        /^\d{2}\/\d{2}\/\d{4}$/, // DD/MM/YYYY (BD default)
        /^\d{4}-\d{2}-\d{2}$/, // ISO 8601
        /^\d{2}-\d{2}-\d{4}$/, // DD-MM-YYYY
        /^\d{1,2}\s\w{3}\s\d{4}$/, // 12 Jan 2024
    ];
    return formats.some((re) => re.test(str)) && !isNaN(Date.parse(str));
}

function isLikelyDatetime(str) {
    return str.includes(':') && !isNaN(Date.parse(str));
}

function detectHeaderRow(rows) {
    for (let i = 0; i < Math.min(10, rows.length); i++) {
        const row = rows[i] || [];
        const textCells = row.filter((c) => c !== '' && isNaN(Number(c))).length;
        const totalValued = row.filter((c) => c !== '').length;
        if (totalValued > 0 && textCells / totalValued > 0.6) {
            return i;
        }
    }
    return 0;
}

/* ═══════════════════════════════════════════════════════════════════════
   NUMBER / DATE FORMATTING (Steps 05 + 09)
   ═══════════════════════════════════════════════════════════════════════ */

/** BD lakh/crore grouping: 1234567.89 → "12,34,567.89" */
function formatBDNumber(n) {
    if (isNaN(n)) return '';
    const str = Math.abs(n).toString();
    const [intPart, decPart] = str.split('.');

    let result;
    if (intPart.length > 3) {
        result = intPart.slice(-3);
        let remaining = intPart.slice(0, -3);
        while (remaining.length > 2) {
            result = remaining.slice(-2) + ',' + result;
            remaining = remaining.slice(0, -2);
        }
        result = remaining + ',' + result;
    } else {
        result = intPart;
    }

    if (decPart) result += '.' + decPart;
    return (n < 0 ? '-' : '') + result;
}

/** Parse a date string into parts. Assumes day-first for numeric D/M/Y
 *  (Bangladesh / international default). Returns null if unparseable. */
function parseDateParts(str) {
    const s = String(str).trim();

    // ISO-ish, year first: YYYY-MM-DD, YYYY/MM/DD, YYYY.MM.DD
    let m = s.match(/^(\d{4})[-./](\d{1,2})[-./](\d{1,2})$/);
    if (m) return { d: +m[3], mo: +m[2], y: +m[1] };

    // Day first: D/M/Y, D-M-Y, D.M.Y (2- or 4-digit year)
    m = s.match(/^(\d{1,2})[-./](\d{1,2})[-./](\d{2,4})$/);
    if (m) {
        let y = +m[3];
        if (y < 100) y += 2000;
        return { d: +m[1], mo: +m[2], y };
    }

    // Textual months, e.g. "12 Jan 2024"
    const d2 = new Date(s);
    if (!isNaN(d2.getTime())) {
        return { d: d2.getDate(), mo: d2.getMonth() + 1, y: d2.getFullYear() };
    }
    return null;
}

/**
 * Reformat a date string to the chosen format. `original` (the default) and
 * any unparseable value return the source value untouched.
 */
function formatDate(raw, format) {
    if (!format || format === 'original') return raw;

    const p = parseDateParts(raw);
    if (!p) return raw;

    const dd = String(p.d).padStart(2, '0');
    const mm = String(p.mo).padStart(2, '0');
    const yyyy = String(p.y).padStart(4, '0');
    const yy = yyyy.slice(-2);

    switch (format) {
        case 'dd/mm/yyyy':
            return `${dd}/${mm}/${yyyy}`;
        case 'dd.mm.yyyy':
            return `${dd}.${mm}.${yyyy}`;
        case 'dd.mm.yy':
            return `${dd}.${mm}.${yy}`;
        case 'mm/dd/yyyy':
            return `${mm}/${dd}/${yyyy}`;
        case 'mm.dd.yyyy':
            return `${mm}.${dd}.${yyyy}`;
        case 'mm.dd.yy':
            return `${mm}.${dd}.${yy}`;
        case 'yyyy-mm-dd':
            return `${yyyy}-${mm}-${dd}`;
        case 'yyyy.mm.dd':
            return `${yyyy}.${mm}.${dd}`;
        default:
            return raw;
    }
}

/**
 * Format a cell for display. Values are shown EXACTLY as the source document
 * provides them — no number/currency reformatting. The only optional transform
 * is date reformatting, and only when the user explicitly picks a date format
 * (default `original` leaves dates untouched). Type detection stays a backend
 * concern used for alignment and totals eligibility.
 */
function formatCell(value, type) {
    if (value === '' || value == null) return '';
    const str = String(value);

    if (type === 'date') {
        const settings = (typeof Alpine !== 'undefined' && Alpine.store('settings')) || {};
        return formatDate(str, settings.dateFormat || 'original');
    }

    return str;
}

/**
 * Column grand total for the optional summary (tfoot) row. This is a value we
 * compute, so it IS formatted (grouping + currency symbol) per settings.
 */
function summaryFor(col) {
    if (!['integer', 'decimal', 'currency'].includes(col.type)) return '';

    const values = Alpine.store('spreadsheet').rows
        .map((r) => parseFloat(String(r[col.key]).replace(/[^\d.-]/g, '')))
        .filter((n) => !isNaN(n));

    if (values.length === 0) return '';
    const total = values.reduce((sum, val) => sum + val, 0);

    const settings = (typeof Alpine !== 'undefined' && Alpine.store('settings')) || {};
    const useBD = settings.numberFormat === 'bd';
    const minFractions = col.type === 'integer' ? 0 : 2;

    // No currency symbol on totals: columns may each use a different currency,
    // so a single global symbol would be wrong. Totals show the grouped number.
    return useBD
        ? formatBDNumber(parseFloat(total.toFixed(minFractions)))
        : total.toLocaleString(undefined, { minimumFractionDigits: minFractions, maximumFractionDigits: 2 });
}

const RIGHT_ALIGNED_TYPES = ['integer', 'decimal', 'currency', 'percentage', 'date', 'datetime'];

/* ═══════════════════════════════════════════════════════════════════════
   ALPINE STORES — registered before Alpine boots.
   ═══════════════════════════════════════════════════════════════════════ */
document.addEventListener('alpine:init', () => {
    /* ─── Spreadsheet store (Step 04) ─────────────────────────────────── */
    Alpine.store('spreadsheet', {
        raw: null,
        sheetNames: [],
        activeSheet: null,
        headers: [], // [{ key, label, type, confidence, align, visible }]
        rows: [],
        skipRows: 0,
        headerRow: 0,
        isLoaded: false,

        // ── Summary Dashboard (Step 11) lives in its OWN window (/app/dashboard).
        //    Here we only track whether the current sheet can drive one, to gate
        //    the "Open Dashboard" launch button. All compute happens on that page.
        dashboard: {
            isGenerated: false,
        },

        loadFile(file) {
            // Files are parsed entirely in the browser (never uploaded), so the
            // only limit is client memory. Cap at 30 MB to avoid tab crashes on
            // very large workbooks.
            const MAX_FILE_BYTES = 30 * 1024 * 1024;
            if (file && file.size > MAX_FILE_BYTES) {
                alert(
                    'This file is ' +
                        (file.size / (1024 * 1024)).toFixed(1) +
                        ' MB. PaperTrail supports spreadsheets up to 30 MB — please remove unused columns/rows or split the sheet and try again.'
                );
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                const data = new Uint8Array(e.target.result);
                this.raw = XLSX.read(data, { type: 'array', codepage: 65001 });
                this.sheetNames = this.raw.SheetNames;
                trackEvent('upload', { filenameHash: null });
                this.selectSheet(this.sheetNames[0]);
            };
            reader.readAsArrayBuffer(file);
        },

        selectSheet(name) {
            this.activeSheet = name;
            const ws = this.raw.Sheets[name];
            // `raw: false` returns each cell's formatted text exactly as shown in
            // the source file (so dates stay as written, not Excel serial numbers).
            const json = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '', raw: false });
            this.parseData(json);
        },

        parseData(rows) {
            if (rows.length === 0) return;

            const offsetRows = rows.slice(this.skipRows);
            this.headerRow = detectHeaderRow(offsetRows);

            const header = offsetRows[this.headerRow] || [];
            const dataRows = offsetRows
                .slice(this.headerRow + 1)
                .filter((r) => r.some((c) => c !== ''));

            this.headers = header.map((h, i) => {
                const columnValues = dataRows.map((r) => r[i]);
                const detection = detectColumnType(columnValues);
                return {
                    key: `col_${i}`,
                    label: String(h).trim() || `Column ${i + 1}`,
                    type: detection.type,
                    confidence: detection.confidence,
                    align: RIGHT_ALIGNED_TYPES.includes(detection.type) ? 'right' : 'left',
                    visible: true,
                };
            });

            this.rows = dataRows.map((r) => {
                const obj = {};
                this.headers.forEach((h, i) => {
                    obj[h.key] = r[i] ?? '';
                });
                return obj;
            });

            this.isLoaded = true;
            this.initDashboard();
            trackEvent('process', { rows: this.rows.length, cols: this.headers.length });
        },

        /** Re-derive a column's alignment after a manual type override. */
        applyAlignment(col) {
            col.align = RIGHT_ALIGNED_TYPES.includes(col.type) ? 'right' : 'left';
        },

        /* The dashboard is available whenever the sheet has at least one numeric
           column to summarise. The standalone window does the rest. */
        initDashboard() {
            const hasNumeric = this.headers.some((h) => ['integer', 'decimal', 'currency'].includes(h.type));
            this.dashboard.isGenerated = hasNumeric && this.rows.length > 0;
        },

        saveState() {
            /* view state is reactive/session-only; kept as a safe no-op hook */
        },
    });

    /* ─── Settings store (Steps 05 + 09) ──────────────────────────────── */
    Alpine.store('settings', {
        // 'original' = keep dates exactly as the file provides (default).
        dateFormat: localStorage.getItem('pt-date-format') || 'original',
        // Totals row is opt-in and hidden by default.
        showTotals: localStorage.getItem('pt-show-totals') === 'true',
        // Bold the final data row (e.g. when the source's last row is a total).
        boldLastRow: localStorage.getItem('pt-bold-last-row') === 'true',
        // Table body font size in px (applies on screen and in print).
        tableFontSize: parseInt(localStorage.getItem('pt-table-font'), 10) || 11,
        // These only affect the computed totals row, never source cells.
        numberFormat: localStorage.getItem('pt-number-format') || 'western',
        currencySymbol: localStorage.getItem('pt-currency') || '৳',

        // Dynamic footer customizer parameters
        showFooter: localStorage.getItem('pt-show-footer') === 'true',
        footerLayout: localStorage.getItem('pt-footer-layout') || 'simple-text',
        footerText: localStorage.getItem('pt-footer-text') || 'Thank you for your business.',

        // Table header color customization overrides
        thBg: localStorage.getItem('pt-th-bg') || '',
        thText: localStorage.getItem('pt-th-text') || '',

        // Page top accent color strip parameter
        showTopBar: localStorage.getItem('pt-show-top-bar') === 'true',

        setDateFormat(format) {
            this.dateFormat = format;
            localStorage.setItem('pt-date-format', format);
        },
        setShowTotals(value) {
            this.showTotals = value;
            localStorage.setItem('pt-show-totals', value);
        },
        setBoldLastRow(value) {
            this.boldLastRow = value;
            localStorage.setItem('pt-bold-last-row', value);
        },
        setTableFontSize(px) {
            const n = Math.min(24, Math.max(7, parseInt(px, 10) || 11));
            this.tableFontSize = n;
            localStorage.setItem('pt-table-font', n);
        },
        setNumberFormat(format) {
            this.numberFormat = format;
            localStorage.setItem('pt-number-format', format);
        },
        setCurrencySymbol(sym) {
            this.currencySymbol = sym;
            localStorage.setItem('pt-currency', sym);
        },
        setShowFooter(value) {
            this.showFooter = value;
            localStorage.setItem('pt-show-footer', value);
        },
        setFooterLayout(layout) {
            this.footerLayout = layout;
            localStorage.setItem('pt-footer-layout', layout);
        },
        setFooterText(text) {
            this.footerText = text;
            localStorage.setItem('pt-footer-text', text);
        },
        setThBg(color) {
            this.thBg = color;
            localStorage.setItem('pt-th-bg', color);
        },
        setThText(color) {
            this.thText = color;
            localStorage.setItem('pt-th-text', color);
        },
        resetThColors() {
            this.thBg = '';
            this.thText = '';
            localStorage.removeItem('pt-th-bg');
            localStorage.removeItem('pt-th-text');
        },
        setShowTopBar(value) {
            this.showTopBar = value;
            localStorage.setItem('pt-show-top-bar', value);
        }
    });

    /* ─── Letterhead store (Step 06) ──────────────────────────────────── */
    Alpine.store('letterhead', {
        profiles: [],
        activeId: null,
        showOnPrint: true,

        init() {
            const saved = localStorage.getItem('pt-letterheads');
            this.profiles = saved ? JSON.parse(saved) : [this.defaultProfile()];
            this.activeId = localStorage.getItem('pt-active-letterhead') ?? this.profiles[0].id;
            this.showOnPrint = localStorage.getItem('pt-show-letterhead') !== 'false';
        },

        get active() {
            return this.profiles.find((p) => p.id === this.activeId) ?? this.profiles[0];
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
            const src = this.profiles.find((p) => p.id === id);
            if (!src) return;
            const copy = { ...src, id: Date.now().toString(), name: src.name + ' (copy)' };
            this.profiles.push(copy);
            this.activeId = copy.id;
            this.save();
        },

        removeProfile(id) {
            if (this.profiles.length === 1) return;
            this.profiles = this.profiles.filter((p) => p.id !== id);
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
                datePosition: 'top', // 'top' (in letterhead) | 'bottom' (under table)
                logoBase64: '',
                logoHeight: 36,
                layout: 'split-header',
                size: 'compact',
                showDivider: true,
                dividerWeight: 'medium',
            };
        },
    });
});
