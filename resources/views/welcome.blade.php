<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PaperTrail — Spreadsheet to Clean PDF</title>

    <!-- Bunny Fonts: Instrument Sans -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Tailwind CSS (via Vite/Build) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['"Instrument Sans"', 'sans-serif'],
                        }
                    }
                }
            }
        </script>
    @endif

    <!-- CDN Libraries (Single Page App Context) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Anti-FOUT / Initial States -->
    <script>
            (function () {
                const theme = localStorage.getItem('pt-theme') || 'indigo';
                document.documentElement.setAttribute('data-theme', theme);
            })();
    </script>

    <style>
        /* Core Semantic Light Variables */
        :root {
            --bg: #faf9f6;
            /* Soft warm off-white */
            --surface: #ffffff;
            --border: #e6e5e0;
            --ink: #111115;
            --ink-muted: #6e6d7a;
            --accent: #3b3c95;
            /* Refined midnight indigo */
            --accent-subtle: #f2f2fa;
        }

        body {
            background-color: var(--bg);
            color: var(--ink);
            font-family: 'Instrument Sans', sans-serif;
        }

        /* simulated paper container */
        .paper-sheet {
            background: white;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
            border: 1px solid var(--border);
        }

        /* Clean document table styling */
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .doc-table th {
            font-weight: 600;
            padding: 8px 12px;
            border-bottom: 2px solid var(--border);
            color: var(--ink);
            text-align: left;
        }

        .doc-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f0efeb;
            color: #33333b;
        }

        .doc-table tr:last-child td {
            border-bottom: none;
        }

        /* Print Media Query */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
                color: black;
            }

            .paper-sheet {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            @page {
                size: A4 portrait;
                margin: 15mm;
            }

            thead {
                display: table-header-group;
            }

            tr {
                break-inside: avoid;
            }
        }
    </style>
</head>

