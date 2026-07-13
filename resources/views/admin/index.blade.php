@extends('layouts.app')

@section('title', 'PaperTrail — Usage Dashboard')

@section('content')
<div class="min-h-screen bg-[#faf9f6] text-[#141428] font-sans">
    <div class="max-w-4xl mx-auto py-12 px-6">

        <div class="flex items-center justify-between mb-10">
            <div>
                <span class="font-mono text-[10px] uppercase tracking-[0.25em] text-[#3b3c95]/70">Admin</span>
                <h1 class="text-2xl font-bold tracking-tight text-[#141428]">Usage Dashboard</h1>
            </div>
            <a href="{{ route('home') }}" class="text-[11px] font-mono uppercase tracking-wider text-[#8a8ab0] hover:text-[#141428] transition-colors">← Home</a>
        </div>

        {{-- Totals --}}
        <div class="grid grid-cols-3 gap-4 mb-10">
            @foreach (['upload', 'process', 'print'] as $eventType)
                <div class="bg-white rounded-2xl p-6 border border-[#dde1ee] text-center shadow-sm">
                    <div class="text-3xl font-bold text-[#3b3c95]">{{ $totals[$eventType] ?? 0 }}</div>
                    <div class="text-[10px] text-[#8a8ab0] uppercase tracking-wider mt-1 font-mono">{{ ucfirst($eventType) }}s</div>
                </div>
            @endforeach
        </div>

        {{-- Recent events --}}
        <div class="bg-white rounded-2xl border border-[#dde1ee] overflow-hidden shadow-sm">
            <div class="px-5 py-3 border-b border-[#dde1ee]">
                <h2 class="text-xs font-bold uppercase tracking-wider text-[#8a8ab0]">Recent Events</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#faf9f6] text-[#8a8ab0]">
                        <tr>
                            <th class="text-left px-5 py-2.5 font-semibold text-xs uppercase tracking-wide">Event</th>
                            <th class="text-left px-5 py-2.5 font-semibold text-xs uppercase tracking-wide">Time</th>
                            <th class="text-right px-5 py-2.5 font-semibold text-xs uppercase tracking-wide">Rows</th>
                            <th class="text-right px-5 py-2.5 font-semibold text-xs uppercase tracking-wide">Cols</th>
                            <th class="text-left px-5 py-2.5 font-semibold text-xs uppercase tracking-wide">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recent as $event)
                            <tr class="border-t border-[#f0f1f5]">
                                <td class="px-5 py-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold font-mono uppercase
                                        {{ $event->event === 'print' ? 'bg-emerald-50 text-emerald-700' : ($event->event === 'process' ? 'bg-indigo-50 text-[#3b3c95]' : 'bg-amber-50 text-amber-700') }}">
                                        {{ $event->event }}
                                    </span>
                                </td>
                                <td class="px-5 py-2.5 text-[#454570]">{{ $event->created_at->diffForHumans() }}</td>
                                <td class="px-5 py-2.5 text-right tabular-nums text-[#454570]">{{ $event->row_count ?? '—' }}</td>
                                <td class="px-5 py-2.5 text-right tabular-nums text-[#454570]">{{ $event->col_count ?? '—' }}</td>
                                <td class="px-5 py-2.5 text-[#8a8ab0] font-mono text-xs">{{ $event->ip_address }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-[#8a8ab0]">No usage events recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
