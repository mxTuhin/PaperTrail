# Step 11 — Auto-Dashboard & Business Summary Generator

## Goal
Build a client-side **Auto-Dashboard Engine** that scans the parsed spreadsheet structure on ingestion, detects key business metrics, and generates a print-safe summary dashboard section. 

While the dashboard behaves smartly **by default**, the user retains **complete control** to configure, override, toggle, and customize every widget and breakdown to suit their report requirements.

---

## 1. Customization-First Data Model

The `dashboard` state in the Alpine `spreadsheet` store stores both computed values and user configuration overrides:

```js
dashboard: {
    // General switches
    enabled: false,         // Main toggle to display dashboard in print
    isGenerated: false,     // True if numeric data exists to build a dashboard

    // UI Configuration Overrides (User Controls)
    config: {
        primaryNumCol: null,    // Key of numeric column to sum/average
        groupCol: null,         // Key of column to group data by (Category or Date)
        groupInterval: 'auto',  // 'auto' | 'day' | 'month' | 'year' (if groupCol is a Date)
        
        // Individual card display checkboxes
        showSum: true,
        showAvg: true,
        showCount: true,
        showTimeline: true,
        showBreakdown: true,
        
        // Layout overrides
        cardColumns: 3,         // 1 | 2 | 3 (grid layout size)
    },

    // Aggregated outputs (read by the UI)
    summary: {
        sum: 0,
        avg: 0,
        min: 0,
        max: 0,
        count: 0,
        timelineText: '',
        breakdownLabel: '',
        breakdownItems: []      // [ { name, value, raw, pct } ]
    }
}
```

---

## 2. Dynamic Summary Calculator (Heuristic + User Overrides)

The calculation engine reads configuration controls. When the user changes a setting in the sidebar (e.g., switches the primary numeric column), `recalculate()` runs instantly to update the preview sheet.