<body class="antialiased min-h-screen flex flex-col" x-data="appState()">

    <!-- Header / Branding Navigation (no-print) -->
    <header
        class="no-print w-full max-w-6xl mx-auto px-6 py-6 flex items-center justify-between border-b border-[#e6e5e0]/60">
        <div class="flex items-center gap-2.5">
            <div
                class="w-8 h-8 rounded-lg bg-[--accent] flex items-center justify-center text-white font-bold text-lg select-none">
                P</div>
            <span class="font-bold tracking-tight text-lg text-[--ink]">PaperTrail</span>
        </div>
        <div class="flex items-center gap-6">
            <span class="text-xs text-[--ink-muted] font-medium hidden sm:inline">Files never leave your browser</span>
            <a href="https://github.com" target="_blank"
                class="text-xs text-[--ink-muted] hover:text-[--ink] transition-colors">Documentation</a>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 w-full max-w-6xl mx-auto px-6 py-12 flex flex-col justify-center">

        <!-- 1. Landing View / Drop Zone Section -->
        <template x-if="!isLoaded">
            <div class="max-w-2xl mx-auto w-full text-center space-y-12 py-8">
                <!-- Hero Message -->
                <div class="space-y-4">
                    <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-[--ink] leading-[1.15]">
                        Turn spreadsheets into <br>
                        <span
                            class="text-[#3b3c95] underline decoration-[#e6e5e0] decoration-wavy underline-offset-8">beautiful
                            PDF reports</span>
                    </h1>
                    <p class="text-[--ink-muted] text-base max-w-md mx-auto leading-relaxed">
                        Drop in Excel or CSV files, choose a clean layout, slap on your company logo, and print. Totally
                        private, client-side formatting.
                    </p>
                </div>

                <!-- Minimal Interactive Upload Box -->
                <div @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                    @drop.prevent="isDragging = false; handleDrop($event)"
                    :class="isDragging ? 'border-[#3b3c95] bg-[#3b3c95]/5 scale-[1.01]' : 'border-dashed border-[--border] hover:border-[#3b3c95]/40 bg-white'"
                    class="border-2 rounded-2xl p-12 text-center transition-all duration-200 cursor-pointer shadow-sm relative group"
                    @click="$refs.fileInput.click()">
                    <input type="file" accept=".xlsx,.xls,.csv,.tsv" class="hidden" x-ref="fileInput"
                        @change="handleFile($event)">

                    <div class="space-y-4">
                        <!-- Icon -->
                        <div
                            class="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center mx-auto group-hover:scale-105 transition-transform duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                stroke="currentColor" class="w-6 h-6 text-[#3b3c95]">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                            </svg>
                        </div>
                        <!-- Texts -->
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-[--ink]">Drag & drop your file here</p>
                            <p class="text-xs text-[--ink-muted]">Supports XLSX, XLS, CSV, and TSV</p>
                        </div>
                        <span
                            class="inline-flex items-center gap-1 text-xs font-semibold text-[#3b3c95] group-hover:underline">
                            or browse files
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-3 h-3">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </span>
                    </div>
                </div>

                <!-- Safe badge -->
                <div
                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-800 text-xs font-semibold">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                        class="w-4 h-4 text-emerald-600">
                        <path fill-rule="evenodd"
                            d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z"
                            clip-rule="evenodd" />
                    </svg>
                    Data privacy secured: Processing occurs completely locally
                </div>
            </div>
        </template>

        <!-- 2. Workspace View Section -->
        <template x-if="isLoaded">
            <div class="w-full flex flex-col gap-6" x-data="workspaceSetup()" x-init="initSortable()">

                <!-- Workspace Subheader controls (no-print) -->
                <div
                    class="no-print flex items-center justify-between bg-white border border-[--border] rounded-xl px-5 py-3 shadow-sm">
                    <div class="flex items-center gap-4">
                        <button @click="resetLoader()"
                            class="text-xs font-semibold text-[--ink-muted] hover:text-[--ink] flex items-center gap-1.5 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                stroke="currentColor" class="w-3.5 h-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                            Import New File
                        </button>
                        <div class="w-px h-4 bg-slate-200"></div>
                        <div class="text-xs text-[--ink-muted] font-medium" x-text="activeSheet"></div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Orientation Toggle -->
                        <select @change="toggleOrientation($event.target.value)"
                            class="text-xs border border-[--border] rounded-lg px-3 py-1.5 bg-white text-[--ink] outline-none">
                            <option value="portrait">Portrait (A4)</option>
                            <option value="landscape">Landscape (A4)</option>
                        </select>

                        <!-- Print Action -->
                        <button @click="triggerPrint()"
                            class="bg-[#3b3c95] text-white px-4 py-1.5 rounded-lg text-xs font-semibold hover:opacity-90 transition-opacity">
                            🖨️ Print to PDF
                        </button>
                    </div>
                </div>

                <!-- Config & Table side-by-side -->
                <div class="flex flex-col lg:flex-row gap-6 items-start">

                    <!-- Config Left Panel (no-print) -->
                    <div
                        class="no-print w-full lg:w-72 shrink-0 bg-white border border-[--border] rounded-xl p-5 space-y-6 shadow-sm">

                        <!-- Column Manager -->
                        <div class="space-y-3">
                            <h3 class="font-bold text-xs uppercase tracking-wider text-[--ink-muted]">Columns</h3>
                            <ul x-ref="columnsList" class="space-y-1.5">
                                <template x-for="(col, index) in headers" :key="col.key">
                                    <li
                                        class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-100 text-xs">
                                        <span class="drag-handle cursor-grab text-slate-400 font-bold">⠿</span>
                                        <input type="checkbox" :checked="col.visible"
                                            @change="col.visible = !col.visible"
                                            class="rounded border-slate-300 text-[#3b3c95] focus:ring-[#3b3c95]">
                                        <input type="text" x-model="col.label"
                                            class="bg-transparent border-none outline-none font-medium flex-1 text-[--ink]">
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Letterhead configurations -->
                        <div class="space-y-3 pt-4 border-t border-slate-100">
                            <h3 class="font-bold text-xs uppercase tracking-wider text-[--ink-muted]">Company Info</h3>

                            <div class="space-y-2.5">
                                <input type="text" x-model="letterhead.companyName" placeholder="Company Name"
                                    class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 outline-none focus:border-[#3b3c95]">
                                <input type="text" x-model="letterhead.docTitle"
                                    placeholder="Document Title (e.g. Invoice)"
                                    class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 outline-none focus:border-[#3b3c95]">
                                <input type="text" x-model="letterhead.statementFor"
                                    placeholder="Prepared For (optional)"
                                    class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 outline-none focus:border-[#3b3c95]">
                                <textarea x-model="letterhead.address" placeholder="Address / Contact details" rows="2"
                                    class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 outline-none focus:border-[#3b3c95]"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- PDF / Paper Sheet Preview -->
                    <div class="flex-1 w-full overflow-auto">
                        <div id="print-area" class="paper-sheet mx-auto p-12 min-h-[297mm] transition-all duration-200">

                            <!-- Letterhead Header -->
                            <div class="border-b-2 border-[#3b3c95] pb-6 mb-8 flex justify-between items-start">
                                <div class="space-y-1">
                                    <h2 class="text-xl font-bold tracking-tight text-slate-900"
                                        x-text="letterhead.companyName || 'Company Name'"></h2>
                                    <p class="text-xs text-slate-500 whitespace-pre-line leading-relaxed"
                                        x-text="letterhead.address || 'Address information'"></p>
                                </div>
                                <div class="text-right space-y-1 font-mono">
                                    <h3 class="text-sm font-bold text-[#3b3c95] tracking-wider uppercase"
                                        x-text="letterhead.docTitle || 'DOCUMENT'"></h3>
                                    <p class="text-xs text-slate-500" x-text="'Date: ' + getTodayDate()"></p>
                                    <p class="text-xs text-slate-500" x-show="letterhead.statementFor"
                                        x-text="'For: ' + letterhead.statementFor"></p>
                                </div>
                            </div>

                            <!-- Spreadsheet table element -->
                            <table class="doc-table">
                                <thead>
                                    <tr>
                                        <template x-for="col in headers.filter(h => h.visible)" :key="col.key">
                                            <th x-text="col.label"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, rIdx) in rows" :key="rIdx">
                                        <tr>
                                            <template x-for="col in headers.filter(h => h.visible)" :key="col.key">
                                                <td x-text="row[col.key]"></td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </main>

    <!-- Footer -->
    <footer class="no-print py-8 border-t border-[#e6e5e0]/60 text-center text-xs text-[--ink-muted]">
        <p>Built by Tuhin · &copy; 2026 · Files are secure and parsed entirely locally</p>
    </footer>

    <!-- Alpine App Logic -->
    <script>
        function appState() {
            return {
                isDragging: false,
                isLoaded: false,
                rawWorkbook: null,
                sheetNames: [],
                activeSheet: '',
                headers: [],
                rows: [],
                letterhead: {
                    companyName: '',
                    docTitle: 'STATEMENT',
                    statementFor: '',
                    address: ''
                },

                handleDrop(event) {
                    const file = event.dataTransfer.files[0];
                    if (file) this.loadFile(file);
                },

                handleFile(event) {
                    const file = event.target.files[0];
                    if (file) this.loadFile(file);
                },

                loadFile(file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const data = new Uint8Array(e.target.result);
                        this.rawWorkbook = XLSX.read(data, { type: 'array' });
                        this.sheetNames = this.rawWorkbook.SheetNames;
                        this.selectSheet(this.sheetNames[0]);
                    };
                    reader.readAsArrayBuffer(file);
                },

                selectSheet(name) {
                    this.activeSheet = name;
                    const worksheet = this.rawWorkbook.Sheets[name];
                    const rawJson = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });

                    if (rawJson.length > 0) {
                        const headerRow = rawJson[0];
                        this.headers = headerRow.map((h, i) => ({
                            key: `col_${i}`,
                            label: String(h).trim() || `Column ${i + 1}`,
                            visible: true
                        }));

                        const bodyRows = rawJson.slice(1).filter(r => r.some(c => c !== ''));
                        this.rows = bodyRows.map(r => {
                            const obj = {};
                            this.headers.forEach((h, i) => {
                                obj[h.key] = r[i] ?? '';
                            });
                            return obj;
                        });

                        this.isLoaded = true;
                    }
                },

                resetLoader() {
                    this.isLoaded = false;
                    this.rawWorkbook = null;
                    this.headers = [];
                    this.rows = [];
                },

                getTodayDate() {
                    return new Date().toLocaleDateString('en-GB');
                }
            }
        }

        function workspaceSetup() {
            return {
                initSortable() {
                    this.$nextTick(() => {
                        Sortable.create(this.$refs.columnsList, {
                            handle: '.drag-handle',
                            animation: 180,
                            onEnd: (evt) => {
                                const headers = this.headers;
                                const [moved] = headers.splice(evt.oldIndex, 1);
                                headers.splice(evt.newIndex, 0, moved);
                            }
                        });
                    });
                },

                toggleOrientation(value) {
                    let styleTag = document.getElementById('print-orientation-style');
                    if (!styleTag) {
                        styleTag = document.createElement('style');
                        styleTag.id = 'print-orientation-style';
                        document.head.appendChild(styleTag);
                    }
                    if (value === 'landscape') {
                        styleTag.textContent = '@media print { @page { size: A4 landscape; } }';
                    } else {
                        styleTag.textContent = '@media print { @page { size: A4 portrait; } }';
                    }
                },

                triggerPrint() {
                    window.print();
                }
            }
        }
    </script>
</body>

</html>