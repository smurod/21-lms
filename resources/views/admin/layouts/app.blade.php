<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Lumina Admin Dashboard')</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.14.9/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        .analytics-prompt-input:-webkit-autofill,
        .analytics-prompt-input:-webkit-autofill:hover,
        .analytics-prompt-input:-webkit-autofill:focus {
            -webkit-text-fill-color: #ffffff;
            -webkit-box-shadow: 0 0 0 1000px #09090b inset;
            transition: background-color 9999s ease-out 0s;
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }

        /* AI «печатает»: три точки в конце стримингового ответа */
        .ai-dots { display: inline-flex; gap: 3px; align-items: center; vertical-align: middle; margin-left: 6px; }
        .ai-dots span { width: 5px; height: 5px; border-radius: 50%; background: #67e8f9; animation: aiDot 1.2s infinite; }
        .ai-dots span:nth-child(2) { animation-delay: .2s; }
        .ai-dots span:nth-child(3) { animation-delay: .4s; }
        @keyframes aiDot { 0%, 60%, 100% { opacity: .25; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }
        @media (prefers-reduced-motion: reduce) { .ai-dots span { animation: none; opacity: .55; } }

        /* Входящие анимации */
        @keyframes fmUp { 0% { opacity: 0; transform: translateY(40px); } 100% { opacity: 1; transform: translateY(0); } }
        @keyframes fmUpLg { 0% { opacity: 0; transform: translateY(60px); } 100% { opacity: 1; transform: translateY(0); } }
        @keyframes fmLeft { 0% { opacity: 0; transform: translateX(-30px); } 100% { opacity: 1; transform: translateX(0); } }

        .anim-up { animation: fmUp 0.8s cubic-bezier(0.1, 0.9, 0.2, 1) backwards; }
        .anim-up-lg { animation: fmUpLg 0.9s cubic-bezier(0.1, 0.9, 0.2, 1) backwards; }
        .anim-left { animation: fmLeft 0.8s cubic-bezier(0.1, 0.9, 0.2, 1) backwards; }
    </style>

    @yield('styles')
</head>
<body x-data="{
        sidebarOpen: {{ request()->routeIs('admin.analytics.*') ? 'false' : 'true' }},
        analyticsMenuOpen: {{ request()->routeIs('admin.analytics.*') ? 'true' : 'false' }},
        analyticsChatOpen: false,
        analyticsLoading: false,
        initialAgentLoading: false,
        chatThinking: false,
        chatDraft: '',
        chatSubmittedMessage: '',
        chatPendingMessage: '',
        chatStreaming: false
      }"
      class="bg-[#0a0c14] text-white overflow-hidden antialiased">

<!-- Фоновый градиент -->
<div class="fixed inset-0 bg-[radial-gradient(at_50%_30%,rgba(165,243,252,0.08)_0%,transparent_50%)] pointer-events-none"></div>

<div class="flex h-screen relative z-10">

    <!-- SIDEBAR -->
    @include('admin.layouts.sidebar')

    <!-- MAIN CONTENT -->
    <div class="flex-1 overflow-auto flex flex-col relative" id="main-scroll-area">

        <!-- TOP HEADER -->
        @include('admin.layouts.header')

        <!-- КОНТЕНТ ВКЛАДОК / СТРАНИЦ -->
        <div class="relative flex-1 p-10">
            @yield('content')
        </div>

        @include('admin.layouts.footer')
    </div>
</div>

@yield('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
    });
</script>
</body>
</html>