```js
Alpine.store('spreadsheet', {
    // ... standard spreadsheet state

    initDashboard() {
        // Initial setup on file load
        const numericCols = this.headers.filter(h => ['integer', 'decimal', 'currency'].includes(h.type));
        if (numericCols.length === 0) {
            this.dashboard.isGenerated = false;
            return;
        }

        // Set default configurations based on heuristics
        this.dashboard.config.primaryNumCol = numericCols.find(h => 
            /total|amount|price|sum|balance/i.test(h.label)
        )?.key || numericCols[numericCols.length - 1].key;

        // Find potential categorizers (2-10 distinct values) or date fields
        const dateCols = this.headers.filter(h => ['date', 'datetime'].includes(h.type));
        const categoryCols = this.headers.filter(h => {
            if (h.type !== 'text') return false;
            const uniqueVals = new Set(this.rows.map(r => String(r[h.key]).trim()));
            return uniqueVals.size > 1 && uniqueVals.size <= 10;
        });

        this.dashboard.config.groupCol = dateCols[0]?.key || categoryCols[0]?.key || null;
        this.dashboard.config.groupInterval = 'auto';
        
        this.recalculateDashboard();
    },

    recalculateDashboard() {
        const rows = this.rows;
        const config = this.dashboard.config;
        if (rows.length === 0 || !config.primaryNumCol) return;

        const numCol = this.headers.find(h => h.key === config.primaryNumCol);
        if (!numCol) return;

        // 1. Gather primary numeric values
        const values = rows.map(r => parseFloat(String(r[numCol.key]).replace(/[^\d.-]/g, ''))).filter(n => !isNaN(n));
        
        this.dashboard.summary.sum = values.reduce((a, b) => a + b, 0);
        this.dashboard.summary.count = rows.length;
        this.dashboard.summary.avg = values.length ? (this.dashboard.summary.sum / values.length) : 0;
        this.dashboard.summary.min = values.length ? Math.min(...values) : 0;
        this.dashboard.summary.max = values.length ? Math.max(...values) : 0;

        // 2. Perform Grouping / Pivot analysis
        this.dashboard.summary.breakdownItems = [];
        this.dashboard.summary.timelineText = '';

        if (config.groupCol) {
            const groupColMeta = this.headers.find(h => h.key === config.groupCol);
            
            if (groupColMeta) {
                this.dashboard.summary.breakdownLabel = groupColMeta.label;

                if (['date', 'datetime'].includes(groupColMeta.type)) {
                    // DATE-BASED TREND GROUPING
                    this.groupDataByDate(groupColMeta, numCol);
                } else {
                    // CATEGORICAL GROUPING
                    this.groupDataByCategory(groupColMeta, numCol);
                }
            }
        }

        this.dashboard.isGenerated = true;
    },

    groupDataByDate(dateCol, numCol) {
        const rows = this.rows;
        const config = this.dashboard.config;
        const dateParsed = rows.map(r => {
            const d = new Date(String(r[dateCol.key]).trim());
            const val = parseFloat(String(r[numCol.key]).replace(/[^\d.-]/g, '')) || 0;
            return { date: d, val, valid: !isNaN(d.getTime()) };
        }).filter(item => item.valid);

        if (dateParsed.length === 0) return;

        const times = dateParsed.map(r => r.date.getTime());
        const minTime = Math.min(...times);
        const maxTime = Math.max(...times);
        const daysDiff = (maxTime - minTime) / (1000 * 60 * 60 * 24);

        // Date Range format text
        const dMin = new Date(minTime);
        const dMax = new Date(maxTime);
        this.dashboard.summary.timelineText = `${dMin.getDate()}/${dMin.getMonth()+1}/${dMin.getFullYear()} - ${dMax.getDate()}/${dMax.getMonth()+1}/${dMax.getFullYear()}`;

        // Determine date aggregation interval
        let interval = config.groupInterval;
        if (interval === 'auto') {
            interval = daysDiff > 730 ? 'year' : (daysDiff < 30 ? 'day' : 'month');
        }

        const dateGroupMap = {};
        dateParsed.forEach(item => {
            let key = '';
            const d = item.date;
            if (interval === 'year') {
                key = `${d.getFullYear()}`;
            } else if (interval === 'month') {
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                key = `${months[d.getMonth()]} ${d.getFullYear()}`;
            } else {
                key = `${d.getDate()} ${d.toLocaleString('en-US', { month: 'short' })}`;
            }

            if (!dateGroupMap[key]) {
                dateGroupMap[key] = { name: key, value: 0, sortKey: d.getTime() };
            }
            dateGroupMap[key].value += item.val;
        });

        // Map to sorted timelines
        const totalSum = this.dashboard.summary.sum;
        this.dashboard.summary.breakdownItems = Object.values(dateGroupMap)
            .sort((a, b) => a.sortKey - b.sortKey)
            .map(g => ({
                name: g.name,
                value: formatCell(g.value, numCol.type),
                raw: g.value,
                pct: totalSum > 0 ? Math.round((g.value / totalSum) * 100) : 0
            }));
    },

    groupDataByCategory(catCol, numCol) {
        const rows = this.rows;
        const totalSum = this.dashboard.summary.sum;
        const groupMap = {};

        rows.forEach(r => {
            const name = String(r[catCol.key]).trim() || 'Uncategorized';
            const val = parseFloat(String(r[numCol.key]).replace(/[^\d.-]/g, '')) || 0;
            
            if (!groupMap[name]) {
                groupMap[name] = { name, value: 0 };
            }
            groupMap[name].value += val;
        });

        this.dashboard.summary.breakdownItems = Object.values(groupMap).map(g => ({
            name: g.name,
            value: formatCell(g.value, numCol.type),
            raw: g.value,
            pct: totalSum > 0 ? Math.round((g.value / totalSum) * 100) : 0
        })).sort((a, b) => b.raw - a.raw);
    }
});
```

---

## 3. Customization Controls Sidebar UI (Left Panel)

This panel allows users to choose what statistics to calculate and which breakdowns to show on the printed sheet.

