<!DOCTYPE html>
<html lang="en" data-theme="indigo">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-FYF7989GH9"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-FYF7989GH9');
    </script>

    <title>Print Report — PaperTrail</title>

    <!-- Bunny Fonts: Instrument Sans -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Tailwind JIT CDN Compiler -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bg: 'var(--bg)',
                        surface: 'var(--surface)',
                        'surface-2': 'var(--surface-2)',
                        'surface-3': 'var(--surface-3)',
                        border: 'var(--border)',
                        'border-2': 'var(--border-2)',
                        ink: 'var(--ink)',
                        'ink-2': 'var(--ink-2)',
                        'ink-muted': 'var(--ink-muted)',
                        accent: 'var(--accent)',
                        'accent-2': 'var(--accent-2)',
                        'accent-fg': 'var(--accent-fg)',
                        'accent-subtle': 'var(--accent-subtle)',
                        'accent-muted': 'var(--accent-muted)',
                        'accent-emphasis': 'var(--accent-emphasis)',
                    },
                    fontFamily: {
                        sans: ['"Instrument Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Static Styles (Matches main design system) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <!-- Client-side Alpine.js (self-hosted, reliable) -->
    <script defer src="{{ asset('js/vendor/alpine.min.js') }}"></script>

    <style>
        /* PDF print page background must be fully white */
        body {
            background: white !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        @media print {
            @page {
                margin: 10mm;
            }

            .a4-print-sheet {
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
                width: 100% !important;
            }

            .no-print {
                display: none !important;
            }
        }

        @media screen {
            .a4-print-sheet {
                max-width: 210mm;
                min-height: 297mm;
                background: white;
                margin: 2rem auto;
                padding: 10mm;
                border: 1px solid var(--border);
                border-radius: var(--radius-xs);
                box-shadow: 0 4px 20px -8px rgba(0, 0, 0, 0.05);
            }

            :root[data-orientation="landscape"] .a4-print-sheet {
                max-width: 297mm;
                min-height: 210mm;
            }
        }

        /* Bold last row style configuration */
        .doc-table tbody tr.pt-bold-row td {
            font-weight: 700;
            color: var(--ink);
        }
    </style>
</head>

<body class="antialiased" x-data="printController()" x-init="initPrint()" :data-table-style="tableStyle">

    <!-- ══════════ FLOATING PREVIEW CONTROLLER TOOLBAR (no-print) ══════════ -->
    <div
        class="no-print bg-[#141428] text-white py-3 px-6 flex items-center justify-between border-b border-slate-800 sticky top-0 z-50">
        <div class="flex items-center gap-2">
            <span class="font-mono text-xs uppercase tracking-[0.2em] font-semibold text-slate-300">
                PAPER/TRAIL <span class="text-slate-500 font-normal">| Print Preview</span>
            </span>
        </div>
        <div class="flex items-center gap-6">
            <!-- Orientation Toggler -->
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400">Layout:</span>
                <div class="inline-flex rounded-md border border-slate-700 overflow-hidden bg-slate-900 p-0.5">
                    <button type="button" @click="toggleOrientation('portrait')"
                        :class="orientation === 'portrait' ? 'bg-[#3b3c95] text-white' : 'text-slate-400 hover:text-white'"
                        class="px-2.5 py-1 text-[11px] font-semibold rounded transition-all">
                        Portrait
                    </button>
                    <button type="button" @click="toggleOrientation('landscape')"
                        :class="orientation === 'landscape' ? 'bg-[#3b3c95] text-white' : 'text-slate-400 hover:text-white'"
                        class="px-2.5 py-1 text-[11px] font-semibold rounded transition-all">
                        Landscape
                    </button>
                </div>
            </div>

            <!-- Print Action -->
            <button @click="window.print()"
                class="bg-[#3b3c95] hover:bg-[#2e2f7a] text-white px-4 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all shadow-sm active:scale-[0.98]">
                🖨️ Print
            </button>

            <!-- Close tab -->
            <button @click="window.close()"
                class="text-xs text-slate-400 hover:text-white font-medium transition-colors">
                ✕ Close Preview
            </button>
        </div>
    </div>

    <!-- Print Simulator Sheet -->
    <div class="a4-print-sheet" id="print-area">

        <!-- ══════════ EXACT DYNAMIC LETTERHEAD BLOCKS (Matches app page view) ══════════ -->
        <div x-show="showLetterhead" class="mb-6">

            <!-- 1. Layout: split-header -->
            <div class="border-b-2 border-[#3b3c95] pb-6 mb-8 flex justify-between items-start"
                x-show="letterhead.layout === 'split-header'">
                <div class="space-y-1 flex items-center gap-4">
                    <img x-show="letterhead.logoBase64" :src="letterhead.logoBase64" class="shrink-0"
                        :style="'height: ' + (letterhead.logoHeight || 36) + 'px'">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-slate-900"
                            x-text="letterhead.companyName || 'Company Name'"></h2>
                        <p class="text-xs text-slate-500 whitespace-pre-line leading-relaxed"
                            x-text="letterhead.address || 'Address information'"></p>
                    </div>
                </div>
                <div class="text-right space-y-1 font-mono">
                    <h3 class="text-sm font-bold text-[#3b3c95] tracking-wider uppercase"
                        x-text="letterhead.docTitle || 'DOCUMENT'"></h3>
                    <p class="text-xs text-slate-500" x-show="letterhead.datePosition === 'top'"
                        x-text="'Date: ' + docDate()"></p>
                    <p class="text-xs text-slate-500" x-show="letterhead.statementFor"
                        x-text="'For: ' + letterhead.statementFor"></p>
                </div>
            </div>

            <!-- 2. Layout: minimal-rule -->
            <div class="text-center pb-4 mb-6 border-b border-slate-900" x-show="letterhead.layout === 'minimal-rule'">
                <div class="flex items-center justify-center gap-3 mb-2" x-show="letterhead.logoBase64">
                    <img :src="letterhead.logoBase64" :style="'height: ' + (letterhead.logoHeight || 36) + 'px'">
                </div>
                <h2 class="text-lg font-bold" x-text="letterhead.companyName"></h2>
                <p class="text-[10px] text-slate-500"
                    x-text="letterhead.docTitle + (letterhead.datePosition === 'top' ? ' · ' + docDate() : '')"></p>
            </div>

            <!-- 3. Layout: centered -->
            <div class="text-center pb-6 mb-8 border-b-2 border-[#3b3c95]" x-show="letterhead.layout === 'centered'">
                <div class="flex items-center justify-center gap-3 mb-2" x-show="letterhead.logoBase64">
                    <img :src="letterhead.logoBase64" :style="'height: ' + (letterhead.logoHeight || 36) + 'px'">
                </div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900"
                    x-text="letterhead.companyName || 'Company Name'"></h2>
                <p class="text-xs text-slate-500 mt-0.5 whitespace-pre-line" x-text="letterhead.address"></p>
                <div class="mt-2 flex items-center justify-center gap-3 text-[10px] font-mono text-slate-500">
                    <span class="font-bold uppercase tracking-wider text-[#3b3c95]"
                        x-text="letterhead.docTitle || 'DOCUMENT'"></span>
                    <span x-show="letterhead.datePosition === 'top'" x-text="'Date: ' + docDate()"></span>
                    <span x-show="letterhead.statementFor" x-text="'For: ' + letterhead.statementFor"></span>
                </div>
            </div>

            <!-- 4. Layout: left-accent-bar -->
            <div class="flex gap-3 pb-6 mb-8 border-b border-slate-200"
                x-show="letterhead.layout === 'left-accent-bar'">
                <div class="w-1 rounded bg-[#3b3c95] shrink-0"></div>
                <div class="flex-1 flex items-start justify-between">
                    <div class="space-y-0.5 flex items-center gap-4">
                        <img x-show="letterhead.logoBase64" :src="letterhead.logoBase64" class="shrink-0"
                            :style="'height: ' + (letterhead.logoHeight || 36) + 'px'">
                        <div>
                            <h2 class="text-lg font-bold tracking-tight text-slate-900"
                                x-text="letterhead.companyName || 'Company Name'"></h2>
                            <p class="text-xs text-slate-500 whitespace-pre-line" x-text="letterhead.address"></p>
                        </div>
                    </div>
                    <div class="text-right font-mono space-y-0.5">
                        <h3 class="text-sm font-bold text-[#3b3c95] uppercase tracking-wider"
                            x-text="letterhead.docTitle || 'DOCUMENT'"></h3>
                        <p class="text-xs text-slate-500" x-show="letterhead.datePosition === 'top'"
                            x-text="'Date: ' + docDate()"></p>
                        <p class="text-xs text-slate-500" x-show="letterhead.statementFor"
                            x-text="'For: ' + letterhead.statementFor"></p>
                    </div>
                </div>
            </div>

            <!-- 5. Layout: monogram-inline -->
            <div class="flex items-center justify-between pb-6 mb-8 border-b-2 border-[#3b3c95]"
                x-show="letterhead.layout === 'monogram-inline'">
                <div class="flex items-center gap-3">
                    <div x-show="!letterhead.logoBase64"
                        class="w-11 h-11 rounded-full bg-[#3b3c95]/10 text-[#3b3c95] flex items-center justify-center font-black text-lg shrink-0"
                        x-text="(letterhead.companyName || 'My').trim().slice(0,2).toUpperCase()"></div>
                    <img x-show="letterhead.logoBase64" :src="letterhead.logoBase64" class="shrink-0"
                        :style="'height: ' + (letterhead.logoHeight || 36) + 'px'">
                    <div class="space-y-0.5">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900"
                            x-text="letterhead.companyName || 'Company Name'"></h2>
                        <p class="text-xs text-slate-500" x-text="letterhead.address"></p>
                    </div>
                </div>
                <div class="text-right font-mono space-y-0.5">
                    <h3 class="text-sm font-bold text-[#3b3c95] uppercase tracking-wider"
                        x-text="letterhead.docTitle || 'DOCUMENT'"></h3>
                    <p class="text-xs text-slate-500" x-show="letterhead.datePosition === 'top'"
                        x-text="'Date: ' + docDate()"></p>
                </div>
            </div>

            <!-- 6. Layout: split-bordered -->
            <div class="border-y-2 border-[#3b3c95] py-4 mb-8 flex justify-between items-start"
                x-show="letterhead.layout === 'split-bordered'">
                <div class="space-y-1 flex items-center gap-4">
                    <img x-show="letterhead.logoBase64" :src="letterhead.logoBase64" class="shrink-0"
                        :style="'height: ' + (letterhead.logoHeight || 36) + 'px'">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-slate-900"
                            x-text="letterhead.companyName || 'Company Name'"></h2>
                        <p class="text-xs text-slate-500 whitespace-pre-line leading-relaxed"
                            x-text="letterhead.address || 'Address information'"></p>
                    </div>
                </div>
                <div class="text-right space-y-1 font-mono shrink-0">
                    <h3 class="text-sm font-bold text-[#3b3c95] tracking-wider uppercase"
                        x-text="letterhead.docTitle || 'DOCUMENT'"></h3>
                    <p class="text-xs text-slate-500" x-show="letterhead.datePosition === 'top'"
                        x-text="'Date: ' + docDate()"></p>
                    <p class="text-xs text-slate-500" x-show="letterhead.statementFor"
                        x-text="'For: ' + letterhead.statementFor"></p>
                </div>
            </div>

            <!-- 7. Layout: editorial-column -->
            <div class="grid grid-cols-3 gap-6 pb-6 mb-8 border-b-2 border-slate-200"
                x-show="letterhead.layout === 'editorial-column'">
                <div class="col-span-2 border-r border-slate-200 pr-6 flex items-start gap-4">
                    <img x-show="letterhead.logoBase64" :src="letterhead.logoBase64" class="shrink-0"
                        :style="'height: ' + (letterhead.logoHeight || 36) + 'px'">
                    <div class="space-y-1">
                        <h2 class="text-2xl font-extrabold tracking-tight text-slate-950 uppercase"
                            x-text="letterhead.companyName || 'Company Name'"></h2>
                        <p class="text-[11px] text-slate-400 italic" x-show="letterhead.tagline"
                            x-text="letterhead.tagline"></p>
                        <p class="text-xs text-slate-500 pt-1 whitespace-pre-line leading-normal"
                            x-text="letterhead.address"></p>
                    </div>
                </div>
                <div class="pl-2 space-y-2 text-right self-end font-mono shrink-0">
                    <h3 class="text-sm font-black text-[#3b3c95] tracking-widest uppercase"
                        x-text="letterhead.docTitle || 'DOCUMENT'"></h3>
                    <div class="text-[10px] text-slate-500 space-y-0.5">
                        <p x-show="letterhead.datePosition === 'top'" x-text="'Date: ' + docDate()"></p>
                        <p x-show="letterhead.statementFor" x-text="'For: ' + letterhead.statementFor"></p>
                    </div>
                </div>
            </div>

            <!-- Modern Minimalist -->
            <div class="flex items-start justify-between pb-6 mb-8 border-b border-slate-100"
                x-show="letterhead.layout === 'modern-minimalist'">
                <div class="flex items-center gap-6">
                    <img x-show="letterhead.logoBase64" :src="letterhead.logoBase64" class="shrink-0"
                        :style="'height: ' + (letterhead.logoHeight || 36) + 'px'">
                    <div class="h-8 w-px bg-slate-200"></div>
                    <div>
                        <h2 class="text-lg font-black uppercase tracking-widest text-slate-900"
                            x-text="letterhead.companyName || 'Company Name'"></h2>
                        <p class="text-[10px] text-slate-400 font-mono tracking-wider" x-text="letterhead.tagline"></p>
                    </div>
                </div>
                <div class="text-right text-xs text-slate-500 font-mono leading-tight shrink-0">
                    <h3 class="font-bold text-[#3b3c95] uppercase text-xs" x-text="letterhead.docTitle || 'DOCUMENT'">
                    </h3>
                    <p class="mt-1" x-show="letterhead.datePosition === 'top'" x-text="'Date: ' + docDate()"></p>
                    <p x-show="letterhead.statementFor" x-text="'For: ' + letterhead.statementFor"></p>
                </div>
            </div>

            <!-- Corporate Block -->
            <div class="bg-slate-50 rounded-xl p-5 mb-8 border border-slate-200/80"
                x-show="letterhead.layout === 'corporate-block'">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-4">
                        <img x-show="letterhead.logoBase64" :src="letterhead.logoBase64" class="shrink-0"
                            :style="'height: ' + (letterhead.logoHeight || 36) + 'px'">
                        <div>
                            <h2 class="text-xl font-extrabold text-slate-900"
                                x-text="letterhead.companyName || 'Company Name'"></h2>
                            <p class="text-xs text-slate-500" x-text="letterhead.address"></p>
                        </div>
                    </div>
                    <div class="text-right font-mono shrink-0">
                        <h3 class="text-xs font-black uppercase tracking-wider text-[#3b3c95]"
                            x-text="letterhead.docTitle || 'DOCUMENT'"></h3>
                        <p class="text-[10px] text-slate-400 mt-1" x-show="letterhead.datePosition === 'top'"
                            x-text="'Date: ' + docDate()"></p>
                    </div>
                </div>
            </div>

            <!-- Asymmetric Compact -->
            <div class="flex items-start justify-between pb-6 mb-8 border-b-2 border-dashed border-slate-200"
                x-show="letterhead.layout === 'asymmetric-compact'">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <div x-show="!letterhead.logoBase64"
                            class="w-8 h-8 rounded-lg bg-[#3b3c95]/10 text-[#3b3c95] flex items-center justify-center font-black text-sm"
                            x-text="(letterhead.companyName || 'My').trim().slice(0,2).toUpperCase()"></div>
                        <img x-show="letterhead.logoBase64" :src="letterhead.logoBase64" class="shrink-0"
                            :style="'height: ' + (letterhead.logoHeight || 36) + 'px'">
                        <h2 class="text-lg font-bold text-slate-900" x-text="letterhead.companyName || 'Company Name'">
                        </h2>
                    </div>
                    <p class="text-xs text-slate-500 max-w-md pt-1" x-text="letterhead.address"></p>
                </div>
                <div class="text-right shrink-0">
                    <h3 class="text-sm font-extrabold uppercase text-[#3b3c95] tracking-wider"
                        x-text="letterhead.docTitle || 'DOCUMENT'"></h3>
                    <div class="text-xs text-slate-500 font-mono mt-1 space-y-0.5">
                        <p x-show="letterhead.datePosition === 'top'" x-text="'Date: ' + docDate()"></p>
                        <p x-show="letterhead.statementFor" x-text="'For: ' + letterhead.statementFor"></p>
                    </div>
                </div>
            </div>

            <!-- Compact Grid -->
            <div class="grid grid-cols-3 gap-6 pb-6 mb-8 border-b-2 border-slate-200 text-xs text-slate-700"
                x-show="letterhead.layout === 'compact-grid'">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <img x-show="letterhead.logoBase64" :src="letterhead.logoBase64" class="shrink-0"
                            :style="'height: ' + (letterhead.logoHeight || 36) + 'px'">
                        <h2 class="font-bold text-slate-900" x-text="letterhead.companyName || 'Company Name'"></h2>
                    </div>
                    <p class="text-[10px] text-slate-500 whitespace-pre-line leading-tight" x-text="letterhead.address">
                    </p>
                </div>
                <div class="border-l border-slate-200 pl-6 space-y-1 font-mono">
                    <h3 class="font-bold uppercase text-[#3b3c95]" x-text="letterhead.docTitle || 'DOCUMENT'"></h3>
                    <p class="text-[10px] text-slate-500" x-show="letterhead.datePosition === 'top'"
                        x-text="'Date: ' + docDate()"></p>
                </div>
                <div class="border-l border-slate-200 pl-6 space-y-1">
                    <p class="font-semibold text-slate-700" x-show="letterhead.statementFor"
                        x-text="'For: ' + letterhead.statementFor"></p>
                    <p class="text-[10px] text-slate-500" x-show="letterhead.bin" x-text="'BIN: ' + letterhead.bin"></p>
                </div>
            </div>
        </div>

        <!-- Spreadsheet Records Table -->
        <table class="doc-table"
            :style="{ 'font-size': computedTableFontSize + 'px', '--th-bg': settings.thBg || undefined, '--th-text': settings.thText || undefined }">
            <thead>
                <tr>
                    <template x-for="col in headers.filter(h => h.visible)" :key="col.key">
                        <th :class="col.align === 'right' ? 'align-right' : ''" x-text="col.label"></th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, idx) in rows" :key="idx">
                    <tr :class="settings.boldLastRow && idx === rows.length - 1 ? 'pt-bold-row' : ''">
                        <template x-for="col in headers.filter(h => h.visible)" :key="col.key">
                            <td :class="col.align === 'right' ? 'align-right' : ''"
                                x-text="formatCell(row[col.key], col.type)"></td>
                        </template>
                    </tr>
                </template>
            </tbody>
            <tfoot x-show="settings.showTotals && hasNumericColumns()">
                <tr>
                    <template x-for="(col, index) in headers.filter(h => h.visible)" :key="col.key">
                        <td :class="col.align === 'right' ? 'align-right' : ''"
                            x-text="index === 0 ? 'Total Sum' : (['integer', 'decimal', 'currency'].includes(col.type) ? calculateSum(col.key, col.type) : '')">
                        </td>
                    </template>
                </tr>
            </tfoot>
        </table>

        <!-- Bottom Date Indicator Block -->
        <div class="mt-8 text-right text-xs font-mono text-slate-500"
            x-show="showLetterhead && letterhead.datePosition === 'bottom'" x-text="'Document Date: ' + docDate()">
        </div>

        <!-- Dynamic Footnote Footer (Max 1 Line) -->
        <div x-show="settings.showFooter"
            class="mt-6 border-t border-slate-100 pt-2 text-[10px] text-slate-400 font-mono">
            <!-- Simple Text Layout -->
            <div class="text-center" x-show="settings.footerLayout === 'simple-text'" x-text="settings.footerText">
            </div>

            <!-- Brand Accent Line Layout -->
            <div class="space-y-1" x-show="settings.footerLayout === 'brand-accent'">
                <div class="h-0.5 w-full bg-[--accent] opacity-30"></div>
                <div class="text-center"
                    x-text="(letterhead.companyName || 'Paper Trail') + ' · ' + settings.footerText"></div>
            </div>

            <!-- Split Footnote Layout -->
            <div class="flex justify-between items-center" x-show="settings.footerLayout === 'split-footnote'">
                <span class="font-bold" x-text="letterhead.companyName || 'Paper Trail'"></span>
                <span x-text="settings.footerText"></span>
            </div>
        </div>
    </div>

    <script>
        function printController() {
            return {
                headers: [],
                rows: [],
                letterhead: {
                    companyName: '',
                    docTitle: 'DOCUMENT',
                    statementFor: '',
                    address: '',
                    layout: 'split-header',
                    logoHeight: 36,
                    datePosition: 'top',
                    date: ''
                },
                showLetterhead: true,
                tableStyle: 'boxed',
                orientation: 'portrait',
                computedTableFontSize: 14,
                dateStr: '',
                settings: {
                    numberFormat: 'western',
                    dateFormat: 'dd/mm/yyyy',
                    currencySymbol: '$',
                    tableFontSize: 14,
                    showTotals: false,
                    boldLastRow: false,
                    showFooter: false,
                    footerLayout: 'simple-text',
                    footerText: 'Thank you for your business.',
                    thBg: '',
                    thText: ''
                },

                initPrint() {
                    const dataStr = sessionStorage.getItem('pt_print_data');
                    if (!dataStr) {
                        window.location.href = "{{ route('app') }}";
                        return;
                    }

                    try {
                        const data = JSON.parse(dataStr);
                        this.headers = data.headers || [];
                        this.rows = data.rows || [];
                        this.letterhead = data.letterhead || this.letterhead;
                        this.showLetterhead = data.showLetterhead !== false;
                        this.tableStyle = data.tableStyle || 'boxed';
                        this.dateStr = data.dateStr || new Date().toLocaleDateString('en-GB');
                        this.settings = data.settings || this.settings;
                        this.orientation = data.orientation || 'portrait';

                        // Set Theme custom properties dynamically
                        const theme = data.theme || 'indigo';
                        document.documentElement.setAttribute('data-theme', theme);

                        // Set layout orientation styling
                        this.toggleOrientation(this.orientation);

                        // Override color accent styles if user has custom accents selected
                        if (data.customAccent) {
                            document.documentElement.style.setProperty('--accent', data.customAccent);
                            document.documentElement.style.setProperty('--accent-subtle', data.customAccent + '18');
                        }

                        // Set dynamic document title for browser save file default naming
                        document.title = this.getDynamicTitle();

                        // Run the Auto-Fit table engine to scale fonts/paddings if columns exceed viewport bounds
                        this.fitTableToPage();

                        // Trigger native printing operations
                        setTimeout(() => {
                            window.print();
                        }, 800);

                    } catch (err) {
                        console.error('Error loading print dataset cache:', err);
                    }
                },

                toggleOrientation(value) {
                    this.orientation = value;
                    document.documentElement.setAttribute('data-orientation', value);
                    this.fitTableToPage();
                },

                fitTableToPage() {
                    this.$nextTick(() => {
                        const container = document.getElementById('print-area');
                        if (!container) return;
                        const table = container.querySelector('.doc-table');
                        if (!table) return;

                        let baseSize = parseFloat(this.settings.tableFontSize) || 14;
                        this.computedTableFontSize = baseSize;
                        table.style.fontSize = baseSize + 'px';

                        this.$nextTick(() => {
                            let containerWidth = container.clientWidth - 32;
                            let currentSize = baseSize;
                            let safetyCounter = 0;
                            const minAllowedSize = 8;

                            while (table.scrollWidth > containerWidth && currentSize > minAllowedSize && safetyCounter < 15) {
                                currentSize -= 0.5;
                                this.computedTableFontSize = currentSize;
                                table.style.fontSize = currentSize + 'px';
                                safetyCounter++;
                            }
                        });
                    });
                },

                docDate() {
                    return this.letterhead.date || this.dateStr;
                },

                getDynamicTitle() {
                    const company = (this.letterhead.companyName || 'Paper Trail').trim();
                    const acronym = company
                        .split(/\s+/)
                        .map(word => word.charAt(0))
                        .join('')
                        .toUpperCase();

                    const docTitle = (this.letterhead.docTitle || 'Document').trim();

                    let d = new Date(this.letterhead.date);
                    if (isNaN(d.getTime())) {
                        if (this.dateStr) {
                            const parts = this.dateStr.split('/');
                            if (parts.length === 3) {
                                const day = parseInt(parts[0], 10);
                                const month = parseInt(parts[1], 10) - 1;
                                const year = parseInt(parts[2], 10);
                                d = new Date(year, month, day);
                            }
                        }
                    }
                    if (isNaN(d.getTime())) {
                        d = new Date();
                    }

                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const finalDate = `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;

                    return `${acronym}_${docTitle}_${finalDate}`;
                },

                hasNumericColumns() {
                    return this.headers
                        .filter(h => h.visible)
                        .some(h => ['integer', 'decimal', 'currency'].includes(h.type));
                },

                // Value aggregation sum calculator (matches spreadsheet workspace)
                calculateSum(key, type) {
                    let total = 0;
                    this.rows.forEach(row => {
                        let cellVal = String(row[key] || '').replace(/[^\d.-]/g, '');
                        let val = parseFloat(cellVal);
                        if (!isNaN(val)) {
                            total += val;
                        }
                    });
                    return this.formatCell(total, type);
                },

                // Value layout formatter (matches spreadsheet workspace)
                formatCell(val, type) {
                    if (val === undefined || val === null || val === '') return '';
                    let num = parseFloat(String(val).replace(/[^\d.-]/g, ''));
                    if (isNaN(num)) return val;

                    if (type === 'integer') {
                        return this.formatNumberGroup(Math.round(num), 0);
                    }
                    if (type === 'decimal') {
                        return this.formatNumberGroup(num, 2);
                    }
                    if (type === 'currency') {
                        const formatted = this.formatNumberGroup(num, 2);
                        return (this.settings.currencySymbol || '$') + formatted;
                    }
                    return val;
                },

                formatNumberGroup(num, decimals) {
                    let parts = num.toFixed(decimals).split('.');
                    let integerPart = parts[0];
                    let decimalPart = parts[1] ? '.' + parts[1] : '';

                    if (this.settings.numberFormat === 'bd') {
                        let lastThree = integerPart.substring(integerPart.length - 3);
                        let otherParts = integerPart.substring(0, integerPart.length - 3);
                        if (otherParts !== '') {
                            lastThree = ',' + lastThree;
                        }
                        let res = otherParts.replace(/\B(?=(\d{2})+(?!\d))/g, ",") + lastThree + decimalPart;
                        return res;
                    } else {
                        return integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ",") + decimalPart;
                    }
                }
            }
        }
    </script>
</body>

</html>