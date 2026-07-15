<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', 'Front | LMS')</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/vendor.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/theme.min.css?v=1.0') }}">
    <link rel="preload" href="{{ asset('assets/css/theme.min-1.css') }}" data-hs-appearance="default" as="style">
    <link rel="preload" href="{{ asset('assets/css/theme-dark.min.css') }}" data-hs-appearance="dark" as="style">
    <style data-hs-appearance-onload-styles="">
        * { transition: unset !important; }
        body { opacity: 0; }
    </style>
    <script>
        window.hs_config = {
            "themeAppearance": {
                "layoutSkin": "default",
                "styles": {
                    "colors": {
                        "primary": "#377dff",
                        "transparent": "transparent",
                        "white": "#fff",
                        "dark": "132144",
                        "gray": {"100": "#f9fafc", "900": "#1e2022"}
                    },
                    "font": "Inter"
                }
            },
            "layoutBuilder": {
                "extend": {"switcherSupport": false},
                "header": {"layoutMode": "single-header", "containerMode": "container"},
                "sidebarLayout": "none"
            }
        };
    </script>
    @livewireStyles
</head>

<body class="footer-offset">
<script src="{{ asset('assets/js/hs.theme-appearance.js') }}"></script>

<div class="main">
    <div class="container min-vh-100 d-flex flex-column justify-content-center py-6">
        <div class="w-100 mx-auto" style="max-width: 28rem;">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                    {{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>

<script src="{{ asset('assets/js/vendor.min.js') }}"></script>
<script src="{{ asset('assets/js/theme.min.js') }}"></script>
<script src="{{ asset('assets/js/hs.theme-appearance-charts.js') }}"></script>
<script>
    (function() { window.onload = function () { HSBsDropdown.init() } })()
</script>
@livewireScripts
</body>
</html>
