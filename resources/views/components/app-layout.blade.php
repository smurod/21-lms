<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Lumina')</title>
        @vite(['resources/css/app.css'])
        @stack('styles')
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('components.navigation-menu')
            @hasSection('full-content')
                @yield('full-content')
            @else
                @yield('content')
            @endif
        </div>
        @stack('scripts')
    </body>
</html>
