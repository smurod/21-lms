<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', '21-LMS Dashboard')</title>
    <link rel="stylesheet" href="{{asset('assets/css/style.css')}}" />
</head>
<body>

<div class="@yield('name') app-shell">
    <!-- Header / Navigation -->
    @include('public.layouts.header')

    <div class="app-main">
        @yield('content')
    </div>

    <!-- Footer -->
    @include('public.layouts.footer')
</div>

<script src="{{asset('assets/js/app.js')}}"></script>
@stack('scripts')
</body>
</html>
