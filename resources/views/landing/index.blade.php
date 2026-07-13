@extends('layouts.app')

@section('title', 'PaperTrail — Format Spreadsheets into Clean PDF Reports')
@section('meta_description', 'Convert cluttered Excel, CSV or TSV spreadsheets into elegant, print-ready A4 PDF reports in seconds. Automatic alignment, currency formatting and custom letterheads — 100% in your browser, so your data never leaves your device. Free and private.')

@push('structured-data')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => 'How do I convert a spreadsheet to a PDF report?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Open PaperTrail, drag in your Excel (XLSX), CSV or TSV file, adjust the styling and letterhead if needed, then use Print to save a clean, print-ready A4 PDF. Everything happens in your browser — no upload or sign-up required.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Is my data uploaded to a server?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'No. PaperTrail processes your spreadsheet entirely on your device using client-side JavaScript. Your files and their contents never leave your browser and are never sent to any server.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Which file formats does PaperTrail support?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'PaperTrail supports Excel .xlsx, .csv and .tsv files up to 30 MB. Columns are automatically aligned, and currency, number and date values are formatted for professional business documents.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Is PaperTrail free to use?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Yes. PaperTrail is completely free to use, with no account, watermark or upload required.',
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
@push('head')
    <!-- Google Fonts: Plus Jakarta Sans for headers -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Premium Background Grid Pattern */
        .bg-grid-dot {
            background-size: 20px 20px;
            background-image: radial-gradient(circle, rgba(99, 102, 241, 0.08) 1px, transparent 1px);
        }

        /* Smooth page entrance animations */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-up {
            animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Glass hover effects */
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .hover-glow:hover {
            box-shadow: 0 20px 40px -15px rgba(99, 102, 241, 0.12);
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-2px);
        }
    </style>
@endpush