```html
<div class="space-y-4 pt-4 border-t border-slate-100 no-print" x-show="$store.spreadsheet.dashboard.isGenerated">
    <div class="flex items-center justify-between">
        <h3 class="font-bold text-xs uppercase tracking-wider text-[--ink-muted]">Dashboard Controls</h3>
        <input type="checkbox" x-model="$store.spreadsheet.dashboard.enabled" class="rounded border-slate-300 text-[#3b3c95]">
    </div>

    <div class="space-y-3" x-show="$store.spreadsheet.dashboard.enabled">
        <!-- 1. Value Column Selection -->
        <div>
            <label class="block text-[10px] uppercase text-[--ink-muted] font-medium mb-1">Value Column</label>
            <select 
                x-model="$store.spreadsheet.dashboard.config.primaryNumCol" 
                @change="$store.spreadsheet.recalculateDashboard()"
                class="w-full text-xs border border-[--border] rounded-lg px-2 py-1.5 bg-white text-[--ink]"
            >
                <template x-for="h in $store.spreadsheet.headers.filter(col => ['integer', 'decimal', 'currency'].includes(col.type))" :key="h.key">
                    <option :value="h.key" x-text="h.label"></option>
                </template>
            </select>
        </div>

        <!-- 2. Category / Trend Grouping Column -->
        <div>
            <label class="block text-[10px] uppercase text-[--ink-muted] font-medium mb-1">Group By</label>
            <select 
                x-model="$store.spreadsheet.dashboard.config.groupCol" 
                @change="$store.spreadsheet.recalculateDashboard()"
                class="w-full text-xs border border-[--border] rounded-lg px-2 py-1.5 bg-white text-[--ink]"
            >
                <option value="">None (KPI Cards Only)</option>
                <template x-for="h in $store.spreadsheet.headers" :key="h.key">
                    <option :value="h.key" x-text="h.label"></option>
                </template>
            </select>
        </div>

        <!-- 3. Dynamic Date Intervals (Only shown if groupCol is Date type) -->
        <div x-show="['date', 'datetime'].includes($store.spreadsheet.headers.find(h => h.key === $store.spreadsheet.dashboard.config.groupCol)?.type)">
            <label class="block text-[10px] uppercase text-[--ink-muted] font-medium mb-1">Time Interval</label>
            <select 
                x-model="$store.spreadsheet.dashboard.config.groupInterval" 
                @change="$store.spreadsheet.recalculateDashboard()"
                class="w-full text-xs border border-[--border] rounded-lg px-2 py-1 bg-white text-[--ink]"
            >
                <option value="auto">Auto-detect Range</option>
                <option value="day">Day</option>
                <option value="month">Month</option>
                <option value="year">Year</option>
            </select>
        </div>

        <!-- 4. Toggle KPI Widgets (What to see and what not) -->
        <div class="space-y-1.5 pt-2 border-t border-slate-100">
            <span class="block text-[10px] uppercase text-[--ink-muted] font-medium mb-1">Visible Metrics</span>
            
            <label class="flex items-center gap-2 text-xs">
                <input type="checkbox" x-model="$store.spreadsheet.dashboard.config.showSum" class="rounded text-[#3b3c95]">
                <span>Show Total Sum</span>
            </label>
            <label class="flex items-center gap-2 text-xs">
                <input type="checkbox" x-model="$store.spreadsheet.dashboard.config.showAvg" class="rounded text-[#3b3c95]">
                <span>Show Average</span>
            </label>
            <label class="flex items-center gap-2 text-xs">
                <input type="checkbox" x-model="$store.spreadsheet.dashboard.config.showCount" class="rounded text-[#3b3c95]">
                <span>Show Record Count</span>
            </label>
            <label class="flex items-center gap-2 text-xs" x-show="$store.spreadsheet.dashboard.summary.timelineText">
                <input type="checkbox" x-model="$store.spreadsheet.dashboard.config.showTimeline" class="rounded text-[#3b3c95]">
                <span>Show Timeline Span</span>
            </label>
            <label class="flex items-center gap-2 text-xs" x-show="$store.spreadsheet.dashboard.config.groupCol">
                <input type="checkbox" x-model="$store.spreadsheet.dashboard.config.showBreakdown" class="rounded text-[#3b3c95]">
                <span>Show Breakdown Chart</span>
            </label>
        </div>

        <!-- 5. Layout options -->
        <div class="pt-2 border-t border-slate-100">
            <label class="block text-[10px] uppercase text-[--ink-muted] font-medium mb-1">KPI Column Layout</label>
            <div class="flex rounded-md border border-[--border] overflow-hidden">
                <button type="button" @click="$store.spreadsheet.dashboard.config.cardColumns = 2" :class="$store.spreadsheet.dashboard.config.cardColumns === 2 ? 'bg-[#3b3c95] text-white' : 'bg-white text-[--ink]'" class="flex-1 text-center py-1 text-xs">2 Cols</button>
                <button type="button" @click="$store.spreadsheet.dashboard.config.cardColumns = 3" :class="$store.spreadsheet.dashboard.config.cardColumns === 3 ? 'bg-[#3b3c95] text-white' : 'bg-white text-[--ink]'" class="flex-1 text-center py-1 text-xs">3 Cols</button>
            </div>
        </div>
    </div>
</div>
```

---

## 4. UI Dashboard Render Markup (Inside `print-area`)

