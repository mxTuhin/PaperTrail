@extends('layouts.app')

@section('title', 'Privacy Policy — PaperTrail')
@section('meta_description', 'PaperTrail processes your spreadsheets entirely in your browser. Read how we handle data: no file uploads, no storage of your document contents, and only anonymous usage counts.')

@push('head')
    <style>
        .privacy-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
    </style>
@endpush

@section('content')
<div class="min-h-screen bg-[#faf9f6] text-[#141428] flex flex-col font-sans selection:bg-[#3b3c95]/10 selection:text-[#3b3c95]">

    {{-- ══════════ PREMIUM MINIMALIST NAVIGATION ══════════ --}}
    <header class="w-full bg-[#faf9f6]/90 backdrop-blur-md sticky top-0 z-50">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-6 border-b border-[#dde1ee]/40">
            <a href="{{ route('home') }}" class="group flex items-center gap-2">
                <span class="font-mono text-xs uppercase tracking-[0.25em] font-semibold text-[#141428] group-hover:text-[#3b3c95] transition-colors duration-200">
                    PAPER<span class="text-[#3b3c95] font-black group-hover:text-[#141428] transition-colors duration-200">/</span>TRAIL
                </span>
            </a>
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="text-[11px] font-mono uppercase tracking-widest text-[#141428] hover:text-[#3b3c95] transition-colors duration-150">
                    ← Back Home
                </a>
            </div>
        </div>
    </header>

    {{-- ══════════ PRIVACY POLICY BODY ══════════ --}}
    <main class="flex-1 w-full max-w-3xl mx-auto px-6 py-16 space-y-10">
        <div class="space-y-3 text-center sm:text-left">
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-[#141428]">Privacy Policy</h1>
            <p class="text-xs font-mono uppercase tracking-wider text-[#8a8ab0]">Last Updated: July 2026</p>
        </div>

        <div class="privacy-card p-8 sm:p-10 space-y-8">
            <section class="space-y-3">
                <h2 class="text-base font-bold tracking-tight text-[#141428] uppercase font-mono text-xs tracking-wider">1. The Client-Side Guarantee</h2>
                <p class="text-xs sm:text-sm text-[#454570] leading-relaxed">
                    PaperTrail is designed around a <strong>thick-client, browser-first architecture</strong>. When you upload or drag spreadsheets (.xlsx, .xls, .csv, .tsv) into this application, all data parsing, format type checks, alignment logic, and print previews are performed <strong>entirely on your own computer</strong> using client-side JavaScript. 
                </p>
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-xs font-semibold leading-relaxed">
                    🛡️ We do not store, upload, copy, or transmit your spreadsheet data, file records, column labels, or company letterhead credentials. Your files never touch our servers.
                </div>
            </section>

            <section class="space-y-3 border-t border-slate-100 pt-6">
                <h2 class="text-base font-bold tracking-tight text-[#141428] uppercase font-mono text-xs tracking-wider">2. What We Collect</h2>
                <p class="text-xs sm:text-sm text-[#454570] leading-relaxed">
                    Because this is a business formatting tool, we only collect minimal, non-identifiable usage statistics to evaluate utility and performance. The parameters captured are:
                </p>
                <ul class="list-disc pl-5 text-xs sm:text-sm text-[#454570] space-y-2 leading-relaxed">
                    <li><strong>Usage Telemetry:</strong> An anonymous counter logs when a file is dropped (upload event), parsed (process event), or downloaded/saved (print event) along with row and column limits (e.g. "processed a sheet with 150 rows and 8 columns").</li>
                    <li><strong>Network & Diagnostic Metadata:</strong> We track visitor IP addresses and User Agent details for system health auditing, server performance tuning, and security purposes.</li>
                </ul>
            </section>

            <section class="space-y-3 border-t border-slate-100 pt-6">
                <h2 class="text-base font-bold tracking-tight text-[#141428] uppercase font-mono text-xs tracking-wider">3. Google Analytics</h2>
                <p class="text-xs sm:text-sm text-[#454570] leading-relaxed">
                    We use <strong>Google Analytics</strong>, a web analytics service provided by Google LLC, to understand aggregate, anonymous traffic patterns — such as page views, approximate region, browser type, and referral sources. This helps us improve the tool's usability and performance. Google Analytics may set its own cookies or identifiers in your browser and process the data described above on Google's servers. Importantly, <strong>your spreadsheet files and their contents are never shared with Google Analytics or any third party</strong> — analytics only measures how the site itself is used, not the documents you format. You can opt out using Google's <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener noreferrer" class="text-[#3b3c95] hover:underline font-semibold">browser opt-out add-on</a>, and Google's handling of this data is governed by the <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" class="text-[#3b3c95] hover:underline font-semibold">Google Privacy Policy</a>.
                </p>
            </section>

            <section class="space-y-3 border-t border-slate-100 pt-6">
                <h2 class="text-base font-bold tracking-tight text-[#141428] uppercase font-mono text-xs tracking-wider">4. Local Storage Usage</h2>
                <p class="text-xs sm:text-sm text-[#454570] leading-relaxed">
                    We do not store cookies on your machine. Instead, we use the browser's native <strong>localStorage</strong> API to save layout presets, visual theme preferences, table grid styles, and company letterhead input fields. This information remains local to your device and is never sent to our database.
                </p>
            </section>

            <section class="space-y-3 border-t border-slate-100 pt-6">
                <h2 class="text-base font-bold tracking-tight text-[#141428] uppercase font-mono text-xs tracking-wider">5. Open Source & Contact</h2>
                <p class="text-xs sm:text-sm text-[#454570] leading-relaxed">
                    PaperTrail's source code is publicly available on GitHub at <a href="https://github.com/mxTuhin/PaperTrail" target="_blank" rel="noopener noreferrer" class="text-[#3b3c95] hover:underline font-semibold">github.com/mxTuhin/PaperTrail</a>, where you can review exactly how your data is handled or open an issue. If you have questions about our client-side architecture or privacy implementations, contact us directly.
                </p>
            </section>
        </div>
    </main>

    {{-- ══════════ FOOTER ══════════ --}}
    <footer class="py-8 border-t border-[#dde1ee]/60 text-center text-xs text-[#8a8ab0]">
        <p>Copyright © 2026 PaperTrail | Crafted with ⚡ by Tuhin | All rights reserved for all type of Information</p>
    </footer>

</div>
@endsection
