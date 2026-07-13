@extends('layouts.app')

@section('title', 'PaperTrail — Format Report')

@push('head')
    <style>
        /* Sidebar styling */
        .workspace-sidebar {
            width: 320px;
            background: white;
            border-right: 1px solid var(--border);
            height: calc(100vh - var(--toolbar-height));
        }
        /* Document paper sheet simulated page boundaries on screen */
        @media screen {
            .document-preview-pane {
                background: var(--bg);
                height: calc(100vh - var(--toolbar-height));
                overflow-y: auto;
            }
            .a4-page-container {
                max-width: 210mm;
                min-height: 297mm;
                background: white;
                box-shadow: var(--shadow-md);
                margin: 2rem auto;
                padding: 15mm;
                border: 1px solid var(--border);
                border-radius: var(--radius-xs);
            }
            :root:has(#pt-orientation-style) .a4-page-container {
                max-width: 297mm;
                min-height: 210mm;
            }
        }
        @media print {
            .a4-page-container {
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
                min-height: auto !important;
                max-width: none !important;
            }
        }
    </style>
@endpush

@section('content')
<div class="min-h-screen flex flex-col font-sans bg-[#faf9f6]" x-data="appWorkspace()" x-init="initApp()">

    {{-- ══════════ PREMIUM MINIMALIST NAVIGATION (no-print) ══════════ --}}
    <header class="no-print h-[--toolbar-height] shrink-0 bg-white border-b border-[--border] flex items-center justify-between px-6 z-10 shadow-sm">
        <div class="flex items-center gap-4">
            <a href="{{ route('home') }}" class="group flex items-center">
                <span class="font-mono text-xs uppercase tracking-[0.25em] font-semibold text-[#141428] group-hover:text-[#3b3c95] transition-colors duration-200">
                    PAPER<span class="text-[#3b3c95] font-black group-hover:text-[#141428] transition-colors duration-200">/</span>TRAIL
                </span>
            </a>
            <div class="w-px h-4 bg-slate-200" x-show="$store.spreadsheet.isLoaded"></div>
            <span class="text-xs text-[--ink-muted] font-medium hidden sm:inline" x-show="$store.spreadsheet.isLoaded" x-text="'File: ' + fileName"></span>
        </div>

        <div class="flex items-center gap-3" x-show="$store.spreadsheet.isLoaded">
            <!-- Orientation Toggle -->
            <select @change="$store.theme.setOrientation($event.target.value)" :value="$store.theme.orientation" class="text-xs border border-[--border] rounded-lg px-3 py-1.5 bg-white text-[--ink] outline-none">
                <option value="portrait">A4 Portrait</option>
                <option value="landscape">A4 Landscape</option>
            </select>

            <!-- Table Styles -->
            <select @change="$store.theme.setTableStyle($event.target.value)" :value="$store.theme.tableStyle" class="text-xs border border-[--border] rounded-lg px-3 py-1.5 bg-white text-[--ink] outline-none">
                <option value="clean">Clean Style</option>
                <option value="ruled">Ruled Style</option>
                <option value="boxed">Boxed Style</option>
                <option value="striped">Striped Style</option>
                <option value="shaded-header">Shaded Header</option>
            </select>

            <!-- Export to PDF -->
            <button @click="triggerPrint()" class="bg-[#3b3c95] text-white px-4 py-1.5 rounded-lg text-xs font-semibold hover:opacity-90 active:scale-[0.98] transition-all shadow-sm">
                🖨️ Print / Save PDF
            </button>
        </div>
    </header>

    <!-- Main Workspace body -->
    <div class="flex-1 flex overflow-hidden">

        <!-- A. Left Sidebar Controls (no-print) -->
        <aside class="no-print workspace-sidebar shrink-0 overflow-y-auto p-5 space-y-6" x-show="$store.spreadsheet.isLoaded">
            
            <!-- Back to Import -->
            <button @click="clearActiveFile()" class="text-xs font-semibold text-[--ink-muted] hover:text-[--ink] flex items-center gap-1.5 transition-colors pb-2 border-b border-slate-100 w-full text-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                Import different file
            </button>

            <!-- Sheet selector (multi-sheet workbooks) -->
            <div class="space-y-2" x-show="$store.spreadsheet.sheetNames.length > 1">
                <label class="font-bold text-xs uppercase tracking-wider text-[--ink-muted]">Select Sheet</label>
                <select @change="$store.spreadsheet.selectSheet($event.target.value)" :value="$store.spreadsheet.activeSheet" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 bg-white text-[--ink] outline-none">
                    <template x-for="name in $store.spreadsheet.sheetNames" :key="name">
                        <option :value="name" x-text="name"></option>
                    </template>
                </select>
            </div>

            <!-- Header config parameters -->
            <div class="space-y-3">
                <h3 class="font-bold text-xs uppercase tracking-wider text-[--ink-muted]">Header Details</h3>
                <div class="space-y-2.5">
                    <input type="text" x-model="$store.letterhead.active.companyName" @input="$store.letterhead.save()" placeholder="Company Name" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 outline-none focus:border-[#3b3c95]">
                    <input type="text" x-model="$store.letterhead.active.docTitle" @input="$store.letterhead.save()" placeholder="Document Title (e.g. Statement)" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 outline-none focus:border-[#3b3c95]">
                    <input type="text" x-model="$store.letterhead.active.statementFor" @input="$store.letterhead.save()" placeholder="Prepared For (optional)" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 outline-none focus:border-[#3b3c95]">
                    <textarea x-model="$store.letterhead.active.address" @input="$store.letterhead.save()" placeholder="Address / Contact Details" rows="2" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 outline-none focus:border-[#3b3c95]"></textarea>
                </div>
            </div>

            <!-- Letterhead options -->
            <div class="space-y-3 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-[--ink-muted]">Letterhead Options</h3>
                    <input type="checkbox" :checked="$store.letterhead.showOnPrint" @change="$store.letterhead.showOnPrint = $event.target.checked; $store.letterhead.save()" class="rounded border-slate-300 text-[#3b3c95]">
                </div>
                <div class="space-y-2" x-show="$store.letterhead.showOnPrint">
                    <label class="block text-[10px] text-[--ink-muted] uppercase">Layout Type</label>
                    <select x-model="$store.letterhead.active.layout" @change="$store.letterhead.save()" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 bg-white text-[--ink]">
                        <option value="split-header">Split Header</option>
                        <option value="centered">Centered Header</option>
                        <option value="left-accent-bar">Left Accent Bar</option>
                        <option value="monogram-inline">Monogram Title</option>
                        <option value="minimal-rule">Minimal Rule</option>
                    </select>

                    <label class="block text-[10px] text-[--ink-muted] uppercase mt-2">Display Density</label>
                    <select x-model="$store.letterhead.active.size" @change="$store.letterhead.save()" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 bg-white text-[--ink]">
                        <option value="compact">Compact (Small)</option>
                        <option value="standard">Standard (Medium)</option>
                        <option value="full">Full (Detailed)</option>
                    </select>
                </div>
            </div>

            <!-- Column visibility & names manager -->
            <div class="space-y-3 pt-4 border-t border-slate-100">
                <h3 class="font-bold text-xs uppercase tracking-wider text-[--ink-muted]">Manage Columns</h3>
                <ul x-ref="columnsList" class="space-y-1.5">
                    <template x-for="col in $store.spreadsheet.headers" :key="col.key">
                        <li class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-100 text-xs">
                            <span class="drag-handle cursor-grab text-slate-400 select-none font-bold">⠿</span>
                            <input type="checkbox" :checked="col.visible" @change="col.visible = !col.visible" class="rounded border-slate-300 text-[#3b3c95]">
                            <input type="text" x-model="col.label" class="bg-transparent border-none outline-none font-medium flex-1 text-[--ink]">
                        </li>
                    </template>
                </ul>
            </div>
        </aside>

        <!-- B. Right Main Preview / Ingestion Area -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- I. No file loaded: Upload box fallback -->
            <div class="flex-1 flex flex-col justify-center items-center p-8 no-print bg-[#faf9f6] bg-grid" x-show="!$store.spreadsheet.isLoaded">
                <div class="max-w-md w-full text-center space-y-6">
                    <div class="space-y-2">
                        <h2 class="text-xl font-bold text-[--ink]">Import spreadsheet to start</h2>
                        <p class="text-xs text-[--ink-muted]">Drop in your data files or browse local drives. Processing takes place locally.</p>
                    </div>

                    <div
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="isDragging = false; handleDrop($event)"
                        :class="isDragging ? 'border-[#3b3c95] bg-[#3b3c95]/5' : 'border-[#dde1ee] hover:border-[#3b3c95]/40 bg-white'"
                        class="border border-dashed rounded-xl p-10 text-center transition-all duration-200 cursor-pointer shadow-sm relative group"
                        @click="$refs.toolInput.click()"
                    >
                        <input type="file" accept=".xlsx,.xls,.csv,.tsv" class="hidden" x-ref="toolInput" @change="handleFile($event)">
                        
                        <div class="space-y-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center mx-auto group-hover:scale-105 transition-transform duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5 text-[#3b3c95]">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                </svg>
                            </div>
                            <div class="space-y-0.5">
                                <p class="text-xs font-semibold text-[#141428]">Drag & drop file here</p>
                                <p class="text-[10px] text-[#8a8ab0]">XLSX, XLS, CSV, or TSV</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- II. File loaded: A4 print layout simulated wrapper -->
            <div class="document-preview-pane flex-1 w-full" x-show="$store.spreadsheet.isLoaded" :data-table-style="$store.theme.tableStyle">
                <div id="print-area" class="a4-page-container transition-all">
                    
                    <!-- Dynamic Letterhead Mount Header Block -->
                    <div x-show="$store.letterhead.showOnPrint" class="mb-6">
                        <div class="border-b-2 border-[#3b3c95] pb-6 mb-8 flex justify-between items-start" x-show="$store.letterhead.active.layout === 'split-header'">
                            <div class="space-y-1">
                                <h2 class="text-xl font-bold tracking-tight text-slate-900" x-text="$store.letterhead.active.companyName || 'Company Name'"></h2>
                                <p class="text-xs text-slate-500 whitespace-pre-line leading-relaxed" x-text="$store.letterhead.active.address || 'Address information'"></p>
                            </div>
                            <div class="text-right space-y-1 font-mono">
                                <h3 class="text-sm font-bold text-[#3b3c95] tracking-wider uppercase" x-text="$store.letterhead.active.docTitle || 'DOCUMENT'"></h3>
                                <p class="text-xs text-slate-500" x-text="'Date: ' + getTodayDate()"></p>
                                <p class="text-xs text-slate-500" x-show="$store.letterhead.active.statementFor" x-text="'For: ' + $store.letterhead.active.statementFor"></p>
                            </div>
                        </div>

                        <!-- Fallback rule/header rendering for other formats -->
                        <div class="text-center pb-4 mb-6 border-b border-slate-900" x-show="$store.letterhead.active.layout === 'minimal-rule'">
                            <h2 class="text-lg font-bold" x-text="$store.letterhead.active.companyName"></h2>
                            <p class="text-[10px] text-slate-500" x-text="$store.letterhead.active.docTitle + ' · ' + getTodayDate()"></p>
                        </div>
                    </div>

                    <!-- Spreadsheet records table -->
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <template x-for="col in $store.spreadsheet.headers.filter(h => h.visible)" :key="col.key">
                                    <th x-text="col.label"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in $store.spreadsheet.rows" :key="idx">
                                <tr>
                                    <template x-for="col in $store.spreadsheet.headers.filter(h => h.visible)" :key="col.key">
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
</div>

<script>
    function appWorkspace() {
        return {
            isDragging: false,
            fileName: '',

            initApp() {
                // Check for pending uploaded file from landing index sessionStorage cache
                const pendingFile = sessionStorage.getItem('pt_pending_file');
                const pendingName = sessionStorage.getItem('pt_pending_name');

                if (pendingFile) {
                    this.fileName = pendingName || 'imported_file.xlsx';
                    this.loadBase64File(pendingFile);
                    
                    // Clear cache for security & storage space optimization
                    sessionStorage.removeItem('pt_pending_file');
                    sessionStorage.removeItem('pt_pending_name');
                }
            },

            loadBase64File(base64String) {
                try {
                    const binaryString = atob(base64String);
                    const len = binaryString.length;
                    const bytes = new Uint8Array(len);
                    for (let i = 0; i < len; i++) {
                        bytes[i] = binaryString.charCodeAt(i);
                    }
                    
                    const workbook = XLSX.read(bytes.buffer, { type: 'array' });
                    Alpine.store('spreadsheet').raw = workbook;
                    Alpine.store('spreadsheet').sheetNames = workbook.SheetNames;
                    Alpine.store('spreadsheet').selectSheet(workbook.SheetNames[0]);
                } catch (err) {
                    alert('Error parsing uploaded file. Please verify file integrity.');
                }
            },

            handleDrop(event) {
                const file = event.dataTransfer.files[0];
                if (file) {
                    this.fileName = file.name;
                    Alpine.store('spreadsheet').loadFile(file);
                }
            },

            handleFile(event) {
                const file = event.target.files[0];
                if (file) {
                    this.fileName = file.name;
                    Alpine.store('spreadsheet').loadFile(file);
                }
            },

            clearActiveFile() {
                this.fileName = '';
                Alpine.store('spreadsheet').isLoaded = false;
                Alpine.store('spreadsheet').raw = null;
                Alpine.store('spreadsheet').headers = [];
                Alpine.store('spreadsheet').rows = [];
            },

            getTodayDate() {
                return new Date().toLocaleDateString('en-GB');
            },

            initSortable() {
                this.$nextTick(() => {
                    if (this.$refs.columnsList) {
                        Sortable.create(this.$refs.columnsList, {
                            handle: '.drag-handle',
                            animation: 180,
                            onEnd: (evt) => {
                                const headers = Alpine.store('spreadsheet').headers;
                                const [moved] = headers.splice(evt.oldIndex, 1);
                                headers.splice(evt.newIndex, 0, moved);
                            }
                        });
                    }
                });
            },

            triggerPrint() {
                window.print();
            }
        }
    }
</script>
@endsection
