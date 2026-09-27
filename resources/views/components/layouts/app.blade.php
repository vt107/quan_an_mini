<!DOCTYPE html>
<html lang="vi" class="h-full scroll-smooth">
<head>
    @include('partials.head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-full bg-stone-50 text-stone-900 antialiased">
    {{ $slot }}
</body>
</html>
