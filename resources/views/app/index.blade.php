@extends('layouts.app')

@section('title', 'PaperTrail — Format Report')

@push('head')
    <style>
        /* The workspace and the A4 document always render in a stable light
           palette — a dark OS / theme must never bleed into the paper or make
           table text unreadable. We pin the semantic tokens for this subtree. */
        .pt-app-scope {
            --bg:            #f6f7fb;
            --surface:       #ffffff;
            --surface-2:     #f0f2f8;
            --surface-3:     #e8ebf4;
            --border:        #dde1ee;
            --border-2:      #c8cde2;
            --ink:           #141428;
            --ink-2:         #454570;
            --ink-muted:     #8a8ab0;
            --accent:        #3b3c95;
            --accent-2:      #5b21b6;
            --accent-fg:     #ffffff;
            --accent-subtle: #eef0fb;
            --accent-muted:  #c7ccef;
            --accent-emphasis: #2e2f7a;
            color: var(--ink-2);
        }

        /* Sidebar styling */
        .workspace-sidebar {
            width: 320px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            height: calc(100vh - var(--toolbar-height));
        }

        /* Document table always fits the page width, in portrait or landscape.
           Headers wrap instead of overflowing the sheet. */
        .a4-page-container .doc-table {
            width: 100%;
            table-layout: auto;
        }
        .a4-page-container .doc-table th {
            white-space: normal;
        }
        .a4-page-container .doc-table td {
            word-break: break-word;
        }
        /* Optional bold final data row */
        .a4-page-container .doc-table tbody tr.pt-bold-row td {
            font-weight: 700;
            color: var(--ink);
        }

        /* Document paper sheet simulated page boundaries on screen */
        @media screen {
            .document-preview-pane {
                background: var(--bg);
                height: calc(100vh - var(--toolbar-height));
                overflow: auto;
            }
            .a4-page-container {
                width: 210mm;
                max-width: 100%;
                min-height: 297mm;
                background: white;
                box-shadow: var(--shadow-md);
                margin: 2rem auto;
                padding: 11.25mm;
                border: 1px solid var(--border);
                border-radius: var(--radius-xs);
            }
            :root[data-orientation="landscape"] .a4-page-container {
                width: 297mm;
                min-height: 210mm;
            }
        }
        @media print {
            /* Page margins on every printed page (portrait or landscape). */
            @page {
                margin: 11.25mm; /* Matches the screen preview sheet padding exactly */
            }
            .a4-page-container {
                padding: 0 !important; /* Handled by page margin */
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
                min-height: auto !important;
                max-width: none !important;
                width: 100% !important;
            }
            /* Carry the on-screen personality into print: letterhead accent,
               shaded table headers, and zebra rows all keep their colour. */
            #print-area,
            #print-area * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
@endpush

@section('content')
<div class="pt-app-scope min-h-screen flex flex-col font-sans bg-[#faf9f6]" x-data="appWorkspace()" x-init="initApp()">

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

        <div class="flex items-center gap-3" x-cloak x-show="$store.spreadsheet.isLoaded">
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
        <aside class="no-print workspace-sidebar shrink-0 overflow-y-auto p-5 space-y-6" x-cloak x-show="$store.spreadsheet.isLoaded">
            
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
                    <input type="text" x-model="$store.letterhead.active.date" @input="$store.letterhead.save()" placeholder="Document Date (blank = today)" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 outline-none focus:border-[#3b3c95]">
                    <div>
                        <label class="block text-[10px] text-[--ink-muted] uppercase mb-1">Date position</label>
                        <select x-model="$store.letterhead.active.datePosition" @change="$store.letterhead.save()" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 bg-white text-[--ink]">
                            <option value="top">On top (in letterhead)</option>
                            <option value="bottom">At bottom (under table)</option>
                        </select>
                    </div>
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
                        <option value="split-bordered">Split Bordered</option>
                        <option value="editorial-column">Editorial Column</option>
                        <option value="modern-minimalist">Modern Minimalist</option>
                        <option value="corporate-block">Corporate Block</option>
                        <option value="asymmetric-compact">Asymmetric Compact</option>
                        <option value="compact-grid">Compact Grid</option>
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
                            <input type="text" x-model="col.label" class="bg-transparent border-none outline-none font-medium flex-1 min-w-0 text-[--ink]">
                            <select :value="col.type"
                                    @change="col.type = $event.target.value; $store.spreadsheet.applyAlignment(col)"
                                    class="shrink-0 text-[10px] bg-white border border-slate-200 rounded px-1 py-0.5 text-[--ink-2]"
                                    title="Column type">
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

            <!-- Table Options (Step 05) -->
            <div class="space-y-2.5 pt-4 border-t border-slate-100">
                <h3 class="font-bold text-xs uppercase tracking-wider text-[--ink-muted]">Table Options</h3>
                <label class="flex items-center justify-between text-xs text-[--ink-2] cursor-pointer">
                    <span>Show totals row</span>
                    <input type="checkbox" :checked="$store.settings.showTotals" @change="$store.settings.setShowTotals($event.target.checked)" class="rounded border-slate-300 text-[#3b3c95]">
                </label>
                <label class="flex items-center justify-between text-xs text-[--ink-2] cursor-pointer">
                    <span>Bold last row</span>
                    <input type="checkbox" :checked="$store.settings.boldLastRow" @change="$store.settings.setBoldLastRow($event.target.checked)" class="rounded border-slate-300 text-[#3b3c95]">
                </label>
                <label class="flex items-center justify-between text-xs text-[--ink-2]">
                    <span>Table font size</span>
                    <span class="flex items-center gap-1">
                        <input type="number" min="7" max="24" step="1"
                               :value="$store.settings.tableFontSize"
                               @input="$store.settings.setTableFontSize($event.target.value)"
                               class="w-14 text-xs border border-[--border] rounded px-2 py-1 bg-white text-[--ink] text-right">
                        <span class="text-[10px] text-[--ink-muted]">px</span>
                    </span>
                </label>

                <!-- Custom Table Header Colors -->
                <div class="space-y-2 pt-2 border-t border-slate-100/60">
                    <label class="block text-[10px] text-[--ink-muted] uppercase">Header Custom Colors</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-[9px] text-[--ink-muted] block mb-1">Background</span>
                            <div class="flex items-center gap-1">
                                <input type="color" :value="$store.settings.thBg || '#ffffff'" @input="$store.settings.setThBg($event.target.value)" class="w-6 h-6 rounded border border-[--border] cursor-pointer bg-transparent p-0">
                                <button type="button" @click="$store.settings.setThBg('')" class="text-[9px] text-red-500 hover:text-red-600 font-medium" x-show="$store.settings.thBg">Clear</button>
                            </div>
                        </div>
                        <div>
                            <span class="text-[9px] text-[--ink-muted] block mb-1">Text Color</span>
                            <div class="flex items-center gap-1">
                                <input type="color" :value="$store.settings.thText || '#000000'" @input="$store.settings.setThText($event.target.value)" class="w-6 h-6 rounded border border-[--border] cursor-pointer bg-transparent p-0">
                                <button type="button" @click="$store.settings.setThText('')" class="text-[9px] text-red-500 hover:text-red-600 font-medium" x-show="$store.settings.thText">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer options -->
            <div class="space-y-3 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-[--ink-muted]">Footer Options</h3>
                    <input type="checkbox" :checked="$store.settings.showFooter" @change="$store.settings.setShowFooter($event.target.checked)" class="rounded border-slate-300 text-[#3b3c95]">
                </div>
                <div class="space-y-2" x-show="$store.settings.showFooter">
                    <label class="block text-[10px] text-[--ink-muted] uppercase">Footer Style</label>
                    <select :value="$store.settings.footerLayout" @change="$store.settings.setFooterLayout($event.target.value)" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 bg-white text-[--ink]">
                        <option value="simple-text">Simple Text</option>
                        <option value="brand-accent">Brand Accent Line</option>
                        <option value="split-footnote">Split Footnote</option>
                    </select>

                    <label class="block text-[10px] text-[--ink-muted] uppercase mt-2">Footer Text</label>
                    <input type="text" :value="$store.settings.footerText" @input="$store.settings.setFooterText($event.target.value)" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 bg-white text-[--ink]">
                </div>
            </div>

            <!-- Date & Numbers (Step 09) -->
            <div class="space-y-3 pt-4 border-t border-slate-100">
                <h3 class="font-bold text-xs uppercase tracking-wider text-[--ink-muted]">Date &amp; Numbers</h3>
                <div class="space-y-2.5">
                    <div>
                        <label class="block text-[10px] text-[--ink-muted] uppercase mb-1">Date format <span class="normal-case text-slate-400">(date columns only)</span></label>
                        <select @change="$store.settings.setDateFormat($event.target.value)" :value="$store.settings.dateFormat" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 bg-white text-[--ink]">
                            <option value="original">Keep original (as in file)</option>
                            <option value="dd/mm/yyyy">DD/MM/YYYY</option>
                            <option value="dd.mm.yyyy">DD.MM.YYYY</option>
                            <option value="dd.mm.yy">DD.MM.YY</option>
                            <option value="mm/dd/yyyy">MM/DD/YYYY</option>
                            <option value="mm.dd.yyyy">MM.DD.YYYY</option>
                            <option value="mm.dd.yy">MM.DD.YY</option>
                            <option value="yyyy-mm-dd">YYYY-MM-DD</option>
                            <option value="yyyy.mm.dd">YYYY.MM.DD</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] text-[--ink-muted] uppercase mb-1">Totals number format</label>
                        <select @change="$store.settings.setNumberFormat($event.target.value)" :value="$store.settings.numberFormat" class="w-full text-xs border border-[--border] rounded-lg px-3 py-2 bg-white text-[--ink]">
                            <option value="western">Western (1,234,567)</option>
                            <option value="bd">BD Lakh/Crore (12,34,567)</option>
                        </select>
                    </div>
                </div>
            </div>
        </aside>

        <!-- B. Right Main Preview / Ingestion Area -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- I. No file loaded: Upload box fallback -->
            <div class="flex-1 flex flex-col justify-center items-center p-8 no-print bg-[#faf9f6] bg-grid" x-cloak x-show="!$store.spreadsheet.isLoaded">
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
            <div class="document-preview-pane flex-1 w-full" x-cloak x-show="$store.spreadsheet.isLoaded" :data-table-style="$store.theme.tableStyle">
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
                                <p class="text-xs text-slate-500" x-show="$store.letterhead.active.datePosition === 'top'" x-text="'Date: ' + docDate()"></p>
                                <p class="text-xs text-slate-500" x-show="$store.letterhead.active.statementFor" x-text="'For: ' + $store.letterhead.active.statementFor"></p>
                            </div>
                        </div>

                        <!-- Minimal rule -->
                        <div class="text-center pb-4 mb-6 border-b border-slate-900" x-show="$store.letterhead.active.layout === 'minimal-rule'">
                            <h2 class="text-lg font-bold" x-text="$store.letterhead.active.companyName"></h2>
                            <p class="text-[10px] text-slate-500" x-text="$store.letterhead.active.docTitle + ($store.letterhead.active.datePosition === 'top' ? ' · ' + docDate() : '')"></p>
                        </div>

                        <!-- Centered -->
                        <div class="text-center pb-6 mb-8 border-b-2 border-[#3b3c95]" x-show="$store.letterhead.active.layout === 'centered'">
                            <h2 class="text-xl font-bold tracking-tight text-slate-900" x-text="$store.letterhead.active.companyName || 'Company Name'"></h2>
                            <p class="text-xs text-slate-500 mt-0.5 whitespace-pre-line" x-text="$store.letterhead.active.address"></p>
                            <div class="mt-2 flex items-center justify-center gap-3 text-[10px] font-mono text-slate-500">
                                <span class="font-bold uppercase tracking-wider text-[#3b3c95]" x-text="$store.letterhead.active.docTitle || 'DOCUMENT'"></span>
                                <span x-show="$store.letterhead.active.datePosition === 'top'" x-text="'Date: ' + docDate()"></span>
                                <span x-show="$store.letterhead.active.statementFor" x-text="'For: ' + $store.letterhead.active.statementFor"></span>
                            </div>
                        </div>

                        <!-- Left accent bar -->
                        <div class="flex gap-3 pb-6 mb-8 border-b border-slate-200" x-show="$store.letterhead.active.layout === 'left-accent-bar'">
                            <div class="w-1 rounded bg-[#3b3c95] shrink-0"></div>
                            <div class="flex-1 flex items-start justify-between">
                                <div class="space-y-0.5">
                                    <h2 class="text-lg font-bold tracking-tight text-slate-900" x-text="$store.letterhead.active.companyName || 'Company Name'"></h2>
                                    <p class="text-xs text-slate-500 whitespace-pre-line" x-text="$store.letterhead.active.address"></p>
                                </div>
                                <div class="text-right font-mono space-y-0.5">
                                    <h3 class="text-sm font-bold text-[#3b3c95] uppercase tracking-wider" x-text="$store.letterhead.active.docTitle || 'DOCUMENT'"></h3>
                                    <p class="text-xs text-slate-500" x-show="$store.letterhead.active.datePosition === 'top'" x-text="'Date: ' + docDate()"></p>
                                    <p class="text-xs text-slate-500" x-show="$store.letterhead.active.statementFor" x-text="'For: ' + $store.letterhead.active.statementFor"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Monogram inline -->
                        <div class="flex items-center justify-between pb-6 mb-8 border-b-2 border-[#3b3c95]" x-show="$store.letterhead.active.layout === 'monogram-inline'">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-full bg-[#3b3c95]/10 text-[#3b3c95] flex items-center justify-center font-black text-lg shrink-0"
                                     x-text="($store.letterhead.active.companyName || 'My').trim().slice(0,2).toUpperCase()"></div>
                                <div class="space-y-0.5">
                                    <h2 class="text-lg font-bold tracking-tight text-slate-900" x-text="$store.letterhead.active.companyName || 'Company Name'"></h2>
                                    <p class="text-xs text-slate-500" x-text="$store.letterhead.active.address"></p>
                                </div>
                            </div>
                            <div class="text-right font-mono space-y-0.5">
                                <h3 class="text-sm font-bold text-[#3b3c95] uppercase tracking-wider" x-text="$store.letterhead.active.docTitle || 'DOCUMENT'"></h3>
                                <p class="text-xs text-slate-500" x-show="$store.letterhead.active.datePosition === 'top'" x-text="'Date: ' + docDate()"></p>
                            </div>
                        </div>

                        <!-- Split Bordered -->
                        <div class="border-y-2 border-[#3b3c95] py-4 mb-8 flex justify-between items-start" x-show="$store.letterhead.active.layout === 'split-bordered'">
                            <div class="space-y-1 flex items-center gap-4">
                                <img x-show="$store.letterhead.active.logoBase64" :src="$store.letterhead.active.logoBase64" class="shrink-0" :style="'height: ' + $store.letterhead.active.logoHeight + 'px'">
                                <div>
                                    <h2 class="text-xl font-bold tracking-tight text-slate-900" x-text="$store.letterhead.active.companyName || 'Company Name'"></h2>
                                    <p class="text-xs text-slate-500 whitespace-pre-line leading-relaxed" x-text="$store.letterhead.active.address || 'Address information'"></p>
                                </div>
                            </div>
                            <div class="text-right space-y-1 font-mono shrink-0">
                                <h3 class="text-sm font-bold text-[#3b3c95] tracking-wider uppercase" x-text="$store.letterhead.active.docTitle || 'DOCUMENT'"></h3>
                                <p class="text-xs text-slate-500" x-show="$store.letterhead.active.datePosition === 'top'" x-text="'Date: ' + docDate()"></p>
                                <p class="text-xs text-slate-500" x-show="$store.letterhead.active.statementFor" x-text="'For: ' + $store.letterhead.active.statementFor"></p>
                            </div>
                        </div>

                        <!-- Editorial Column -->
                        <div class="grid grid-cols-3 gap-6 pb-6 mb-8 border-b-2 border-slate-200" x-show="$store.letterhead.active.layout === 'editorial-column'">
                            <div class="col-span-2 border-r border-slate-200 pr-6 flex items-start gap-4">
                                <img x-show="$store.letterhead.active.logoBase64" :src="$store.letterhead.active.logoBase64" class="shrink-0" :style="'height: ' + $store.letterhead.active.logoHeight + 'px'">
                                <div class="space-y-1">
                                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-950 uppercase" x-text="$store.letterhead.active.companyName || 'Company Name'"></h2>
                                    <p class="text-[11px] text-slate-400 italic" x-show="$store.letterhead.active.tagline" x-text="$store.letterhead.active.tagline"></p>
                                    <p class="text-xs text-slate-500 pt-1 whitespace-pre-line leading-normal" x-text="$store.letterhead.active.address"></p>
                                </div>
                            </div>
                            <div class="pl-2 space-y-2 text-right self-end font-mono shrink-0">
                                <h3 class="text-sm font-black text-[#3b3c95] tracking-widest uppercase" x-text="$store.letterhead.active.docTitle || 'DOCUMENT'"></h3>
                                <div class="text-[10px] text-slate-500 space-y-0.5">
                                    <p x-show="$store.letterhead.active.datePosition === 'top'" x-text="'Date: ' + docDate()"></p>
                                    <p x-show="$store.letterhead.active.statementFor" x-text="'For: ' + $store.letterhead.active.statementFor"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Modern Minimalist -->
                        <div class="flex items-start justify-between pb-6 mb-8 border-b border-slate-100" x-show="$store.letterhead.active.layout === 'modern-minimalist'">
                            <div class="flex items-center gap-6">
                                <img x-show="$store.letterhead.active.logoBase64" :src="$store.letterhead.active.logoBase64" class="shrink-0" :style="'height: ' + $store.letterhead.active.logoHeight + 'px'">
                                <div class="h-8 w-px bg-slate-200"></div>
                                <div>
                                    <h2 class="text-lg font-black uppercase tracking-widest text-slate-900" x-text="$store.letterhead.active.companyName || 'Company Name'"></h2>
                                    <p class="text-[10px] text-slate-400 font-mono tracking-wider" x-text="$store.letterhead.active.tagline"></p>
                                </div>
                            </div>
                            <div class="text-right text-xs text-slate-500 font-mono leading-tight shrink-0">
                                <h3 class="font-bold text-[#3b3c95] uppercase text-xs" x-text="$store.letterhead.active.docTitle || 'DOCUMENT'"></h3>
                                <p class="mt-1" x-show="$store.letterhead.active.datePosition === 'top'" x-text="'Date: ' + docDate()"></p>
                                <p x-show="$store.letterhead.active.statementFor" x-text="'For: ' + $store.letterhead.active.statementFor"></p>
                            </div>
                        </div>

                        <!-- Corporate Block -->
                        <div class="bg-slate-50 rounded-xl p-5 mb-8 border border-slate-200/80" x-show="$store.letterhead.active.layout === 'corporate-block'">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-4">
                                    <img x-show="$store.letterhead.active.logoBase64" :src="$store.letterhead.active.logoBase64" class="shrink-0" :style="'height: ' + $store.letterhead.active.logoHeight + 'px'">
                                    <div>
                                        <h2 class="text-xl font-extrabold text-slate-900" x-text="$store.letterhead.active.companyName || 'Company Name'"></h2>
                                        <p class="text-xs text-slate-500" x-text="$store.letterhead.active.address"></p>
                                    </div>
                                </div>
                                <div class="text-right font-mono shrink-0">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-[#3b3c95]" x-text="$store.letterhead.active.docTitle || 'DOCUMENT'"></h3>
                                    <p class="text-[10px] text-slate-400 mt-1" x-show="$store.letterhead.active.datePosition === 'top'" x-text="'Date: ' + docDate()"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Asymmetric Compact -->
                        <div class="flex items-start justify-between pb-6 mb-8 border-b-2 border-dashed border-slate-200" x-show="$store.letterhead.active.layout === 'asymmetric-compact'">
                            <div class="space-y-1">
                                <div class="flex items-center gap-3">
                                    <div x-show="!$store.letterhead.active.logoBase64" class="w-8 h-8 rounded-lg bg-[#3b3c95]/10 text-[#3b3c95] flex items-center justify-center font-black text-sm" x-text="($store.letterhead.active.companyName || 'My').trim().slice(0,2).toUpperCase()"></div>
                                    <img x-show="$store.letterhead.active.logoBase64" :src="$store.letterhead.active.logoBase64" class="shrink-0" :style="'height: ' + $store.letterhead.active.logoHeight + 'px'">
                                    <h2 class="text-lg font-bold text-slate-900" x-text="$store.letterhead.active.companyName || 'Company Name'"></h2>
                                </div>
                                <p class="text-xs text-slate-500 max-w-md pt-1" x-text="$store.letterhead.active.address"></p>
                            </div>
                            <div class="text-right shrink-0">
                                <h3 class="text-sm font-extrabold uppercase text-[#3b3c95] tracking-wider" x-text="$store.letterhead.active.docTitle || 'DOCUMENT'"></h3>
                                <div class="text-xs text-slate-500 font-mono mt-1 space-y-0.5">
                                    <p x-show="$store.letterhead.active.datePosition === 'top'" x-text="'Date: ' + docDate()"></p>
                                    <p x-show="$store.letterhead.active.statementFor" x-text="'For: ' + $store.letterhead.active.statementFor"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Grid -->
                        <div class="grid grid-cols-3 gap-6 pb-6 mb-8 border-b-2 border-slate-200 text-xs text-slate-700" x-show="$store.letterhead.active.layout === 'compact-grid'">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <img x-show="$store.letterhead.active.logoBase64" :src="$store.letterhead.active.logoBase64" class="shrink-0" :style="'height: ' + $store.letterhead.active.logoHeight + 'px'">
                                    <h2 class="font-bold text-slate-900" x-text="$store.letterhead.active.companyName || 'Company Name'"></h2>
                                </div>
                                <p class="text-[10px] text-slate-500 whitespace-pre-line leading-tight" x-text="$store.letterhead.active.address"></p>
                            </div>
                            <div class="border-l border-slate-200 pl-6 space-y-1 font-mono">
                                <h3 class="font-bold uppercase text-[#3b3c95]" x-text="$store.letterhead.active.docTitle || 'DOCUMENT'"></h3>
                                <p class="text-[10px] text-slate-500" x-show="$store.letterhead.active.datePosition === 'top'" x-text="'Date: ' + docDate()"></p>
                            </div>
                            <div class="border-l border-slate-200 pl-6 space-y-1">
                                <p class="font-semibold text-slate-700" x-show="$store.letterhead.active.statementFor" x-text="'For: ' + $store.letterhead.active.statementFor"></p>
                                <p class="text-[10px] text-slate-500" x-show="$store.letterhead.active.bin" x-text="'BIN: ' + $store.letterhead.active.bin"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Spreadsheet records table -->
                    <table class="doc-table" :style="{ 'font-size': computedTableFontSize + 'px', '--th-bg': $store.settings.thBg || undefined, '--th-text': $store.settings.thText || undefined }">
                        <thead>
                            <tr>
                                <template x-for="col in $store.spreadsheet.headers.filter(h => h.visible)" :key="col.key">
                                    <th :class="col.align === 'right' ? 'align-right' : ''">
                                        <span class="inline-flex items-center gap-1 max-w-full align-middle" :class="col.align === 'right' ? 'flex-row-reverse' : ''">
                                            <input type="text" x-model="col.label"
                                                   @keydown.enter="$event.target.blur()"
                                                   :size="Math.max((col.label || '').length, 3)"
                                                   title="Click to rename this column"
                                                   class="bg-transparent border-0 border-b border-dashed border-transparent hover:border-slate-300 focus:border-[#3b3c95] outline-none p-0 max-w-full"
                                                   :style="'font: inherit; color: inherit; width: auto;' + (col.align === 'right' ? 'text-align:right;' : '')">
                                            <button type="button" @click="sortBy(col.key)"
                                                    class="no-print text-[10px] leading-none text-slate-400 hover:text-[#3b3c95] shrink-0"
                                                    title="Sort by this column">
                                                <span x-show="sortCol === col.key" x-text="sortDir === 'asc' ? '▲' : '▼'"></span>
                                                <span x-show="sortCol !== col.key">↕</span>
                                            </button>
                                        </span>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in sortedRows" :key="idx">
                                <tr :class="$store.settings.boldLastRow && idx === sortedRows.length - 1 ? 'pt-bold-row' : ''">
                                    <template x-for="col in $store.spreadsheet.headers.filter(h => h.visible)" :key="col.key">
                                        <td :class="col.align === 'right' ? 'align-right' : ''"
                                            x-text="formatCell(row[col.key], col.type)"></td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot x-show="$store.settings.showTotals && hasNumericColumns()">
                            <tr>
                                <template x-for="(col, i) in $store.spreadsheet.headers.filter(h => h.visible)" :key="col.key">
                                    <td :class="col.align === 'right' ? 'align-right' : ''"
                                        x-text="i === 0 ? 'Total' : summaryFor(col)"></td>
                                </template>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Document date at the bottom (when chosen) -->
                    <div x-show="$store.letterhead.showOnPrint && $store.letterhead.active.datePosition === 'bottom'"
                         class="mt-8 text-right text-xs font-mono text-slate-500"
                         x-text="'Date: ' + docDate()"></div>

                    <!-- Dynamic Footnote Footer (Max 1 Line) -->
                    <div x-show="$store.settings.showFooter" class="mt-6 border-t border-slate-100 pt-2 text-[10px] text-slate-400 font-mono">
                        <!-- Simple Text Layout -->
                        <div class="text-center" x-show="$store.settings.footerLayout === 'simple-text'" x-text="$store.settings.footerText"></div>

                        <!-- Brand Accent Line Layout -->
                        <div class="space-y-1" x-show="$store.settings.footerLayout === 'brand-accent'">
                            <div class="h-0.5 w-full bg-[--accent] opacity-30"></div>
                            <div class="text-center" x-text="($store.letterhead.active.companyName || 'Paper Trail') + ' · ' + $store.settings.footerText"></div>
                        </div>

                        <!-- Split Footnote Layout -->
                        <div class="flex justify-between items-center" x-show="$store.settings.footerLayout === 'split-footnote'">
                            <span class="font-bold" x-text="$store.letterhead.active.companyName || 'Paper Trail'"></span>
                            <span x-text="$store.settings.footerText"></span>
                        </div>
                    </div>
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
            sortCol: null,
            sortDir: 'asc',
            computedTableFontSize: 14,

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

                this.initSortable();

                // Setup watchers for the auto-fit table size engine
                this.$watch('$store.settings.tableFontSize', (val) => {
                    this.computedTableFontSize = parseFloat(val) || 14;
                    this.fitTableToPage();
                });
                this.$watch('$store.theme.orientation', () => {
                    this.fitTableToPage();
                });
                this.$watch('$store.spreadsheet.headers', () => {
                    this.fitTableToPage();
                }, { deep: true });
                this.$watch('$store.spreadsheet.rows', () => {
                    this.fitTableToPage();
                });

                window.addEventListener('resize', () => this.fitTableToPage());
            },

            // ── Row sorting (Step 05) ──────────────────────────────
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
                    const numA = parseFloat(String(va).replace(/[^\d.-]/g, ''));
                    const numB = parseFloat(String(vb).replace(/[^\d.-]/g, ''));

                    if (!isNaN(numA) && !isNaN(numB)) {
                        return this.sortDir === 'asc' ? numA - numB : numB - numA;
                    }
                    return this.sortDir === 'asc'
                        ? String(va).localeCompare(String(vb))
                        : String(vb).localeCompare(String(va));
                });
            },

            hasNumericColumns() {
                return Alpine.store('spreadsheet').headers
                    .filter((h) => h.visible)
                    .some((h) => ['integer', 'decimal', 'currency'].includes(h.type));
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
                // Reset so re-selecting the SAME file later still fires @change.
                event.target.value = '';
            },

            clearActiveFile() {
                this.fileName = '';
                this.sortCol = null;
                this.sortDir = 'asc';
                Alpine.store('spreadsheet').isLoaded = false;
                Alpine.store('spreadsheet').raw = null;
                Alpine.store('spreadsheet').headers = [];
                Alpine.store('spreadsheet').rows = [];
                if (this.$refs.toolInput) this.$refs.toolInput.value = '';
            },

            getTodayDate() {
                return new Date().toLocaleDateString('en-GB');
            },

            // The document date: the letterhead's own value, or today if blank.
            docDate() {
                return Alpine.store('letterhead').active.date || this.getTodayDate();
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
                if (typeof trackEvent === 'function') {
                    trackEvent('print', {
                        rows: Alpine.store('spreadsheet').rows.length,
                        cols: Alpine.store('spreadsheet').headers.filter((h) => h.visible).length,
                    });
                }

                // Gather print dataset
                const payload = {
                    headers: Alpine.store('spreadsheet').headers,
                    rows: Alpine.store('spreadsheet').rows,
                    letterhead: Alpine.store('letterhead').active,
                    showLetterhead: Alpine.store('letterhead').showOnPrint,
                    tableStyle: Alpine.store('theme').tableStyle,
                    dashboard: Alpine.store('spreadsheet').dashboard,
                    theme: Alpine.store('theme').current,
                    customAccent: Alpine.store('theme').customAccent,
                    orientation: Alpine.store('theme').orientation,
                    dateStr: this.docDate(),
                    settings: {
                        numberFormat: Alpine.store('settings') ? Alpine.store('settings').numberFormat : 'western',
                        dateFormat: Alpine.store('settings') ? Alpine.store('settings').dateFormat : 'dd/mm/yyyy',
                        currencySymbol: Alpine.store('settings') ? Alpine.store('settings').currencySymbol : '$',
                        tableFontSize: Alpine.store('settings') ? Alpine.store('settings').tableFontSize : 14,
                        showTotals: Alpine.store('settings') ? Alpine.store('settings').showTotals : false,
                        boldLastRow: Alpine.store('settings') ? Alpine.store('settings').boldLastRow : false,
                        showFooter: Alpine.store('settings') ? Alpine.store('settings').showFooter : false,
                        footerLayout: Alpine.store('settings') ? Alpine.store('settings').footerLayout : 'simple-text',
                        footerText: Alpine.store('settings') ? Alpine.store('settings').footerText : 'Thank you for your business.',
                        thBg: Alpine.store('settings') ? Alpine.store('settings').thBg : '',
                        thText: Alpine.store('settings') ? Alpine.store('settings').thText : ''
                    }
                };

                sessionStorage.setItem('pt_print_data', JSON.stringify(payload));

                // Open print preview page in a clean new tab
                window.open("{{ route('app.print') }}", "_blank");
            },

            fitTableToPage() {
                this.$nextTick(() => {
                    const container = document.getElementById('print-area');
                    if (!container) return;
                    const table = container.querySelector('.doc-table');
                    if (!table) return;

                    let baseSize = parseFloat(Alpine.store('settings').tableFontSize) || 14;
                    this.computedTableFontSize = baseSize;
                    table.style.fontSize = baseSize + 'px';

                    this.$nextTick(() => {
                        let containerWidth = container.clientWidth - 32; // safety gap for padding margins
                        let currentSize = baseSize;
                        let safetyCounter = 0;
                        const minAllowedSize = 8; // don't shrink below 8px for readability

                        while (table.scrollWidth > containerWidth && currentSize > minAllowedSize && safetyCounter < 15) {
                            currentSize -= 0.5;
                            this.computedTableFontSize = currentSize;
                            table.style.fontSize = currentSize + 'px';
                            safetyCounter++;
                        }
                    });
                });
            }
        }
    }
</script>
@endsection
