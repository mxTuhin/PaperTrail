<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'PaperTrail — Print')</title>

    @vite(['resources/css/app.css'])

    @stack('head')
</head>
<body class="print-body">
    @yield('content')
</body>
</html>
