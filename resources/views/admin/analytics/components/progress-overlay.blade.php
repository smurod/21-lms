@php
    $progressSteps = [
        1 => ['icon' => 'log-in', 'title' => 'Подключение к DataLens'],
        2 => ['icon' => 'folder-search-2', 'title' => 'Рабочая тетрадь AI Generated'],
        3 => ['icon' => 'database-zap', 'title' => 'Подключение к базе данных'],
        4 => ['icon' => 'scan-search', 'title' => 'Анализ структуры базы'],
        5 => ['icon' => 'brain-circuit', 'title' => 'AI генерирует и проверяет SQL'],
        6 => ['icon' => 'chart-no-axes-combined', 'title' => 'Создание QL-чартов'],
        7 => ['icon' => 'layout-dashboard', 'title' => 'Сборка сетки dashboard'],
        8 => ['icon' => 'send', 'title' => 'Публикация в DataLens'],
        9 => ['icon' => 'check-circle-2', 'title' => 'Dashboard готов'],
    ];
    $currentStep = (int) ($activeJob['step'] ?? 0);
@endphp

<div class="fixed inset-0 z-50 flex items-center justify-center bg-[#080b12]/55 px-6 py-8 backdrop-blur-sm">
    <div class="w-full max-w-4xl overflow-hidden rounded-3xl border border-cyan-400/25 bg-zinc-900/90 shadow-2xl shadow-black/50">
        <div class="border-b border-white/5 bg-[radial-gradient(at_15%_0%,rgba(34,211,238,.16),transparent_55%)] px-7 py-7">
            <div class="flex items-start gap-4">
                <div class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-cyan-400/15">
                    <span class="absolute inset-0 animate-ping rounded-2xl bg-cyan-400/20"></span>
                    <i data-lucide="bot" class="relative h-6 w-6 text-cyan-200"></i>
                </div>
                <div>
                    <p class="text-[11px] font-mono font-semibold uppercase tracking-[.18em] text-cyan-300">AI Agent работает</p>
                    <h2 data-progress-message class="mt-1 text-xl font-semibold text-white">{{ $activeJob['message'] ?? 'Подготавливаем dashboard…' }}</h2>
                    <p class="mt-2 text-sm text-zinc-400">Этапы обновляются автоматически, dashboard остаётся на заднем плане.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-1 p-6 sm:grid-cols-2">
            @foreach ($progressSteps as $step => $item)
                @php
                    $isDone = $step < $currentStep;
                    $isActive = $step === $currentStep;
                @endphp
                <div data-progress-row="{{ $step }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 {{ $isActive ? 'bg-cyan-400/10' : '' }}">
                    <span data-progress-indicator="{{ $step }}" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border {{ $isDone ? 'border-cyan-300/30 bg-cyan-300 text-zinc-950' : ($isActive ? 'border-cyan-300/50 bg-cyan-300/20 text-cyan-200' : 'border-white/10 bg-zinc-950 text-zinc-600') }}">
                        @if ($isDone)
                            <i data-lucide="check" class="h-4 w-4"></i>
                        @elseif ($isActive)
                            <i data-lucide="loader-circle" class="h-4 w-4 animate-spin"></i>
                        @else
                            <i data-lucide="{{ $item['icon'] }}" class="h-3.5 w-3.5"></i>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p data-progress-title="{{ $step }}" class="text-sm font-semibold {{ $isActive ? 'text-white' : ($isDone ? 'text-zinc-300' : 'text-zinc-600') }}">{{ $item['title'] }}</p>
                        @if ($isActive)
                            <p class="mt-0.5 truncate text-xs text-cyan-200/80">{{ $activeJob['message'] ?? '' }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border-t border-white/5 px-6 py-4 text-center">
            <a data-progress-complete-link href="{{ request()->fullUrl() }}"
               class="hidden inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-zinc-950 transition-colors hover:bg-cyan-200">
                Показать результат
            </a>
        </div>
    </div>
</div>
