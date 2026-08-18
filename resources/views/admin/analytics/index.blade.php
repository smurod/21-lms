@extends('admin.layouts.app')

@section('title', 'AI Analytics')

@section('content')
    @php
        $serviceUnavailable = $serviceHealth['unavailable'] ?? false;
        $llmOnline = (bool) ($serviceHealth['llm_server'] ?? false);
        $dataLensOnline = (bool) ($serviceHealth['datalens'] ?? false);
        $jobIsRunning = $activeJob && in_array($activeJob['status'] ?? '', ['queued', 'running'], true);
        $jobIsGenerate = $jobIsRunning && ($activeJob['operation'] ?? '') === 'generate';
        $hasDashboard = $selectedDashboard
            && $selectedDashboard->status === 'ready'
            && ! $jobIsGenerate;
        $dashboardHeight = $selectedDashboard
            ? max(720, 260 + count($selectedDashboard->charts ?? []) * 540)
            : 720;
        $promptSuggestions = $dashboards->pluck('prompt')->filter()->unique()->values();
        $conversationMessages = $selectedDashboard
            ? $selectedDashboard->messages->map(fn ($message) => ['role' => $message->role, 'content' => $message->content])->all()
            : [];
        // First persisted conversation message moves chat to the right quarter
        // even before DataLens widgets exist. A clean workspace stays full.
        $showConversationPanel = ! empty($conversationMessages);
        $isInitialConversation = $selectedDashboard
            && $selectedDashboard->status === 'conversation';
    @endphp

    <div class="space-y-6 anim-up" style="animation-delay: .1s"
         @if ($showConversationPanel) x-init="analyticsChatOpen = true; @if ($jobIsRunning) analyticsLoading = true; @endif" :style="analyticsChatOpen ? 'margin-right: min(25vw, 430px)' : ''" @endif>
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-mono font-semibold uppercase tracking-[.18em] text-cyan-300">
                    <span class="h-1.5 w-1.5 rounded-full {{ $serviceUnavailable ? 'bg-rose-400' : 'bg-cyan-400 shadow-[0_0_8px_rgba(34,211,238,.9)]' }}"></span>
                    DataLens AI Agent
                </div>
                <h1 class="text-3xl font-semibold tracking-tighter text-white">AI Analytics</h1>
                <p class="mt-1 text-sm font-medium text-zinc-500">Создавайте и изменяйте аналитические dashboards простыми запросами.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-2xl border px-3 py-2 text-xs font-semibold {{ $llmOnline ? 'border-violet-400/20 bg-violet-400/10 text-violet-300' : 'border-rose-400/20 bg-rose-400/10 text-rose-300' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $llmOnline ? 'bg-violet-300' : 'bg-rose-300' }}"></span>
                    OpenAI {{ $llmOnline ? 'online' : 'offline' }}
                </span>
                <span class="inline-flex items-center gap-2 rounded-2xl border px-3 py-2 text-xs font-semibold {{ $dataLensOnline ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300' : 'border-rose-400/20 bg-rose-400/10 text-rose-300' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $dataLensOnline ? 'bg-emerald-300' : 'bg-rose-300' }}"></span>
                    DataLens {{ $dataLensOnline ? 'online' : 'offline' }}
                </span>
            </div>
        </div>

        @if (session('success'))
            <div class="flex items-start gap-3 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-5 py-4 text-sm text-emerald-200">
                <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-start gap-3 rounded-2xl border border-rose-400/20 bg-rose-400/10 px-5 py-4 text-sm text-rose-200">
                <i data-lucide="circle-alert" class="mt-0.5 h-5 w-5 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($serviceUnavailable)
            <div class="rounded-2xl border border-amber-400/20 bg-amber-400/10 px-5 py-4 text-sm text-amber-100">
                AI service недоступен. Запустите datalens-ai на адресе из <code class="rounded bg-black/20 px-1.5 py-0.5 font-mono text-xs">DATALENS_AI_URL</code>.
            </div>
        @endif

        @if ($hasDashboard)
            <section class="relative min-h-[calc(100vh-255px)] overflow-hidden rounded-3xl border border-white/5 bg-zinc-900 shadow-xl shadow-black/20">
                <div class="relative border-b border-white/5 px-6 py-5 text-center">
                    <div class="mx-auto max-w-xl">
                        <div class="mb-2 flex items-center justify-center gap-2 text-[11px] font-mono font-semibold uppercase tracking-widest text-cyan-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-cyan-300"></span>
                            DataLens Dashboard
                        </div>
                        <h2 class="truncate text-xl font-semibold text-white">{{ $selectedDashboard->title }}</h2>
                        <p class="mt-1 text-sm text-zinc-500">Интерактивная аналитика, созданная AI-агентом по данным LMS.</p>
                    </div>
                    <a href="{{ route('admin.analytics.index', ['reset' => 1]) }}"
                       class="absolute right-6 top-1/2 inline-flex -translate-y-1/2 items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-zinc-200 transition-colors hover:bg-white/10 hover:text-white">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        Сбросить
                    </a>
                </div>

                <div class="relative min-h-[720px] bg-zinc-950" style="height: {{ $dashboardHeight }}px">
                    <iframe src="{{ $previewUrl }}" title="{{ $selectedDashboard->title }}"
                            class="h-full w-full border-0 bg-white" loading="lazy"></iframe>
                </div>

                @if ($jobIsRunning)
                    @include('admin.analytics.components.progress-overlay')
                @endif
            </section>

            @if (! $jobIsRunning && ! $showConversationPanel)
                <form method="POST" action="{{ route('admin.analytics.live.edit', $selectedDashboard) }}" target="analytics-job-launcher"
                      @submit="analyticsLoading = true"
                      class="fixed bottom-6 left-1/2 z-30 w-[min(760px,calc(100vw-8rem))] -translate-x-1/2">
                    @csrf
                    <div class="flex items-center gap-2 rounded-[24px] border border-white/15 bg-zinc-950/95 px-3 py-2 shadow-2xl shadow-black/60 backdrop-blur focus-within:border-violet-400/50 focus-within:ring-1 focus-within:ring-violet-400/30">
                        <input type="text" name="message" required maxlength="2000" value="{{ old('message') }}" list="analytics-prompt-suggestions" autocomplete="off"
                               placeholder="Попросите AI изменить dashboard…"
                               class="analytics-prompt-input min-w-0 flex-1 bg-transparent px-3 py-2 text-sm text-white outline-none placeholder:text-zinc-600">
                        <button type="submit" {{ $serviceUnavailable ? 'disabled' : '' }}
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-400 text-zinc-950 transition-colors hover:bg-violet-300 disabled:cursor-not-allowed disabled:opacity-40">
                            <i data-lucide="arrow-up" class="h-4 w-4"></i>
                        </button>
                    </div>
                    @error('message')
                    <p class="mt-2 text-center text-xs font-medium text-rose-400">{{ $message }}</p>
                    @enderror
                </form>
            @endif
        @else
            @if ($isInitialConversation)
                <section class="relative min-h-[calc(100vh-270px)] overflow-hidden rounded-3xl border border-white/5 bg-zinc-900/50 shadow-2xl shadow-black/20">
                    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(at_50%_50%,rgba(34,211,238,.08),transparent_48%)]"></div>
                    <div class="relative flex h-full min-h-[calc(100vh-270px)] items-center justify-center px-8 text-center">
                        <div>
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-3xl bg-cyan-400/10">
                                <i data-lucide="message-square-text" class="h-7 w-7 text-cyan-300"></i>
                            </div>
                            <h2 class="mt-5 text-2xl font-semibold text-white">Новый dashboard</h2>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-zinc-500">Диалог начат. Продолжайте разговор в AI chat справа: агент ответит текстом или сам запустит создание dashboard, когда запрос потребует анализа.</p>
                        </div>
                    </div>
                </section>
            @else
                <div class="relative flex min-h-[calc(100vh-270px)] flex-col justify-end overflow-hidden rounded-3xl border border-white/5 bg-zinc-900/50 px-6 pb-10 pt-16 shadow-2xl shadow-black/20 sm:px-10">
                    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(at_50%_75%,rgba(34,211,238,.12),transparent_45%)]"></div>
                    <div class="mx-auto w-full max-w-3xl">
                        <div class="absolute left-1/2 top-[35%] w-full max-w-xl -translate-x-1/2 text-center">
                            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-3xl bg-cyan-400/10">
                                <i data-lucide="sparkles" class="h-7 w-7 text-cyan-300"></i>
                            </div>
                            <h2 class="text-3xl font-semibold tracking-tight text-white">Что хотите проанализировать?</h2>
                            <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-zinc-500">Опишите нужные метрики, события или пользователей. AI подготовит SQL, проверит его и соберёт dashboard.</p>
                        </div>

                        <div x-show="chatThinking" x-cloak x-transition.opacity class="mb-4 flex items-center gap-3 rounded-2xl border border-cyan-400/15 bg-cyan-400/5 px-4 py-3 text-sm text-cyan-100">
                            <span class="flex h-6 w-6 items-center justify-center"><span class="h-2.5 w-2.5 animate-pulse rounded-full bg-cyan-300"></span></span>
                            <span>AI думает…</span>
                        </div>

                        <form method="POST" action="{{ route('admin.analytics.agent') }}" target="analytics-job-launcher" @submit="chatSubmittedMessage = chatDraft; chatDraft = ''; chatThinking = true" class="flex items-center gap-2 rounded-[24px] border border-white/10 bg-zinc-950/95 px-3 py-2 shadow-2xl shadow-black/40 backdrop-blur focus-within:border-cyan-400/50 focus-within:ring-1 focus-within:ring-cyan-400/30">
                            @csrf
                            <input type="hidden" name="message" :value="chatSubmittedMessage">
                            <input id="generate-message" type="text" x-model="chatDraft" required maxlength="2000" list="analytics-prompt-suggestions" autocomplete="off"
                                   placeholder="Например: Покажи активность пользователей, динамику XP и проекты…"
                                   class="analytics-prompt-input min-w-0 flex-1 bg-transparent px-3 py-2 text-[15px] text-white outline-none placeholder:text-zinc-600">
                            <button type="submit" {{ $serviceUnavailable ? 'disabled' : '' }}
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white text-zinc-950 transition-all hover:bg-cyan-200 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40">
                                <i data-lucide="arrow-up" class="h-5 w-5"></i>
                            </button>
                        </form>
                        @error('message')
                        <p class="mt-3 text-center text-xs font-medium text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($jobIsRunning)
                        @include('admin.analytics.components.progress-overlay')
                    @endif
                </div>
            @endif
        @endif
    </div>

    @if ($showConversationPanel)
        @include('admin.analytics.components.conversation-panel')
    @endif

    <iframe name="analytics-job-launcher" class="hidden" title="AI job launcher"></iframe>

    @if (! $showConversationPanel)
        <div x-show="analyticsLoading" x-cloak x-transition.opacity
             class="fixed inset-0 z-[60] flex items-center justify-center bg-[#080b12]/55 px-6 py-8 backdrop-blur-sm">
            <div class="w-full max-w-4xl overflow-hidden rounded-3xl border border-cyan-400/25 bg-zinc-900/95 shadow-2xl shadow-black/60">
                <div class="flex items-center gap-4 bg-[radial-gradient(at_15%_0%,rgba(34,211,238,.16),transparent_55%)] px-7 py-7">
                    <div class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-cyan-400/15">
                        <span class="absolute inset-0 animate-ping rounded-2xl bg-cyan-400/20"></span>
                        <i data-lucide="bot" class="relative h-6 w-6 text-cyan-200"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-mono font-semibold uppercase tracking-[.18em] text-cyan-300">AI Agent работает</p>
                        <h2 data-progress-message class="mt-1 text-xl font-semibold text-white">Передаём запрос на анализ…</h2>
                        <p class="mt-2 text-sm text-zinc-400">Dashboard остаётся на заднем плане.</p>
                    </div>
                </div>
                <div class="grid gap-1 p-6 sm:grid-cols-2">
                    @foreach ([
                        1 => 'Подключение к DataLens', 2 => 'Рабочая тетрадь AI Generated',
                        3 => 'Подключение к базе данных', 4 => 'Анализ структуры базы',
                        5 => 'AI генерирует и проверяет SQL', 6 => 'Создание QL-чартов',
                        7 => 'Сборка сетки dashboard', 8 => 'Публикация в DataLens', 9 => 'Dashboard готов'
                    ] as $step => $title)
                        <div data-progress-row="{{ $step }}" class="flex items-center gap-3 rounded-2xl px-4 py-3">
                            <span data-progress-indicator="{{ $step }}" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/10 bg-zinc-950 text-sm font-bold text-zinc-600">·</span>
                            <p data-progress-title="{{ $step }}" class="text-sm font-semibold text-zinc-600">{{ $title }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <datalist id="analytics-prompt-suggestions">
        @foreach ($promptSuggestions as $suggestion)
            <option value="{{ $suggestion }}"></option>
        @endforeach
    </datalist>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // The main Laravel container can retain its previous scroll position
            // after the final job redirect. Reset only this visual container so
            // the dashboard header and the first DataLens section are visible.
            const mainScrollArea = document.getElementById('main-scroll-area');
            const resetAnalyticsScroll = function () {
                if (mainScrollArea) mainScrollArea.scrollTop = 0;
                window.scrollTo(0, 0);
            };
            // The browser may restore a nested scroll position after DOMContentLoaded.
            // Repeat only this visual reset after that restoration has completed.
            if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
            resetAnalyticsScroll();
            requestAnimationFrame(resetAnalyticsScroll);
            window.addEventListener('load', resetAnalyticsScroll, { once: true });
            window.setTimeout(resetAnalyticsScroll, 0);

            let stream = null;

            function scrollConversationToBottom() {
                const history = document.getElementById('analytics-conversation-history');
                if (!history) return;
                history.scrollTop = history.scrollHeight;
            }

            function appendConversationMessage(role, content) {
                const history = document.getElementById('analytics-conversation-history');
                if (!history || !content) return;
                const row = document.createElement('div');
                row.className = 'flex ' + (role === 'user' ? 'justify-end' : 'justify-start');
                const bubble = document.createElement('div');
                bubble.className = 'max-w-[92%] rounded-2xl px-4 py-3 text-sm leading-6 ' +
                    (role === 'user'
                        ? 'rounded-br-md bg-violet-400 text-zinc-950'
                        : 'rounded-bl-md border border-white/10 bg-white/5 text-zinc-200');
                const label = document.createElement('p');
                label.className = 'mb-1 text-[10px] font-mono font-semibold uppercase tracking-wide ' +
                    (role === 'user' ? 'text-zinc-800/75' : 'text-cyan-300');
                label.textContent = role === 'user' ? 'Вы' : 'AI агент';
                const text = document.createElement('p');
                text.className = 'whitespace-pre-wrap break-words';
                text.textContent = content;
                bubble.append(label, text);
                row.appendChild(bubble);
                history.appendChild(row);
                requestAnimationFrame(scrollConversationToBottom);
            }

            requestAnimationFrame(() => {
                scrollConversationToBottom();
                const history = document.getElementById('analytics-conversation-history');
                if (history) {
                    new MutationObserver(scrollConversationToBottom).observe(history, { childList: true, subtree: true });
                }
            });

            function updateProgress(job) {
                const step = Number(job.step || 0);
                document.querySelectorAll('[data-progress-row]').forEach(function (row) {
                    const current = Number(row.dataset.progressRow);
                    const done = current < step;
                    const active = current === step;
                    const indicator = row.querySelector('[data-progress-indicator]');
                    const title = row.querySelector('[data-progress-title]');
                    row.classList.toggle('bg-cyan-400/10', active);
                    if (title) {
                        title.className = 'text-sm font-semibold ' + (active ? 'text-white' : (done ? 'text-zinc-300' : 'text-zinc-600'));
                    }
                    if (indicator) {
                        indicator.textContent = done ? '✓' : (active ? '◌' : '·');
                        indicator.className = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-sm font-bold ' +
                            (done ? 'border-cyan-300/30 bg-cyan-300 text-zinc-950' : (active ? 'border-cyan-300/50 bg-cyan-300/20 text-cyan-200 animate-pulse' : 'border-white/10 bg-zinc-950 text-zinc-600'));
                    }
                });
                const message = document.querySelector('[data-progress-message]');
                if (message && job.message) message.textContent = job.message;
            }

            function startStream(streamUrl, completeUrl) {
                if (window.Alpine) {
                    const appState = Alpine.$data(document.body);
                    if (appState.chatPendingMessage) {
                        appendConversationMessage('user', appState.chatPendingMessage);
                    }
                    appState.initialAgentLoading = false;
                    appState.chatThinking = false;
                    appState.chatPendingMessage = '';
                    appState.analyticsLoading = true;
                }
                if (stream) stream.close();
                stream = new EventSource(streamUrl);
                stream.addEventListener('progress', function (event) {
                    updateProgress(JSON.parse(event.data));
                });
                stream.addEventListener('complete', function (event) {
                    updateProgress(JSON.parse(event.data));
                    stream.close();
                    // One final server navigation persists the completed dashboard in Laravel.
                    window.location.assign(completeUrl);
                });
                stream.addEventListener('failed', function (event) {
                    updateProgress(JSON.parse(event.data));
                    stream.close();
                    window.location.assign(completeUrl);
                });
            }

            window.addEventListener('message', function (event) {
                if (event.origin !== window.location.origin) return;
                if (event.data?.type === 'analytics-job-start') {
                    startStream(event.data.streamUrl, event.data.completeUrl);
                    return;
                }
                if (event.data?.type === 'analytics-chat-response') {
                    if (event.data.initialConversation && event.data.completeUrl) {
                        window.location.assign(event.data.completeUrl);
                        return;
                    }
                    if (window.Alpine) {
                        const appState = Alpine.$data(document.body);
                        appState.chatThinking = false;
                        appState.initialAgentLoading = false;
                        appState.chatPendingMessage = '';
                    }
                    appendConversationMessage('user', event.data.userMessage);
                    appendConversationMessage('assistant', event.data.assistantMessage);
                }
            });

            @if (!empty($jobStreamUrl ?? null))
            startStream(@json($jobStreamUrl), @json(request()->fullUrl()));
            @endif
        });
    </script>
@endsection