<div class="min-h-screen bg-[#fcfbfa] text-[#0f0f1d] flex flex-col font-sans selection:bg-indigo-500/10 selection:text-indigo-600 bg-grid-dot relative overflow-x-hidden" x-data="landingUploader()">

    <!-- Glowing background color mesh blobs (lively feel) -->
    <div class="absolute top-[-100px] left-1/4 w-[500px] h-[500px] bg-gradient-to-tr from-indigo-300/30 to-purple-300/30 rounded-full blur-3xl -z-10 pointer-events-none"></div>
    <div class="absolute top-[300px] right-[-100px] w-[600px] h-[600px] bg-gradient-to-br from-indigo-200/25 to-pink-200/25 rounded-full blur-3xl -z-10 pointer-events-none"></div>
    <div class="absolute top-[800px] left-[-200px] w-[500px] h-[500px] bg-gradient-to-tr from-emerald-100/30 to-indigo-100/30 rounded-full blur-3xl -z-10 pointer-events-none"></div>

    {{-- ══════════ PREMIUM MINIMALIST NAVIGATION ══════════ --}}
    <header class="w-full bg-[#fcfbfa]/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200/40">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <!-- Monospace Brand Logo -->
            <a href="{{ route('home') }}" class="group flex items-center gap-2">
                <span class="font-mono text-xs uppercase tracking-[0.3em] font-extrabold text-[#0f0f1d] group-hover:text-indigo-600 transition-colors duration-200">
                    PAPER<span class="text-indigo-600 font-black group-hover:text-[#0f0f1d] transition-colors duration-200">/</span>TRAIL
                </span>
            </a>

            <!-- Subdued Navigation Links -->
            <div class="flex items-center gap-8">
                <a href="#how-it-works" class="text-[11px] font-mono uppercase tracking-wider text-slate-500 hover:text-indigo-600 transition-colors duration-150 hidden sm:inline">
                    Workflow
                </a>
                <a href="#playground" class="text-[11px] font-mono uppercase tracking-wider text-slate-500 hover:text-indigo-600 transition-colors duration-150 hidden sm:inline">
                    Live Demo
                </a>
                <a href="{{ route('app') }}" class="group inline-flex items-center gap-1.5 text-[11px] font-mono uppercase tracking-widest text-[#0f0f1d] hover:text-indigo-600 transition-colors duration-150 font-bold">
                    Workspace 
                    <span class="inline-block transform group-hover:translate-x-0.5 transition-transform duration-150">→</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">

        {{-- ══════════ HERO SECTION ══════════ --}}
        <section class="max-w-6xl mx-auto px-6 pt-16 pb-24 grid lg:grid-cols-12 gap-12 items-center animate-fade-up">
            
            <!-- Left Info Block -->
            <div class="lg:col-span-7 space-y-8 text-left">
                <div class="space-y-5">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200/80 bg-emerald-50 px-3.5 py-1 text-xs font-semibold text-emerald-800 shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        100% secure client-side parsing
                    </span>
                    
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-[1.08] font-['Plus_Jakarta_Sans']">
                        Beautiful reports from <br>
                        <span class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 bg-clip-text text-transparent">raw spreadsheets.</span>
                    </h1>
                    
                    <p class="text-slate-600 text-sm sm:text-base max-w-xl leading-relaxed">
                        Convert cluttered Excel sheet logs or CSV databases into elegant, print-ready document PDFs instantly. Clean alignment, currency formatting, custom letterhead profiles, and telemetry-free security right in your browser.
                    </p>
                </div>

                <!-- Action Button Cluster -->
                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('app') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-md shadow-indigo-600/10 hover:shadow-lg hover:shadow-indigo-600/20 active:scale-[0.98]">
                        Open Clean Workspace
                    </a>
                    <button type="button" @click="loadDemoData()" class="border border-slate-300 hover:border-indigo-600 text-slate-700 hover:text-indigo-600 bg-white px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-sm active:scale-[0.98]">
                        ⚡ Load Demo Sheet
                    </button>
                </div>

                <!-- Value Proposition Highlights -->
                <div class="grid grid-cols-2 gap-4 text-xs font-semibold text-slate-500 max-w-md pt-2">
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px]">✓</span> Auto Type-Detection
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px]">✓</span> Drag-to-Reorder Columns
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px]">✓</span> Premium Custom Footers
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px]">✓</span> Over 10+ Header Profiles
                    </div>
                </div>
            </div>

            <!-- Right Interactive Upload Portal -->
            <div class="lg:col-span-5 w-full">
                <div
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="isDragging = false; handleDrop($event)"
                    :class="isDragging ? 'border-indigo-500 bg-indigo-500/5 scale-[1.01] shadow-lg shadow-indigo-500/5' : 'border-slate-300/80 hover:border-indigo-600/40 bg-white shadow-md'"
                    class="border border-dashed rounded-3xl p-10 text-center transition-all duration-200 cursor-pointer relative group flex flex-col justify-center min-h-[340px]"
                    @click="$refs.fileInput.click()"
                >
                    <input type="file" accept=".xlsx,.xls,.csv,.tsv" class="hidden" x-ref="fileInput" @change="handleFile($event)">
                    
                    <div class="space-y-6">
                        <!-- Portal Icon wrapper -->
                        <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto group-hover:scale-105 transition-transform duration-200 shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                            </svg>
                        </div>
                        
                        <!-- Texts -->
                        <div class="space-y-2">
                            <p class="text-base font-bold text-slate-800">Drop your spreadsheet here</p>
                            <p class="text-xs text-slate-400 max-w-xs mx-auto">Supports xlsx, csv, or tsv files. Your data is handled entirely client-side and never uploaded.</p>
                        </div>

                        <!-- Action Indicator -->
                        <div class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 group-hover:underline">
                            or browse local files
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════ DYNAMIC THEME PLAYGROUND / SIMULATOR ══════════ --}}
        <section id="playground" class="py-24 border-t border-slate-200/60 bg-white" x-data="playgroundController()">
            <div class="max-w-6xl mx-auto px-6">
                
                <div class="max-w-xl mx-auto text-center space-y-3 mb-16">
                    <span class="font-mono text-[10px] uppercase tracking-[0.25em] text-indigo-600 font-bold">Interactive Showcase</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 font-['Plus_Jakarta_Sans']">Live styling playground</h2>
                    <p class="text-xs sm:text-sm text-slate-500">Preview the exact appearance of A4 document reports with real-time styling parameters below.</p>
                </div>

                <div class="grid lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Left Toolbar Panel -->
                    <div class="lg:col-span-4 space-y-6 bg-slate-50 border border-slate-200/80 rounded-2xl p-6">
                        
                        <!-- 1. Color Palette selectors -->
                        <div class="space-y-2">
                            <label class="block text-[10px] uppercase font-mono tracking-wider text-slate-400 font-bold">1. Accent Color</label>
                            <div class="flex flex-wrap gap-2.5">
                                <template x-for="item in colors" :key="item.id">
                                    <button @click="activeColor = item.id" 
                                            :title="item.label"
                                            :class="activeColor === item.id ? 'ring-2 ring-indigo-600 ring-offset-2 scale-105' : 'hover:scale-105'"
                                            class="w-8 h-8 rounded-full border border-slate-200 transition-all cursor-pointer relative"
                                            :style="`background-color: ${item.hex}`">
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- 2. Table Layout selectors -->
                        <div class="space-y-2">
                            <label class="block text-[10px] uppercase font-mono tracking-wider text-slate-400 font-bold">2. Table Style</label>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="item in tableStyles" :key="item.id">
                                    <button @click="activeStyle = item.id"
                                            :class="activeStyle === item.id ? 'border-indigo-600 text-indigo-600 bg-white font-bold' : 'border-slate-200 text-slate-600 bg-transparent hover:bg-white'"
                                            class="border px-3 py-2 rounded-xl text-xs text-center transition-all cursor-pointer"
                                            x-text="item.label">
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- 3. Sample Sheet selector -->
                        <div class="space-y-2">
                            <label class="block text-[10px] uppercase font-mono tracking-wider text-slate-400 font-bold">3. Sample Sheet Dataset</label>
                            <select x-model="activeData" class="w-full border border-slate-200 rounded-xl px-3 py-2 bg-white text-xs text-slate-700 outline-none">
                                <option value="sales">Sales Invoice Ledger</option>
                                <option value="billing">Monthly Service Billing</option>
                                <option value="inventory">Equipment Logistics Logs</option>
                            </select>
                        </div>
                    </div>

                    <!-- Right Sheet Simulator -->
                    <div class="lg:col-span-8 bg-white border border-slate-200/80 rounded-2xl p-8 sm:p-12 shadow-md relative overflow-hidden transition-all duration-300"
                         :style="`border-top-width: 6px; border-top-color: ${getSelectedColorHex()}`">
                        
                        <!-- Sim Head -->
                        <div class="flex justify-between items-start pb-6 border-b border-slate-100 mb-6">
                            <div class="space-y-1">
                                <h4 class="text-base font-bold text-slate-800">Horizon Ventures Ltd.</h4>
                                <p class="text-[10px] text-slate-400">Road 12, Banani, Dhaka · billing@horizon.com</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold uppercase tracking-wider" :style="`color: ${getSelectedColorHex()}`" x-text="activeData === 'sales' ? 'Sales Invoice' : (activeData === 'billing' ? 'Billing Statement' : 'Logistics Report')"></span>
                                <p class="text-[9px] text-slate-400 mt-0.5">Date: 13/07/2026</p>
                            </div>
                        </div>

                        <!-- Sim Table -->
                        <div class="overflow-x-auto" :data-table-style="activeStyle">
                            <table class="doc-table w-full text-left text-xs border-collapse" :style="`--accent: ${getSelectedColorHex()}; --accent-fg: #ffffff; --accent-subtle: ${getSelectedColorHex()}18; --accent-muted: ${getSelectedColorHex()}30; --border: #e2e8f0; --border-2: #cbd5e1; --surface-2: #f8fafc; --surface-3: #f1f5f9; --ink: #0f0f1d; --ink-2: #334155;`">
                                <thead>
                                    <tr>
                                        <template x-for="h in getHeaders()" :key="h">
                                            <th class="p-2.5 font-bold" x-text="h"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, idx) in getRows()" :key="idx">
                                        <tr>
                                            <template x-for="key in Object.keys(row)" :key="key">
                                                <td class="p-2.5" x-text="row[key]"></td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        {{-- ══════════ FEATURE CARDS SECTION ══════════ --}}
        <section class="py-24 border-t border-slate-200/60 bg-[#fcfbfa]">
            <div class="max-w-6xl mx-auto px-6">
                <div class="max-w-xl mx-auto text-center space-y-3 mb-16">
                    <span class="font-mono text-[10px] uppercase tracking-[0.25em] text-indigo-600 font-bold">Capabilities</span>
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 font-['Plus_Jakarta_Sans']">Designed for professional workflows</h2>
                    <p class="text-xs sm:text-sm text-slate-500">Every feature is optimized to produce neat, human-grade business reports in seconds.</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Feature: Type detection -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 space-y-4 shadow-sm hover-glow transition-all duration-200">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="font-bold text-sm text-slate-800">Auto type-detection</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">Financial columns auto-align right, text headers stay left, and date inputs format cleanly.</p>
                        </div>
                    </div>

                    <!-- Feature: Column sorting -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 space-y-4 shadow-sm hover-glow transition-all duration-200">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="font-bold text-sm text-slate-800">Interactive workspace</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">Drag columns to reorder, click column headers to rename, or hide non-essential details.</p>
                        </div>
                    </div>

                    <!-- Feature: Multiple Letterheads -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 space-y-4 shadow-sm hover-glow transition-all duration-200">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="font-bold text-sm text-slate-800">10+ Letterhead presets</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">Save multiple business profiles and cycle layouts from Centered to Editorial in one click.</p>
                        </div>
                    </div>

                    <!-- Feature: Native A4 Print -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 space-y-4 shadow-sm hover-glow transition-all duration-200">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" /></svg>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="font-bold text-sm text-slate-800">One-click browser print</h3>
                            <p class="text-xs text-slate-500 leading-relaxed">Dynamic table font auto-fitting prevents clipping. Swap Portrait/Landscape dynamically.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════ WORKFLOW STEPS ══════════ --}}
        <section id="how-it-works" class="py-24 border-t border-slate-200/60 bg-white/40">
            <div class="max-w-6xl mx-auto px-6">
                <div class="max-w-xl mx-auto text-center space-y-3 mb-16">
                    <span class="font-mono text-[10px] uppercase tracking-[0.25em] text-indigo-600 font-bold">Workflow</span>
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 font-['Plus_Jakarta_Sans']">Formatting reports in 3 steps</h2>
                    <p class="text-xs sm:text-sm text-slate-500">Transform your tabular sheet assets instantly using our local offline pipeline.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Step 1 -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-8 space-y-4 shadow-sm hover-glow transition-all duration-200 relative">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-extrabold text-sm shrink-0 shadow-md shadow-indigo-600/10">1</div>
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold">Step 01</span>
                        </div>
                        <h3 class="font-bold text-sm text-slate-800">Load Spreadsheet</h3>
                        <p class="text-xs text-slate-500 leading-relaxed">Drop or browse your file. It is read completely locally on your machine — no data is sent to external servers.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-8 space-y-4 shadow-sm hover-glow transition-all duration-200 relative">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-extrabold text-sm shrink-0 shadow-md shadow-indigo-600/10">2</div>
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold">Step 02</span>
                        </div>
                        <h3 class="font-bold text-sm text-slate-800">Design Layout</h3>
                        <p class="text-xs text-slate-500 leading-relaxed">Configure company profile headers, select ruled or boxed templates, adjust body sizes, and toggle column layouts.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-8 space-y-4 shadow-sm hover-glow transition-all duration-200 relative">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-extrabold text-sm shrink-0 shadow-md shadow-indigo-600/10">3</div>
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold">Step 03</span>
                        </div>
                        <h3 class="font-bold text-sm text-slate-800">Download PDF</h3>
                        <p class="text-xs text-slate-500 leading-relaxed">Launch the dynamic print window, review auto-fit columns, and save the report with our tailored clean file naming.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════ PRIVACY VALUE PROP ══════════ --}}
        <section class="py-24 border-t border-slate-200/60 bg-white">
            <div class="max-w-4xl mx-auto px-6 bg-slate-50 border border-slate-200/85 rounded-3xl p-10 sm:p-14 shadow-sm flex flex-col sm:flex-row gap-8 items-start relative overflow-hidden">
                <!-- Inner glow -->
                <div class="absolute bottom-0 right-0 w-64 h-64 bg-indigo-200/20 rounded-full blur-2xl pointer-events-none"></div>

                <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 shadow-sm">
                    <span class="text-3xl">🛡️</span>
                </div>
                <div class="space-y-4 relative z-10">
                    <span class="font-mono text-[10px] uppercase tracking-[0.25em] text-indigo-600 font-bold">Data Privacy Guarantee</span>
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 font-['Plus_Jakarta_Sans']">Your business financials stay in your browser</h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Typical online file converters push your sensitive spreadsheets to external databases. PaperTrail leverages pure client-side scripting to process, sort, format, and generate previews entirely on your local machine. No user registrations required, no file logs stored, and zero data leakage vectors.
                    </p>
                    <ul class="space-y-3 pt-2">
                        <li class="flex items-start gap-2.5 text-xs text-slate-600">
                            <span class="w-4 h-4 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] shrink-0 font-bold">✓</span>
                            <span>Direct local browser parsing — the server never receives file content.</span>
                        </li>
                        <li class="flex items-start gap-2.5 text-xs text-slate-600">
                            <span class="w-4 h-4 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] shrink-0 font-bold">✓</span>
                            <span>Anonymous pings only track usage event counts (files loaded / sheets parsed).</span>
                        </li>
                        <li class="flex items-start gap-2.5 text-xs text-slate-600">
                            <span class="w-4 h-4 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] shrink-0 font-bold">✓</span>
                            <span>Zero cloud storage logs — compliant with strict corporate privacy mandates.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        {{-- ══════════ FAQ ══════════ --}}
        <section id="faq" class="py-24 border-t border-slate-200/60 bg-[#fcfbfa]">
            <div class="max-w-3xl mx-auto px-6">
                <div class="text-center space-y-3 mb-14">
                    <span class="font-mono text-[10px] uppercase tracking-[0.25em] text-indigo-600 font-bold">FAQ</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 font-['Plus_Jakarta_Sans']">Frequently asked questions</h2>
                    <p class="text-xs sm:text-sm text-slate-500">Everything you need to know about turning spreadsheets into clean PDF reports.</p>
                </div>

                <div class="space-y-4">
                    <details class="group bg-white border border-slate-200/80 rounded-2xl shadow-sm open:shadow-md transition-all duration-200">
                        <summary class="flex items-center justify-between gap-4 cursor-pointer list-none p-6">
                            <h3 class="font-bold text-sm text-slate-800">How do I convert a spreadsheet to a PDF report?</h3>
                            <span class="w-6 h-6 rounded-full bg-slate-50 text-indigo-600 flex items-center justify-center text-lg shrink-0 transition-transform duration-200 group-open:rotate-45">+</span>
                        </summary>
                        <p class="px-6 pb-6 -mt-1 text-xs sm:text-sm text-slate-500 leading-relaxed">Open PaperTrail, drag in your Excel (XLSX), CSV or TSV file, adjust the styling and letterhead if needed, then use Print to save a clean, print-ready A4 PDF. Everything happens in your browser — no upload or sign-up required.</p>
                    </details>

                    <details class="group bg-white border border-slate-200/80 rounded-2xl shadow-sm open:shadow-md transition-all duration-200">
                        <summary class="flex items-center justify-between gap-4 cursor-pointer list-none p-6">
                            <h3 class="font-bold text-sm text-slate-800">Is my data uploaded to a server?</h3>
                            <span class="w-6 h-6 rounded-full bg-slate-50 text-indigo-600 flex items-center justify-center text-lg shrink-0 transition-transform duration-200 group-open:rotate-45">+</span>
                        </summary>
                        <p class="px-6 pb-6 -mt-1 text-xs sm:text-sm text-slate-500 leading-relaxed">No. PaperTrail processes your spreadsheet entirely on your device using client-side JavaScript. Your files and their contents never leave your browser and are never sent to any server.</p>
                    </details>

                    <details class="group bg-white border border-slate-200/80 rounded-2xl shadow-sm open:shadow-md transition-all duration-200">
                        <summary class="flex items-center justify-between gap-4 cursor-pointer list-none p-6">
                            <h3 class="font-bold text-sm text-slate-800">Which file formats does PaperTrail support?</h3>
                            <span class="w-6 h-6 rounded-full bg-slate-50 text-indigo-600 flex items-center justify-center text-lg shrink-0 transition-transform duration-200 group-open:rotate-45">+</span>
                        </summary>
                        <p class="px-6 pb-6 -mt-1 text-xs sm:text-sm text-slate-500 leading-relaxed">PaperTrail supports Excel .xlsx, .csv and .tsv files up to 30&nbsp;MB. Columns are automatically aligned, and currency, number and date values are formatted for professional business documents.</p>
                    </details>

                    <details class="group bg-white border border-slate-200/80 rounded-2xl shadow-sm open:shadow-md transition-all duration-200">
                        <summary class="flex items-center justify-between gap-4 cursor-pointer list-none p-6">
                            <h3 class="font-bold text-sm text-slate-800">Is PaperTrail free to use?</h3>
                            <span class="w-6 h-6 rounded-full bg-slate-50 text-indigo-600 flex items-center justify-center text-lg shrink-0 transition-transform duration-200 group-open:rotate-45">+</span>
                        </summary>
                        <p class="px-6 pb-6 -mt-1 text-xs sm:text-sm text-slate-500 leading-relaxed">Yes. PaperTrail is completely free to use, with no account, watermark or upload required.</p>
                    </details>
                </div>
            </div>
        </section>

    </main>

    {{-- ══════════ FOOTER ══════════ --}}
    <footer class="py-10 border-t border-slate-200/60 text-center text-xs text-slate-400 bg-white space-y-3">
        <p>Copyright © 2026 PaperTrail | Crafted with ⚡ by Tuhin | All rights reserved for all type of Information</p>
        <p><a href="{{ route('privacy') }}" class="underline hover:text-indigo-600 transition-colors font-mono uppercase text-[9px] tracking-wider font-bold">Privacy Policy</a></p>
    </footer>

