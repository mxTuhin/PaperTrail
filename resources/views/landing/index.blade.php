@extends('layouts.app')

@section('title', 'PaperTrail — Format Spreadsheets into Clean PDF Reports')

@push('head')
    <meta name="description" content="Turn any spreadsheet into a clean, print-ready A4 PDF. Files never leave your browser. Free, fast, private.">
    <style>
        /* Premium Background Grid Pattern */
        .bg-grid {
            background-size: 24px 24px;
            background-image: 
                linear-gradient(to right, rgba(17, 17, 21, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(17, 17, 21, 0.02) 1px, transparent 1px);
        }

        /* Subtle entrance animations */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-up {
            animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Soft pulsing glow for accent cards */
        .hover-glow:hover {
            box-shadow: 0 10px 30px -10px rgba(59, 60, 149, 0.08), 0 1px 3px rgba(59, 60, 149, 0.02);
            border-color: rgba(59, 60, 149, 0.25);
        }
    </style>
@endpush

@section('content')
<div class="min-h-screen bg-[#faf9f6] text-[#141428] flex flex-col font-sans selection:bg-[#3b3c95]/10 selection:text-[#3b3c95] bg-grid" x-data="landingUploader()">

    {{-- ══════════ PREMIUM MINIMALIST NAVIGATION ══════════ --}}
    <header class="w-full bg-[#faf9f6]/90 backdrop-blur-md sticky top-0 z-50">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-6 border-b border-[#dde1ee]/40">
            <!-- Typographic Monospace Brand Label -->
            <a href="{{ route('home') }}" class="group flex items-center gap-2">
                <span class="font-mono text-xs uppercase tracking-[0.25em] font-semibold text-[#141428] group-hover:text-[#3b3c95] transition-colors duration-200">
                    PAPER<span class="text-[#3b3c95] font-black group-hover:text-[#141428] transition-colors duration-200">/</span>TRAIL
                </span>
            </a>

            <!-- Subdued, Clean Links and Action -->
            <div class="flex items-center gap-8">
                <a href="#how-it-works" class="text-[11px] font-mono uppercase tracking-wider text-[#8a8ab0] hover:text-[#141428] transition-colors duration-150 hidden sm:inline">
                    Workflow
                </a>
                <a href="#themes" class="text-[11px] font-mono uppercase tracking-wider text-[#8a8ab0] hover:text-[#141428] transition-colors duration-150 hidden sm:inline">
                    Presets
                </a>
                <a href="{{ route('app') }}" class="group inline-flex items-center gap-1 text-[11px] font-mono uppercase tracking-widest text-[#141428] hover:text-[#3b3c95] transition-colors duration-150">
                    Workspace 
                    <span class="inline-block transform group-hover:translate-x-0.5 transition-transform duration-150">→</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="flex-1">

        {{-- ══════════ HERO SECTION ══════════ --}}
        <section class="max-w-5xl mx-auto px-6 pt-16 pb-20 grid lg:grid-cols-12 gap-12 items-center animate-fade-up">
            
            <!-- Left Info Block -->
            <div class="lg:col-span-7 space-y-8 text-left">
                <div class="space-y-4">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3.5 py-1 text-xs font-semibold text-emerald-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Files processed completely locally
                    </span>
                    
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-[#141428] leading-[1.1]">
                        Clean A4 PDF reports <br>
                        <span class="text-[#3b3c95] underline decoration-[#dde1ee] underline-offset-4">from any spreadsheet.</span>
                    </h1>
                    
                    <p class="text-[#454570] text-sm sm:text-base max-w-xl leading-relaxed">
                        Drop in an Excel or CSV file. Automatically formats alignments, decimal places, and currency values. Style with clean table presets and custom company letterheads in seconds.
                    </p>
                </div>

                <!-- Trust value highlights list -->
                <ul class="grid grid-cols-2 gap-4 text-xs font-medium text-[#454570]">
                    <li class="flex items-center gap-2">
                        <span class="text-[#3b3c95]">✓</span> Auto Type-Detection
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="text-[#3b3c95]">✓</span> Drag-and-Drop Columns
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="text-[#3b3c95]">✓</span> Multi-profile Letterheads
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="text-[#3b3c95]">✓</span> Instant Local Print
                    </li>
                </ul>
            </div>

            <!-- Right Interactive Upload Box -->
            <div class="lg:col-span-5 w-full">
                <div
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="isDragging = false; handleDrop($event)"
                    :class="isDragging ? 'border-[#3b3c95] bg-[#3b3c95]/5 scale-[1.01] shadow-md' : 'border-[#dde1ee] hover:border-[#3b3c95]/30 bg-white shadow-sm'"
                    class="border border-dashed rounded-2xl p-12 text-center transition-all duration-200 cursor-pointer relative group flex flex-col justify-center min-h-[320px]"
                    @click="$refs.fileInput.click()"
                >
                    <input type="file" accept=".xlsx,.xls,.csv,.tsv" class="hidden" x-ref="fileInput" @change="handleFile($event)">
                    
                    <div class="space-y-5">
                        <!-- Icon container -->
                        <div class="w-14 h-14 rounded-2xl bg-[#3b3c95]/5 flex items-center justify-center mx-auto group-hover:scale-105 transition-transform duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-7 h-7 text-[#3b3c95]">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                            </svg>
                        </div>
                        
                        <!-- Main texts -->
                        <div class="space-y-1.5">
                            <p class="text-sm font-semibold text-[#141428]">Select or drop file to format</p>
                            <p class="text-xs text-[#8a8ab0]">Supports XLSX, XLS, CSV, or TSV formats</p>
                        </div>

                        <!-- CTA tag line -->
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#3b3c95] group-hover:underline">
                            browse local directories
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════ HOW IT WORKS SECTION ══════════ --}}
        <section id="how-it-works" class="border-t border-[#dde1ee]/60 bg-white/40 py-20">
            <div class="max-w-5xl mx-auto px-6">
                <div class="max-w-xl mx-auto text-center space-y-3 mb-16">
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-[#141428]">Formatting in three steps</h2>
                    <p class="text-xs sm:text-sm text-[#8a8ab0]">Import, adjust parameters, and print immediately.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Step 1 -->
                    <div class="bg-white border border-[#dde1ee] rounded-2xl p-8 space-y-4 shadow-sm">
                        <div class="w-10 h-10 rounded-full bg-[#3b3c95] flex items-center justify-center text-white font-bold text-sm">1</div>
                        <h3 class="font-bold text-sm text-[#141428]">Import Spreadsheet</h3>
                        <p class="text-xs text-[#8a8ab0] leading-relaxed">Drag or upload your files. They are parsed completely locally using client-side JavaScript.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="bg-white border border-[#dde1ee] rounded-2xl p-8 space-y-4 shadow-sm">
                        <div class="w-10 h-10 rounded-full bg-[#3b3c95] flex items-center justify-center text-white font-bold text-sm">2</div>
                        <h3 class="font-bold text-sm text-[#141428]">Configure & Personalize</h3>
                        <p class="text-xs text-[#8a8ab0] leading-relaxed">Rename header titles, toggle column visibilities, drag columns to reorder, and apply company info blocks.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="bg-white border border-[#dde1ee] rounded-2xl p-8 space-y-4 shadow-sm">
                        <div class="w-10 h-10 rounded-full bg-[#3b3c95] flex items-center justify-center text-white font-bold text-sm">3</div>
                        <h3 class="font-bold text-sm text-[#141428]">Save PDF</h3>
                        <p class="text-xs text-[#8a8ab0] leading-relaxed">Choose clean ruled or boxed styles, toggle layout orientations, and download your clean A4 document reports.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ══════════ DYNAMIC THEME SHOWCASE ══════════ --}}
        <section id="themes" class="py-20 border-t border-[#dde1ee]/60" x-data="{ activeTheme: 'indigo' }">
            <div class="max-w-5xl mx-auto px-6">
                
                <div class="max-w-xl mx-auto text-center space-y-3 mb-12">
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-[#141428]">Aesthetic document layout presets</h2>
                    <p class="text-xs sm:text-sm text-[#8a8ab0]">Apply clean styled themes instantly matching your business profiles. Tap below to preview.</p>
                </div>

                <!-- Theme selectors grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-3xl mx-auto mb-12">
                    <button @click="activeTheme = 'indigo'" :class="activeTheme === 'indigo' ? 'border-[#3b3c95] bg-white' : 'border-[#dde1ee] bg-white/50'" class="border p-4 rounded-xl text-center transition-all hover:bg-white flex flex-col items-center gap-2 shadow-sm">
                        <div class="w-6 h-6 rounded-full bg-[#3b3c95]"></div>
                        <span class="text-xs font-semibold">Indigo (Default)</span>
                    </button>
                    <button @click="activeTheme = 'emerald'" :class="activeTheme === 'emerald' ? 'border-[#059669] bg-white' : 'border-[#dde1ee] bg-white/50'" class="border p-4 rounded-xl text-center transition-all hover:bg-white flex flex-col items-center gap-2 shadow-sm">
                        <div class="w-6 h-6 rounded-full bg-[#059669]"></div>
                        <span class="text-xs font-semibold">Emerald Vibe</span>
                    </button>
                    <button @click="activeTheme = 'sunset'" :class="activeTheme === 'sunset' ? 'border-[#ea580c] bg-white' : 'border-[#dde1ee] bg-white/50'" class="border p-4 rounded-xl text-center transition-all hover:bg-white flex flex-col items-center gap-2 shadow-sm">
                        <div class="w-6 h-6 rounded-full bg-[#ea580c]"></div>
                        <span class="text-xs font-semibold">Sunset Warm</span>
                    </button>
                    <button @click="activeTheme = 'oxblood'" :class="activeTheme === 'oxblood' ? 'border-[#7f1d1d] bg-white' : 'border-[#dde1ee] bg-white/50'" class="border p-4 rounded-xl text-center transition-all hover:bg-white flex flex-col items-center gap-2 shadow-sm">
                        <div class="w-6 h-6 rounded-full bg-[#7f1d1d]"></div>
                        <span class="text-xs font-semibold">Classic Oxblood</span>
                    </button>
                </div>

                <!-- Simulated A4 card showing active color style -->
                <div class="max-w-2xl mx-auto bg-white border border-[#dde1ee] rounded-2xl p-10 shadow-sm relative overflow-hidden transition-all duration-300">
                    <div class="absolute top-0 left-0 right-0 h-1.5 transition-colors duration-300"
                         :class="{
                             'bg-[#3b3c95]': activeTheme === 'indigo',
                             'bg-[#059669]': activeTheme === 'emerald',
                             'bg-[#ea580c]': activeTheme === 'sunset',
                             'bg-[#7f1d1d]': activeTheme === 'oxblood',
                         }"></div>

                    <!-- Company Info Header -->
                    <div class="flex justify-between items-start pb-6 border-b border-slate-100 mb-6">
                        <div class="space-y-1">
                            <h4 class="text-base font-bold text-slate-800">Acme Trading Co.</h4>
                            <p class="text-[10px] text-slate-400">12/A Motijheel, Dhaka · statement@acme.com</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold uppercase tracking-wider"
                                  :class="{
                                      'text-[#3b3c95]': activeTheme === 'indigo',
                                      'text-[#059669]': activeTheme === 'emerald',
                                      'text-[#ea580c]': activeTheme === 'sunset',
                                      'text-[#7f1d1d]': activeTheme === 'oxblood',
                                  }">Sales Summary</span>
                            <p class="text-[9px] text-slate-400 mt-0.5">Date: 13/07/2026</p>
                        </div>
                    </div>

                    <!-- Dynamic Styled Table preview inside template -->
                    <div class="overflow-hidden border border-slate-100 rounded-lg">
                        <table class="w-full text-left text-[11px] border-collapse">
                            <thead>
                                <tr class="text-white transition-colors duration-300"
                                    :class="{
                                        'bg-[#3b3c95]': activeTheme === 'indigo',
                                        'bg-[#059669]': activeTheme === 'emerald',
                                        'bg-[#ea580c]': activeTheme === 'sunset',
                                        'bg-[#7f1d1d]': activeTheme === 'oxblood',
                                    }">
                                    <th class="p-2 font-semibold">Invoice ID</th>
                                    <th class="p-2 font-semibold">Client</th>
                                    <th class="p-2 font-semibold text-right">Balance Due</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr>
                                    <td class="p-2 font-mono text-slate-500">#INV-0042</td>
                                    <td class="p-2 font-semibold text-slate-700">Apex Industries</td>
                                    <td class="p-2 text-right font-medium text-slate-900">৳ 24,500.00</td>
                                </tr>
                                <tr class="bg-slate-50">
                                    <td class="p-2 font-mono text-slate-500">#INV-0043</td>
                                    <td class="p-2 font-semibold text-slate-700">Rahman Brothers</td>
                                    <td class="p-2 text-right font-medium text-slate-900">৳ 18,300.00</td>
                                </tr>
                                <tr>
                                    <td class="p-2 font-mono text-slate-500">#INV-0044</td>
                                    <td class="p-2 font-semibold text-slate-700">Beximco Corp</td>
                                    <td class="p-2 text-right font-medium text-slate-900">৳ 45,000.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>

        {{-- ══════════ PRIVACY DEEP DIVE SECTION ══════════ --}}
        <section class="py-20 border-t border-[#dde1ee]/60 bg-white/40">
            <div class="max-w-4xl mx-auto px-6 bg-white border border-[#dde1ee] rounded-2xl p-10 sm:p-14 shadow-sm flex flex-col sm:flex-row gap-8 items-center">
                <div class="w-16 h-16 rounded-full bg-[#3b3c95]/5 flex items-center justify-center shrink-0">
                    <span class="text-3xl">🛡️</span>
                </div>
                <div class="space-y-4">
                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-[#141428]">Absolute privacy: your files never touch our servers</h2>
                    <p class="text-xs sm:text-sm text-[#454570] leading-relaxed">
                        Traditional converters process documents in remote cloud servers. PaperTrail reads and formats files completely on your device. Zero user accounts, zero database file saves, and zero leak risk. Sensitive business financials remain private.
                    </p>
                </div>
            </div>
        </section>

    </div>

    {{-- ══════════ FOOTER ══════════ --}}
    <footer class="py-8 border-t border-[#dde1ee]/60 text-center text-xs text-[#8a8ab0]">
        <p>Built by Tuhin · &copy; 2026 · Privacy Protected</p>
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
                        alert('File size is too large to process through browser session storage. Please open the blank tool directly.');
                    }
                };
                reader.readAsArrayBuffer(file);
            }
        }
    }
</script>
@endsection
