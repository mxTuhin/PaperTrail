<!DOCTYPE html>
<html lang="en" data-theme="indigo">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'PaperTrail')</title>

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

    <!-- Static Styles (No Build Tooling Needed) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <!-- Dynamic Theme State Initialization (Anti-FOUT) -->
    <script>
        (function () {
            const root = document.documentElement;
            root.setAttribute('data-theme', localStorage.getItem('pt-theme') || 'indigo');
            const accent = localStorage.getItem('pt-custom-accent');
            if (accent) {
                root.style.setProperty('--accent', accent);
                root.style.setProperty('--accent-subtle', accent + '18');
            }
            const orientation = localStorage.getItem('pt-orientation') || 'portrait';
            root.setAttribute('data-orientation', orientation);
            if (orientation === 'landscape') {
                const s = document.createElement('style');
                s.id = 'pt-orientation-style';
                s.textContent = '@media print { @page { size: A4 landscape; } }';
                document.head.appendChild(s);
            }
        })();
    </script>

    <!-- Alpine store -->
    @include('partials.theme-store')

    <!-- PaperTrail engine: stores + helpers (must run before Alpine boots) -->
    <script src="{{ asset('js/papertrail.js') }}"></script>

    <!-- Client-side libraries (loaded asynchronously from CDN) -->
    {{-- Self-hosted (same-origin, reliable) — avoids intermittent CDN load failures --}}
    <script defer src="{{ asset('js/vendor/xlsx.full.min.js') }}"></script>
    <script defer src="{{ asset('js/vendor/sortable.min.js') }}"></script>
    <script defer src="{{ asset('js/vendor/alpine.min.js') }}"></script>

    @stack('head')
</head>
<body class="min-h-screen antialiased bg-bg text-ink-2" x-data :data-table-style="$store.theme.tableStyle">
    @yield('content')

    @stack('scripts')
</body>
</html>