```html
<div x-show="$store.spreadsheet.dashboard.enabled && $store.spreadsheet.dashboard.isGenerated" class="mb-8 space-y-6">
    
    <!-- 1. Custom Grid columns count layout -->
    <div class="grid gap-4"
         :class="{
             'grid-cols-2': $store.spreadsheet.dashboard.config.cardColumns === 2,
             'grid-cols-3': $store.spreadsheet.dashboard.config.cardColumns === 3
         }">
         
        <!-- Total Sum Widget -->
        <div x-show="$store.spreadsheet.dashboard.config.showSum" class="border border-[--border] rounded-xl p-4 bg-[--surface] flex flex-col justify-between break-inside-avoid">
            <span class="text-[10px] uppercase font-mono tracking-wider text-[--ink-muted]">Total Sum</span>
            <span class="text-lg font-bold mt-1 text-[--ink]" x-text="$store.spreadsheet.dashboard.summary.sum"></span>
            <span class="text-[9px] text-[--ink-muted] mt-1.5" x-text="'Accumulated numeric values'"></span>
        </div>

        <!-- Average Widget -->
        <div x-show="$store.spreadsheet.dashboard.config.showAvg" class="border border-[--border] rounded-xl p-4 bg-[--surface] flex flex-col justify-between break-inside-avoid">
            <span class="text-[10px] uppercase font-mono tracking-wider text-[--ink-muted]">Average</span>
            <span class="text-lg font-bold mt-1 text-[--ink]" x-text="$store.spreadsheet.dashboard.summary.avg"></span>
            <span class="text-[9px] text-[--ink-muted] mt-1.5" x-text="'Range: ' + $store.spreadsheet.dashboard.summary.min + ' - ' + $store.spreadsheet.dashboard.summary.max"></span>
        </div>

        <!-- Record Count Widget -->
        <div x-show="$store.spreadsheet.dashboard.config.showCount" class="border border-[--border] rounded-xl p-4 bg-[--surface] flex flex-col justify-between break-inside-avoid">
            <span class="text-[10px] uppercase font-mono tracking-wider text-[--ink-muted]">Record Count</span>
            <span class="text-lg font-bold mt-1 text-[--ink]" x-text="$store.spreadsheet.dashboard.summary.count"></span>
            <span class="text-[9px] text-[--ink-muted] mt-1.5" x-text="'Total spreadsheet lines parsed'"></span>
        </div>

        <!-- Timeline Span Widget -->
        <div x-show="$store.spreadsheet.dashboard.config.showTimeline && $store.spreadsheet.dashboard.summary.timelineText" class="border border-[--border] rounded-xl p-4 bg-[--surface] flex flex-col justify-between break-inside-avoid">
            <span class="text-[10px] uppercase font-mono tracking-wider text-[--ink-muted]">Timeline Span</span>
            <span class="text-sm font-bold mt-2 text-[--ink]" x-text="$store.spreadsheet.dashboard.summary.timelineText"></span>
            <span class="text-[9px] text-[--ink-muted] mt-1.5" x-text="'Date distribution duration'"></span>
        </div>
    </div>

    <!-- 2. Categorization / Date Trend breakdowns -->
    <div x-show="$store.spreadsheet.dashboard.config.showBreakdown && $store.spreadsheet.dashboard.config.groupCol && $store.spreadsheet.dashboard.summary.breakdownItems.length > 0" 
         class="border border-[--border] rounded-xl p-5 bg-[--surface] space-y-4 break-inside-avoid">
        
        <h4 class="text-xs font-bold text-[--ink] uppercase tracking-wider" x-text="'Breakdown by ' + $store.spreadsheet.dashboard.summary.breakdownLabel"></h4>
        
        <div class="space-y-3">
            <template x-for="item in $store.spreadsheet.summary.breakdownItems" :key="item.name">
                <div class="space-y-1">
                    <div class="flex justify-between text-xs font-semibold text-[--ink-2]">
                        <span x-text="item.name"></span>
                        <span x-text="item.value + ' (' + item.pct + '%)'"></span>
                    </div>
                    <!-- Pure CSS percentage meter bar (100% print-safe) -->
                    <div class="w-full h-2 rounded bg-slate-100 overflow-hidden relative">
                        <div class="h-full bg-[--accent] rounded transition-all duration-500" :style="`width: ${item.pct}%`"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
```

---

## Checklist
- [ ] Config panel provides dropdown select parameters for primary value column and category group column.
- [ ] Recalculation logic updates metrics dynamically when configurations are altered.
- [ ] User can check/uncheck individual metrics (Sum, Average, Count, Timeline) to hide or show them in the final print.
- [ ] Layout grid adapts dynamically between 2-column or 3-column rows based on user options.
- [ ] Group intervals (Day, Month, Year) can be manually forced for date columns via controls.
- [ ] Elements print cleanly and layout calculations remain 100% local.
- [ ] Timeline metrics hide dynamically on sheets lacking parseable dates.
- [ ] Custom selections are maintained during sheet pivots.
- [ ] Layout preservation breaks avoid cards splitting across pages (`break-inside: avoid`).
