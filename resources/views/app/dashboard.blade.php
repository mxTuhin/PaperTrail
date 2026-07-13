<!DOCTYPE html>
<html lang="en" data-theme="indigo">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Summary Dashboard — PaperTrail</title>

    <!-- Bunny Fonts: Instrument Sans -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Tailwind JIT CDN Compiler -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Instrument Sans"', 'sans-serif'] } } } };
    </script>

    <!-- Static Styles (Matches main design system) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <!-- Client-side Alpine.js (self-hosted, reliable) -->
    <script defer src="{{ asset('js/vendor/alpine.min.js') }}"></script>

    <style>
        body {
            background: #eef0f6;
            margin: 0 !important;
            padding: 0 !important;
            font-family: 'Instrument Sans', sans-serif;
        }
        [x-cloak] { display: none !important; }

        @media screen {
            .dash-sheet {
                max-width: 210mm;
                background: white;
                margin: 2rem auto;
                padding: 15mm;
                border: 1px solid #e2e5ee;
                border-radius: 6px;
                box-shadow: 0 4px 24px -10px rgba(0,0,0,0.12);
            }
        }

        @media print {
            @page { margin: 12mm; }
            body { background: white !important; }
            .no-print { display: none !important; }
            .dash-scroll { overflow: visible !important; }
            .dash-sheet {
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                max-width: none !important;
                width: 100% !important;
            }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body class="antialiased text-slate-800 h-screen flex flex-col overflow-hidden" x-data="dashboardController()" x-init="init()">

    <!-- ══════════ TOP TOOLBAR (no-print) ══════════ -->
    <div class="no-print bg-[#141428] text-white py-3 px-6 flex items-center justify-between border-b border-slate-800 shrink-0">
        <span class="font-mono text-xs uppercase tracking-[0.2em] font-semibold text-slate-300">
            PAPER/TRAIL <span class="text-slate-500 font-normal">| Summary Dashboard</span>
        </span>
        <div class="flex items-center gap-6">
            <button @click="window.print()" x-show="hasData" class="bg-[#3b3c95] hover:bg-[#2e2f7a] text-white px-4 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all shadow-sm active:scale-[0.98]">
                🖨️ Print / Save PDF
            </button>
            <button @click="window.close()" class="text-xs text-slate-400 hover:text-white font-medium transition-colors">✕ Close</button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden">

        <!-- ══════════ CONTROLS SIDEBAR (no-print) ══════════ -->
        <aside class="no-print w-72 shrink-0 bg-white border-r border-slate-200 overflow-y-auto p-5 space-y-5" x-show="hasData" x-cloak>

            <!-- Value column -->
            <div>
                <label class="block text-[10px] uppercase text-slate-400 font-semibold mb-1.5 tracking-wider">Value column</label>
                <select x-model="config.valueCol" @change="compute()" class="w-full text-xs border border-slate-300 rounded-lg px-2 py-2 bg-white text-slate-800">
                    <template x-for="h in numericCols" :key="h.key">
                        <option :value="h.key" x-text="h.label"></option>
                    </template>
                </select>
                <p x-show="numericCols.length === 0" class="text-[11px] text-amber-600 mt-1">No numeric columns detected in this sheet.</p>
            </div>

            <!-- Group by -->
            <div>
                <label class="block text-[10px] uppercase text-slate-400 font-semibold mb-1.5 tracking-wider">Group by</label>
                <select x-model="config.groupCol" @change="compute()" class="w-full text-xs border border-slate-300 rounded-lg px-2 py-2 bg-white text-slate-800">
                    <option value="">None (KPIs only)</option>
                    <template x-for="h in headers" :key="h.key">
                        <option :value="h.key" x-text="h.label + (isDateCol(h.key) ? '  (date)' : '')"></option>
                    </template>
                </select>
            </div>

            <!-- Interval (dates only) -->
            <div x-show="groupColIsDate()">
                <label class="block text-[10px] uppercase text-slate-400 font-semibold mb-1.5 tracking-wider">Time grouping</label>
                <div class="flex rounded-lg border border-slate-300 overflow-hidden">
                    <button type="button" @click="config.interval='month'; compute()" :class="config.interval==='month' ? 'bg-[#3b3c95] text-white' : 'bg-white text-slate-700'" class="flex-1 py-1.5 text-xs font-medium">Each month</button>
                    <button type="button" @click="config.interval='quarter'; compute()" :class="config.interval==='quarter' ? 'bg-[#3b3c95] text-white' : 'bg-white text-slate-700'" class="flex-1 py-1.5 text-xs font-medium border-l border-slate-300">3 months</button>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="pt-4 border-t border-slate-100 space-y-2">
                <span class="block text-[10px] uppercase text-slate-400 font-semibold mb-1 tracking-wider">KPI cards</span>
                <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" x-model="config.showSum" class="rounded text-[#3b3c95]"> Total</label>
                <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" x-model="config.showAvg" class="rounded text-[#3b3c95]"> Average</label>
                <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" x-model="config.showCount" class="rounded text-[#3b3c95]"> Record count</label>
                <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" x-model="config.showMax" class="rounded text-[#3b3c95]"> Highest</label>
                <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" x-model="config.showMin" class="rounded text-[#3b3c95]"> Lowest</label>
                <label class="flex items-center gap-2 text-xs text-slate-600" x-show="summary.timelineText"><input type="checkbox" x-model="config.showTimeline" class="rounded text-[#3b3c95]"> Timeline span</label>
            </div>

            <!-- KPI columns -->
            <div>
                <label class="block text-[10px] uppercase text-slate-400 font-semibold mb-1.5 tracking-wider">Card layout</label>
                <div class="flex rounded-lg border border-slate-300 overflow-hidden">
                    <button type="button" @click="config.cardColumns=2" :class="config.cardColumns===2 ? 'bg-[#3b3c95] text-white' : 'bg-white text-slate-700'" class="flex-1 py-1.5 text-xs">2 cols</button>
                    <button type="button" @click="config.cardColumns=3" :class="config.cardColumns===3 ? 'bg-[#3b3c95] text-white' : 'bg-white text-slate-700'" class="flex-1 py-1.5 text-xs border-l border-slate-300">3 cols</button>
                </div>
            </div>

            <!-- Sections -->
            <div class="pt-4 border-t border-slate-100 space-y-2">
                <span class="block text-[10px] uppercase text-slate-400 font-semibold mb-1 tracking-wider">Sections</span>
                <label class="flex items-center gap-2 text-xs text-slate-600" x-show="config.groupCol"><input type="checkbox" x-model="config.showChart" class="rounded text-[#3b3c95]"> Trend chart</label>
                <label class="flex items-center gap-2 text-xs text-slate-600" x-show="config.groupCol"><input type="checkbox" x-model="config.showTable" class="rounded text-[#3b3c95]"> Breakdown table</label>
                <label class="flex items-center gap-2 text-xs text-slate-600" x-show="config.groupCol"><input type="checkbox" x-model="config.showBars" class="rounded text-[#3b3c95]"> Breakdown bars</label>
                <p x-show="!config.groupCol" class="text-[11px] text-slate-400">Pick a “Group by” column to enable chart &amp; table.</p>
            </div>
        </aside>

        <!-- ══════════ REPORT SCROLL AREA ══════════ -->
        <main class="flex-1 overflow-y-auto dash-scroll">

            <!-- Empty state -->
            <div x-show="!hasData" x-cloak class="max-w-md mx-auto mt-24 text-center px-6">
                <h1 class="text-lg font-bold text-slate-800">No dashboard data</h1>
                <p class="text-sm text-slate-500 mt-2">Open this report from the PaperTrail workspace using the “Open Dashboard” button.</p>
                <a href="{{ route('app') }}" class="inline-block mt-5 text-xs font-semibold text-[#3b3c95] hover:underline">← Back to workspace</a>
            </div>

            <!-- Report sheet (the print target) -->
            <div class="dash-sheet" x-show="hasData" x-cloak>

                <!-- Header -->
                <div class="flex justify-between items-start border-b-2 border-[#3b3c95] pb-5 mb-8">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900" x-text="letterhead.companyName || 'Summary Report'"></h1>
                        <p class="text-xs text-slate-500 mt-1" x-text="letterhead.docTitle || 'Business Summary'"></p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase font-mono tracking-wider text-[#3b3c95] font-semibold">Summary Dashboard</span>
                        <p class="text-xs text-slate-500 mt-1" x-text="'Date: ' + dateStr"></p>
                        <p class="text-[11px] text-slate-400 mt-0.5" x-text="valueLabel ? 'Metric: ' + valueLabel : ''"></p>
                    </div>
                </div>

                <!-- KPI cards -->
                <div class="grid gap-4 mb-8" :class="config.cardColumns === 2 ? 'grid-cols-2' : 'grid-cols-3'">
                    <div x-show="config.showSum" class="border border-slate-200 rounded-xl p-5 bg-slate-50/70 break-inside-avoid">
                        <span class="text-[10px] uppercase font-mono tracking-wider text-slate-400">Total</span>
                        <div class="text-2xl font-bold text-slate-900 mt-1 tabular-nums" x-text="summary.sumText"></div>
                        <span class="text-[10px] text-slate-400" x-text="valueLabel"></span>
                    </div>
                    <div x-show="config.showAvg" class="border border-slate-200 rounded-xl p-5 bg-slate-50/70 break-inside-avoid">
                        <span class="text-[10px] uppercase font-mono tracking-wider text-slate-400">Average</span>
                        <div class="text-2xl font-bold text-slate-900 mt-1 tabular-nums" x-text="summary.avgText"></div>
                        <span class="text-[10px] text-slate-400">Per record</span>
                    </div>
                    <div x-show="config.showCount" class="border border-slate-200 rounded-xl p-5 bg-slate-50/70 break-inside-avoid">
                        <span class="text-[10px] uppercase font-mono tracking-wider text-slate-400">Records</span>
                        <div class="text-2xl font-bold text-slate-900 mt-1 tabular-nums" x-text="summary.count"></div>
                        <span class="text-[10px] text-slate-400">Rows counted</span>
                    </div>
                    <div x-show="config.showMax" class="border border-slate-200 rounded-xl p-5 bg-slate-50/70 break-inside-avoid">
                        <span class="text-[10px] uppercase font-mono tracking-wider text-slate-400">Highest</span>
                        <div class="text-2xl font-bold text-slate-900 mt-1 tabular-nums" x-text="summary.maxText"></div>
                        <span class="text-[10px] text-slate-400">Single record</span>
                    </div>
                    <div x-show="config.showMin" class="border border-slate-200 rounded-xl p-5 bg-slate-50/70 break-inside-avoid">
                        <span class="text-[10px] uppercase font-mono tracking-wider text-slate-400">Lowest</span>
                        <div class="text-2xl font-bold text-slate-900 mt-1 tabular-nums" x-text="summary.minText"></div>
                        <span class="text-[10px] text-slate-400">Single record</span>
                    </div>
                    <div x-show="config.showTimeline && summary.timelineText" class="border border-slate-200 rounded-xl p-5 bg-slate-50/70 break-inside-avoid">
                        <span class="text-[10px] uppercase font-mono tracking-wider text-slate-400">Timeline</span>
                        <div class="text-base font-bold text-slate-900 mt-1.5" x-text="summary.timelineText"></div>
                        <span class="text-[10px] text-slate-400">Date span</span>
                    </div>
                </div>

                <!-- Trend chart (vertical bars) -->
                <div x-show="config.showChart && config.groupCol && summary.groups.length > 0" class="border border-slate-200 rounded-xl p-6 bg-white mb-6 break-inside-avoid">
                    <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-5" x-text="(config.interval === 'quarter' ? 'Quarterly ' : 'Monthly ') + 'trend — ' + valueLabel"></h2>
                    <div class="flex items-end gap-1.5 h-44 border-b border-slate-200">
                        <template x-for="g in summary.groups" :key="g.label">
                            <div class="flex-1 flex flex-col items-center justify-end h-full group">
                                <span class="text-[8px] text-slate-500 mb-1 tabular-nums whitespace-nowrap" x-text="g.pct + '%'"></span>
                                <div class="w-full bg-[#3b3c95] rounded-t transition-all" :style="`height: ${maxTotal ? Math.max(2, (g.total / maxTotal) * 100) : 2}%`" :title="g.label + ': ' + g.valueText"></div>
                            </div>
                        </template>
                    </div>
                    <div class="flex gap-1.5 mt-1.5">
                        <template x-for="g in summary.groups" :key="g.label">
                            <span class="flex-1 text-center text-[8px] text-slate-500 leading-tight break-words" x-text="g.label"></span>
                        </template>
                    </div>
                </div>

                <!-- Breakdown table -->
                <div x-show="config.showTable && config.groupCol && summary.groups.length > 0" class="border border-slate-200 rounded-xl p-6 bg-white mb-6 break-inside-avoid">
                    <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4" x-text="'Breakdown by ' + summary.groupLabel"></h2>
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="border-b-2 border-slate-300 text-slate-400 uppercase text-[9px] tracking-wider">
                                <th class="py-2 pr-2 text-left" x-text="summary.groupLabel || 'Group'"></th>
                                <th class="py-2 px-2 text-right">Total</th>
                                <th class="py-2 px-2 text-right">Records</th>
                                <th class="py-2 px-2 text-right">Average</th>
                                <th class="py-2 pl-2 text-right">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="g in summary.groups" :key="g.label">
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 pr-2 font-medium text-slate-700" x-text="g.label"></td>
                                    <td class="py-2 px-2 text-right tabular-nums text-slate-800" x-text="g.valueText"></td>
                                    <td class="py-2 px-2 text-right tabular-nums text-slate-500" x-text="g.count"></td>
                                    <td class="py-2 px-2 text-right tabular-nums text-slate-500" x-text="g.avgText"></td>
                                    <td class="py-2 pl-2 text-right tabular-nums text-slate-800" x-text="g.pct + '%'"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-slate-300 font-bold text-slate-900">
                                <td class="py-2 pr-2" x-text="'Total (' + summary.groups.length + ')'"></td>
                                <td class="py-2 px-2 text-right tabular-nums" x-text="summary.sumText"></td>
                                <td class="py-2 px-2 text-right tabular-nums" x-text="summary.count"></td>
                                <td class="py-2 px-2 text-right tabular-nums" x-text="summary.avgText"></td>
                                <td class="py-2 pl-2 text-right">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Breakdown bars -->
                <div x-show="config.showBars && config.groupCol && summary.groups.length > 0" class="border border-slate-200 rounded-xl p-6 bg-white break-inside-avoid">
                    <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4" x-text="'Share by ' + summary.groupLabel"></h2>
                    <div class="space-y-3">
                        <template x-for="g in summary.groups" :key="g.label">
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-medium text-slate-600">
                                    <span x-text="g.label"></span>
                                    <span class="tabular-nums" x-text="g.valueText + '  (' + g.pct + '%)'"></span>
                                </div>
                                <div class="w-full h-2 rounded bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded bg-[#3b3c95]" :style="`width: ${g.pct}%`"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <p class="text-[10px] text-slate-400 mt-10 text-center">Generated with PaperTrail — your files never leave your device.</p>
            </div>
        </main>
    </div>

    <script>
        const PT_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        function dashboardController() {
            return {
                hasData: false,
                headers: [],
                rows: [],
                letterhead: {},
                dateStr: '',
                numberFormat: 'western',
                config: {
                    valueCol: '', groupCol: '', interval: 'month', cardColumns: 3,
                    showSum: true, showAvg: true, showCount: true, showMax: true, showMin: true, showTimeline: true,
                    showChart: true, showTable: true, showBars: true,
                },
                summary: {
                    count: 0, sumText: '0', avgText: '0', minText: '0', maxText: '0',
                    timelineText: '', groupLabel: '', groups: [],
                },

                init() {
                    const raw = sessionStorage.getItem('pt_dashboard_data');
                    if (!raw) { return; }
                    try {
                        const data = JSON.parse(raw);
                        this.headers = data.headers || [];
                        this.rows = data.rows || [];
                        this.letterhead = data.letterhead || {};
                        this.dateStr = data.dateStr || new Date().toLocaleDateString('en-GB');
                        this.numberFormat = data.numberFormat || 'western';
                        if (!this.rows.length) { return; }

                        // Sensible defaults: a "value-ish" numeric column + first date/category column.
                        const nums = this.numericCols;
                        this.config.valueCol = (nums[0] || {}).key || '';
                        const dateCol = this.headers.find((h) => this.isDateCol(h.key));
                        const catCol = this.headers.find((h) => this.isLowCardinalityText(h.key));
                        this.config.groupCol = (dateCol || catCol || {}).key || '';

                        this.hasData = true;
                        this.compute();
                    } catch (e) {
                        this.hasData = false;
                    }
                },

                get numericCols() {
                    return this.headers.filter((h) => ['integer', 'decimal', 'currency', 'percentage'].includes(h.type));
                },
                // Always reflect the column currently chosen in "Value column".
                get valueLabel() {
                    const col = this.headers.find((h) => h.key === this.config.valueCol);
                    return col ? col.label : '';
                },
                get maxTotal() {
                    return this.summary.groups.reduce((m, g) => Math.max(m, g.total), 0);
                },
                isDateCol(key) {
                    const h = this.headers.find((x) => x.key === key);
                    return !!h && ['date', 'datetime'].includes(h.type);
                },
                groupColIsDate() {
                    return this.isDateCol(this.config.groupCol);
                },
                isLowCardinalityText(key) {
                    const h = this.headers.find((x) => x.key === key);
                    if (!h || h.type !== 'text') { return false; }
                    const uniq = new Set(this.rows.map((r) => String(r[key]).trim()));
                    return uniq.size > 1 && uniq.size <= 15;
                },

                // ── Number handling ──────────────────────────────────────────
                parseNum(v) {
                    if (typeof v === 'number') { return v; }
                    const s = String(v ?? '').replace(/[^0-9.\-]/g, '');
                    const n = parseFloat(s);
                    return isNaN(n) ? NaN : n;
                },
                fmt(n) {
                    if (n == null || isNaN(n)) { return '0'; }
                    const r = Math.round(Number(n) * 100) / 100;
                    return this.numberFormat === 'bd' ? this.formatBD(r) : r.toLocaleString(undefined, { maximumFractionDigits: 2 });
                },
                formatBD(num) {
                    const parts = (Math.round(Number(num) * 100) / 100).toString().split('.');
                    let intPart = parts[0];
                    const sign = intPart.startsWith('-') ? '-' : '';
                    intPart = intPart.replace('-', '');
                    let lastThree = intPart.slice(-3);
                    const other = intPart.slice(0, -3);
                    if (other) { lastThree = ',' + lastThree; }
                    const formatted = other.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
                    return sign + formatted + (parts[1] ? '.' + parts[1] : '');
                },

                // ── Date order detection (per the DD>12 technique) ───────────
                // Scan the group column: if any first field exceeds 12 the column
                // is day-first (DD.MM.YYYY / DD/MM/YYYY); otherwise the month comes
                // first (MM.DD.YYYY / MM/DD/YYYY).
                detectDateOrder(key) {
                    let anyFirstOver12 = false;
                    for (const r of this.rows) {
                        const p = String(r[key] ?? '').trim().split(/[.\/\-]/);
                        if (p.length < 3) { continue; }
                        const first = parseInt(p[0], 10);
                        if (first > 12) { anyFirstOver12 = true; break; }
                    }
                    return anyFirstOver12 ? 'dmy' : 'mdy';
                },
                parseMonthYear(str, order) {
                    const parts = String(str ?? '').trim().split(/[.\/\-\s]+/).filter(Boolean);
                    if (parts.length < 3) { return null; }
                    let month, year;
                    if (parts[0].length === 4) {
                        // ISO-ish YYYY-MM-DD
                        year = parseInt(parts[0], 10);
                        month = parseInt(parts[1], 10);
                    } else {
                        month = order === 'dmy' ? parseInt(parts[1], 10) : parseInt(parts[0], 10);
                        year = parseInt(parts[2], 10);
                    }
                    if (isNaN(month) || isNaN(year) || month < 1 || month > 12) { return null; }
                    if (year < 100) { year += 2000; }
                    return { month, year };
                },

                // A trailing "Total"/"Sum" row in the source sheet would be
                // double-counted, so drop it before summarising. Detected either
                // by a total-like keyword in the last row, or by its value equal
                // to the sum of all preceding rows in the value column.
                looksLikeTotalRow(row) {
                    const kw = /\b(grand\s*total|sub\s*total|total|totals|sum|balance)\b/i;
                    return this.headers.some((h) => {
                        const v = String(row[h.key] ?? '').trim();
                        return v && kw.test(v);
                    });
                },
                effectiveRows(vKey) {
                    const rows = this.rows;
                    if (rows.length < 3) { return rows; }
                    const last = rows[rows.length - 1];

                    if (this.looksLikeTotalRow(last)) { return rows.slice(0, -1); }

                    if (vKey) {
                        const lastVal = this.parseNum(last[vKey]);
                        if (!isNaN(lastVal) && lastVal !== 0) {
                            let restSum = 0, restCount = 0;
                            for (let i = 0; i < rows.length - 1; i++) {
                                const n = this.parseNum(rows[i][vKey]);
                                if (!isNaN(n)) { restSum += n; restCount++; }
                            }
                            if (restCount >= 2 && Math.abs(restSum - lastVal) / Math.max(Math.abs(lastVal), 1) < 0.005) {
                                return rows.slice(0, -1);
                            }
                        }
                    }
                    return rows;
                },

                // ── Compute everything from the raw rows ─────────────────────
                compute() {
                    const vKey = this.config.valueCol;
                    const rows = this.effectiveRows(vKey);
                    const nums = [];
                    rows.forEach((r) => { const n = this.parseNum(r[vKey]); if (!isNaN(n)) { nums.push(n); } });
                    const sum = nums.reduce((a, b) => a + b, 0);

                    this.summary.count = rows.length;
                    this.summary.sumText = this.fmt(sum);
                    this.summary.avgText = this.fmt(nums.length ? sum / nums.length : 0);
                    this.summary.minText = this.fmt(nums.length ? Math.min(...nums) : 0);
                    this.summary.maxText = this.fmt(nums.length ? Math.max(...nums) : 0);

                    const gCol = this.headers.find((h) => h.key === this.config.groupCol);
                    this.summary.groupLabel = gCol ? gCol.label : '';
                    this.summary.timelineText = '';
                    this.summary.groups = [];

                    if (!this.config.groupCol) { return; }
                    this.summary.groups = this.groupColIsDate()
                        ? this.groupByDate(vKey, sum, rows)
                        : this.groupByCategory(vKey, sum, rows);
                },

                groupByDate(vKey, grandTotal, rows) {
                    const gKey = this.config.groupCol;
                    const order = this.detectDateOrder(gKey);
                    const map = new Map();
                    let minK = null, maxK = null, minMY = null, maxMY = null;

                    rows.forEach((r) => {
                        const my = this.parseMonthYear(r[gKey], order);
                        if (!my) { return; }
                        const val = this.parseNum(r[vKey]) || 0;
                        let key, label, sortKey;
                        if (this.config.interval === 'quarter') {
                            const q = Math.floor((my.month - 1) / 3) + 1;
                            key = my.year + '-Q' + q;
                            label = 'Q' + q + ' ' + my.year;
                            sortKey = my.year * 10 + q;
                        } else {
                            key = my.year + '-' + String(my.month).padStart(2, '0');
                            label = PT_MONTHS[my.month - 1] + ' ' + my.year;
                            sortKey = my.year * 100 + my.month;
                        }
                        if (!map.has(key)) { map.set(key, { label, sortKey, total: 0, count: 0 }); }
                        const g = map.get(key);
                        g.total += val;
                        g.count++;

                        const mk = my.year * 100 + my.month;
                        if (minK === null || mk < minK) { minK = mk; minMY = my; }
                        if (maxK === null || mk > maxK) { maxK = mk; maxMY = my; }
                    });

                    if (minMY && maxMY) {
                        this.summary.timelineText = PT_MONTHS[minMY.month - 1] + ' ' + minMY.year + ' – ' + PT_MONTHS[maxMY.month - 1] + ' ' + maxMY.year;
                    }
                    return this.finalizeGroups([...map.values()].sort((a, b) => a.sortKey - b.sortKey), grandTotal);
                },

                groupByCategory(vKey, grandTotal, rows) {
                    const gKey = this.config.groupCol;
                    const map = new Map();
                    rows.forEach((r) => {
                        const cat = String(r[gKey] ?? '').trim() || '—';
                        const val = this.parseNum(r[vKey]) || 0;
                        if (!map.has(cat)) { map.set(cat, { label: cat, total: 0, count: 0 }); }
                        const g = map.get(cat);
                        g.total += val;
                        g.count++;
                    });
                    const arr = [...map.values()].sort((a, b) => b.total - a.total).slice(0, 15);
                    return this.finalizeGroups(arr, grandTotal);
                },

                finalizeGroups(arr, grandTotal) {
                    const denom = grandTotal || arr.reduce((s, g) => s + g.total, 0) || 1;
                    arr.forEach((g) => {
                        g.pct = Math.round((g.total / denom) * 100);
                        g.valueText = this.fmt(g.total);
                        g.avgText = this.fmt(g.count ? g.total / g.count : 0);
                    });
                    return arr;
                },
            };
        }
    </script>
</body>
</html>