</div>

<script>
    function landingUploader() {
        return {
            isDragging: false,

            handleDrop(event) {
                const file = event.dataTransfer.files[0];
                if (file) this.processFile(file);
            },

            handleFile(event) {
                const file = event.target.files[0];
                if (file) this.processFile(file);
            },

            processFile(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const arrayBuffer = e.target.result;
                    const binary = new Uint8Array(arrayBuffer);
                    let binaryString = '';
                    for (let i = 0; i < binary.length; i++) {
                        binaryString += String.fromCharCode(binary[i]);
                    }
                    const base64 = btoa(binaryString);

                    try {
                        sessionStorage.setItem('pt_pending_file', base64);
                        sessionStorage.setItem('pt_pending_name', file.name);
                        
                        if (typeof trackEvent === 'function') {
                            trackEvent('upload');
                        }

                        window.location.href = "{{ route('app') }}";
                    } catch (err) {
                        alert('File size is too large to process through browser session storage. Please open the clean workspace directly.');
                    }
                };
                reader.readAsArrayBuffer(file);
            },

            loadDemoData() {
                // Hardcoded base64 representation of a standard sample sales ledger CSV:
                // Invoice ID,Client,Country,Balance Due,Status,Date
                // #INV-1001,Pinnacle Group,United States,42500.00,Paid,2026-05-12
                // #INV-1002,Rahman & Sons,Bangladesh,18200.00,Pending,2026-05-14
                // #INV-1003,Vertex Global,Germany,31000.00,Overdue,2026-05-15
                // #INV-1004,Zenith Media,United Kingdom,12500.00,Paid,2026-05-18
                // #INV-1005,Beximco Pharm,Bangladesh,65000.00,Pending,2026-05-20
                const base64Csv = 'SW52b2ljZSBJRCxDbGllbnQsQ291bnRyeSxCYWxhbmNlIER1ZSxTdGF0dXMsRGF0ZQojSU5WLTEwMDEsUGlubmFjbGUgR3JvdXAsVW5pdGVkIFN0YXRlcyw0MjUwMC4wMCxQYWlkLDIwMjYtMDUtMTIKI0lOVi0xMDAyLFJhaG1hbiAmIFNvbnMsQmFuZ2xhZGVzaCwxODIwMC4wMCxQZW5kaW5nLDIwMjYtMDUtMTQKI0lOVi0xMDAzLFZlcnRleCBHbG9iYWwsR2VybWFueSwzMTAwMC4wMCxPdmVyZHVlLDIwMjYtMDUtMTUKI0lOVi0xMDA0LFplbml0aCBNZWRpYSxVbml0ZWQgS2luZ2RvbSwxMjUwMC4wMCxQYWlkLDIwMjYtMDUtMTgKI0lOVi0xMDA1LEJleGltY28gUGhhcm0sQmFuZ2xhZGVzaCw2NTAwMC4wMCxQZW5kaW5nLDIwMjYtMDUtMjA=';
                
                try {
                    sessionStorage.setItem('pt_pending_file', base64Csv);
                    sessionStorage.setItem('pt_pending_name', 'sample_sales_data.csv');
                    
                    if (typeof trackEvent === 'function') {
                        trackEvent('upload');
                    }

                    window.location.href = "{{ route('app') }}";
                } catch (err) {
                    alert('Error loading sample data. Redirecting to workspace.');
                    window.location.href = "{{ route('app') }}";
                }
            }
        }
    }

    function playgroundController() {
        return {
            activeColor: 'indigo',
            activeStyle: 'boxed',
            activeData: 'sales',

            colors: [
                { id: 'indigo', label: 'Indigo Accent', hex: '#6366f1' },
                { id: 'emerald', label: 'Emerald Accent', hex: '#10b981' },
                { id: 'sunset', label: 'Sunset Accent', hex: '#f97316' },
                { id: 'oxblood', label: 'Oxblood Accent', hex: '#991b1b' }
            ],

            tableStyles: [
                { id: 'clean', label: 'Clean Style' },
                { id: 'ruled', label: 'Ruled Style' },
                { id: 'boxed', label: 'Boxed Style' },
                { id: 'shaded-header', label: 'Shaded Header' }
            ],

            getSelectedColorHex() {
                const c = this.colors.find(col => col.id === this.activeColor);
                return c ? c.hex : '#6366f1';
            },

            getHeaders() {
                if (this.activeData === 'sales') {
                    return ['Invoice ID', 'Client Name', 'Balance Due'];
                }
                if (this.activeData === 'billing') {
                    return ['Service Period', 'Contract Account', 'Total Amount'];
                }
                return ['Asset Serial', 'Item Category', 'Stock Level'];
            },

            getRows() {
                if (this.activeData === 'sales') {
                    return [
                        { id: '#INV-0042', client: 'Nova Trading', amount: '৳ 24,500.00' },
                        { id: '#INV-0043', client: 'Rahman Brothers', amount: '৳ 18,300.00' },
                        { id: '#INV-0044', client: 'Beximco Corp', amount: '৳ 45,000.00' }
                    ];
                }
                if (this.activeData === 'billing') {
                    return [
                        { id: 'May 2026', client: 'Corporate Cloud VPS', amount: '$ 450.00' },
                        { id: 'Jun 2026', client: 'Enterprise Firewalls', amount: '$ 1,200.00' },
                        { id: 'Jul 2026', client: 'Dedicated Network Fiber', amount: '$ 800.00' }
                    ];
                }
                return [
                    { id: 'SN-84920', client: 'High-density Rack Server', amount: '12 Units' },
                    { id: 'SN-32049', client: 'Gigabit Switch Chassis', amount: '8 Units' },
                    { id: 'SN-10294', client: 'Backup NAS Array', amount: '4 Units' }
                ];
            }
        }
    }
</script>
@endsection
