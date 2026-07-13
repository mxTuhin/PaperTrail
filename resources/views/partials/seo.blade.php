@php
    $baseUrl = rtrim(config('app.url'), '/');
    $defaultDescription = 'Turn any spreadsheet into a clean, print-ready A4 PDF report. PaperTrail formats Excel, CSV and TSV files with smart column alignment, currency formatting and custom letterheads — entirely in your browser. Free, fast and private: your files never leave your device.';
    $description = trim($__env->yieldContent('meta_description', $defaultDescription));
    $canonical = trim($__env->yieldContent('canonical', url()->current()));
    $robots = trim($__env->yieldContent('robots', 'index, follow, max-image-preview:large, max-snippet:-1'));
    $ogType = trim($__env->yieldContent('og_type', 'website'));
    $ogTitle = trim($__env->yieldContent('og_title', trim($__env->yieldContent('title', 'PaperTrail — Format Spreadsheets into Clean PDF Reports'))));
    $ogImage = $baseUrl.'/og-image.svg';
@endphp

    {{-- Primary meta --}}
    <meta name="description" content="{{ $description }}">
    <meta name="keywords" content="spreadsheet to PDF, Excel to PDF, CSV to PDF, xlsx to PDF report, print-ready A4 report, invoice formatter, financial report generator, letterhead PDF, browser spreadsheet formatter, private client-side converter">
    <meta name="author" content="PaperTrail">
    <meta name="publisher" content="PaperTrail">
    <meta name="robots" content="{{ $robots }}">
    <meta name="theme-color" content="#3b3c95">
    <meta name="color-scheme" content="light">
    <meta name="application-name" content="PaperTrail">
    <link rel="canonical" href="{{ $canonical }}">

    {{-- Open Graph --}}
    <meta property="og:site_name" content="PaperTrail">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="PaperTrail — format spreadsheets into clean, print-ready PDF reports">
    <meta property="og:locale" content="en_US">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="PaperTrail — format spreadsheets into clean, print-ready PDF reports">

    {{-- Icons --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ $baseUrl }}/og-image.svg">

    {{-- Site-wide structured data (Organization + WebSite + WebApplication) --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => $baseUrl.'/#organization',
                'name' => 'PaperTrail',
                'url' => $baseUrl,
                'logo' => $baseUrl.'/og-image.svg',
                'description' => 'PaperTrail is a free, browser-based tool that turns spreadsheets into clean, print-ready PDF business reports without uploading any data.',
            ],
            [
                '@type' => 'WebSite',
                '@id' => $baseUrl.'/#website',
                'name' => 'PaperTrail',
                'url' => $baseUrl,
                'publisher' => ['@id' => $baseUrl.'/#organization'],
                'inLanguage' => 'en',
            ],
            [
                '@type' => ['WebApplication', 'SoftwareApplication'],
                '@id' => $baseUrl.'/#app',
                'name' => 'PaperTrail',
                'url' => $baseUrl.'/app',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Any (web browser)',
                'browserRequirements' => 'Requires a modern web browser with JavaScript enabled.',
                'inLanguage' => 'en',
                'description' => 'Convert Excel (XLSX), CSV and TSV spreadsheets into elegant, print-ready A4 PDF reports. Automatic column alignment, currency and date formatting, custom letterheads and an auto-dashboard — all processed locally in the browser so your data never leaves your device.',
                'featureList' => [
                    'Convert XLSX, CSV and TSV spreadsheets to print-ready A4 PDF',
                    'Automatic column alignment and currency, number and date formatting',
                    'Custom company letterhead and document header profiles',
                    'Drag-to-reorder, rename and hide columns',
                    'Auto-generated summary dashboard with KPIs, charts and month grouping',
                    '100% client-side processing — files never leave your browser',
                ],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                ],
                'publisher' => ['@id' => $baseUrl.'/#organization'],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @stack('structured-data')
