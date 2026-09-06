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

                        <form method="POST" action="{{ route('admin.analytics.agent') }}" target="analytics-job-launcher"
                              data-prepare-url="{{ route('admin.analytics.chat.prepare') }}"
                              data-stream-base="{{ rtrim(config('datalens_ai.base_url'), '/') }}"
                              data-store-base="{{ url('admin/analytics') }}"
                              @submit.prevent="submitChatStreaming($el)" class="flex items-center gap-2 rounded-[24px] border border-white/10 bg-zinc-950/95 px-3 py-2 shadow-2xl shadow-black/40 backdrop-blur focus-within:border-cyan-400/50 focus-within:ring-1 focus-within:ring-cyan-400/30">
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

            function startStream(streamUrl, completeUrl, onProgress) {
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
                    const job = JSON.parse(event.data);
                    updateProgress(job);
                    if (onProgress) onProgress(job);
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

            // --------------------------------------------------------------
            // Streaming chat: tokens appear as the AI generates them, and the
            // send button turns into a stop button while the answer runs.
            // --------------------------------------------------------------
            let chatAbort = null;
            let streamingBubbleText = null;
            let streamingTextNode = null;
            let streamingDots = null;
            let streamingStatus = null;
            let jobCancelUrl = null;

            function fillStreamingBubble(text) {
                // Текст растёт, анимация точек остаётся в конце, пока агент пишет.
                if (streamingTextNode) streamingTextNode.textContent = text;
            }

            function finishStreamingBubble() {
                if (streamingDots) streamingDots.remove();
            }

            async function submitChatStreaming(form) {
                if (chatAbort) return; // a reply is already streaming
                const appState = window.Alpine ? Alpine.$data(document.body) : null;
                const draft = (appState?.chatDraft || form.querySelector('input[name="message"]')?.value || '').trim();
                if (!draft) return;

                const csrf = form.querySelector('input[name="_token"]')?.value || '';
                const prepareUrl = form.dataset.prepareUrl;
                const streamBase = form.dataset.streamBase;
                const storeBase = form.dataset.storeBase;

                chatAbort = new AbortController();
                if (appState) {
                    // Первый чат открывает панель справа немедленно — весь
                    // дальнейший диалог (и «AI думает», и токены) идёт в ней.
                    appState.analyticsChatOpen = true;
                    appState.chatSubmittedMessage = draft;
                    appState.chatPendingMessage = draft;
                    appState.chatDraft = '';
                    appState.chatThinking = true;
                    appState.chatStreaming = true;
                }
                streamingBubbleText = null;
                jobCancelUrl = null;

                const jsonFetch = (url, body) => fetch(url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                    body: JSON.stringify(body || {}),
                    signal: chatAbort?.signal,
                });

                try {
                    // 1) Laravel: store the user message, build the AI payload.
                    const prepare = await jsonFetch(prepareUrl, {
                        message: draft,
                        dashboard_id: form.dataset.dashboardId ? Number(form.dataset.dashboardId) : null,
                    });
                    if (!prepare.ok) throw new Error('prepare ' + prepare.status);
                    const prepared = await prepare.json();

                    // Новый чат: панели ещё нет в DOM. Один разрешённый reload
                    // на страницу беседы — стриминг продолжается уже в ней.
                    if (!document.getElementById('analytics-conversation-history')) {
                        sessionStorage.setItem('analyticsPendingTurn', JSON.stringify({
                            prepared: prepared, draft: draft, csrf: csrf,
                            streamBase: streamBase, storeBase: storeBase,
                            appendUser: false,
                        }));
                        window.location.assign('/admin/analytics?dashboard=' + prepared.dashboard_id);
                        return;
                    }

                    runStreamingTurn(prepared, draft, {streamBase: streamBase, storeBase: storeBase, csrf: csrf, appendUser: true});
                } catch (error) {
                    const aborted = error.name === 'AbortError';
                    fillStreamingBubble(aborted ? '⏹ Остановлено пользователем.' : '⚠ ' + (error.message || 'Ошибка запроса к AI.'));
                    if (!aborted) console.warn('chat streaming failed:', error);
                    if (appState) { appState.chatStreaming = false; appState.chatThinking = false; }
                }
            }

            async function runStreamingTurn(prepared, draft, urls) {
                const appState = window.Alpine ? Alpine.$data(document.body) : null;
                const streamBase = urls.streamBase;
                const storeBase = urls.storeBase;
                const csrf = urls.csrf || '';
                const dashboardId = prepared.dashboard_id;

                chatAbort = new AbortController();
                if (appState) {
                    appState.chatThinking = true;
                    appState.chatStreaming = true;
                }
                streamingBubbleText = null;
                const jsonFetch = (url, body) => fetch(url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                    body: JSON.stringify(body || {}),
                    signal: chatAbort?.signal,
                });

                try {
                    // После reload-перехода сообщение уже отрисовано сервером.
                    if (urls.appendUser !== false) {
                        appendConversationMessage('user', draft);
                    }
                    if (appState) { appState.chatPendingMessage = ''; }

                    // Python: stream the routing decision and reply tokens.
                    let tool = 'chat';
                    let reply = '';
                    const assistantRow = document.createElement('div');
                    assistantRow.className = 'flex justify-start';
                    const assistantBubble = document.createElement('div');
                    assistantBubble.className = 'max-w-[92%] rounded-2xl rounded-bl-md border border-white/10 bg-white/5 px-4 py-3 text-sm leading-6 text-zinc-200';
                    const assistantLabel = document.createElement('p');
                    assistantLabel.className = 'mb-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-cyan-300';
                    assistantLabel.textContent = 'AI агент';
                    // Текст ответа + анимация точек в конце, пока агент пишет.
                    streamingBubbleText = document.createElement('p');
                    streamingBubbleText.className = 'whitespace-pre-wrap break-words';
                    const streamingTextNode = document.createTextNode('');
                    const streamingDots = document.createElement('span');
                    streamingDots.className = 'ai-dots';
                    streamingDots.innerHTML = '<span></span><span></span><span></span>';
                    streamingBubbleText.append(streamingTextNode, streamingDots);
                    // Статус-строка для tool-call'ов (обновляется из job-SSE).
                    const streamingStatus = document.createElement('p');
                    streamingStatus.className = 'mt-2 hidden items-center gap-2 text-xs text-cyan-200';
                    assistantBubble.append(assistantLabel, streamingBubbleText, streamingStatus);
                    assistantRow.appendChild(assistantBubble);
                    const historyEl = document.getElementById('analytics-conversation-history');
                    if (historyEl) historyEl.appendChild(assistantRow);
                    requestAnimationFrame(scrollConversationToBottom);

                    const streamResponse = await fetch(streamBase + '/api/agent/respond/stream', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(prepared.payload),
                        signal: chatAbort?.signal,
                    });
                    if (!streamResponse.ok || !streamResponse.body) {
                        let detail = 'HTTP ' + streamResponse.status;
                        try {
                            const err = await streamResponse.json();
                            detail = Array.isArray(err.detail)
                                ? err.detail.map(e => e.msg).join('; ')
                                : (err.detail || detail);
                        } catch (_) {}
                        throw new Error(detail);
                    }
                    if (appState) { appState.chatThinking = false; }

                    const reader = streamResponse.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';
                    let streamError = null;
                    while (true) {
                        const {done, value} = await reader.read();
                        if (done) break;
                        buffer += decoder.decode(value, {stream: true});
                        let sep;
                        while ((sep = buffer.indexOf('\n\n')) !== -1) {
                            const chunk = buffer.slice(0, sep);
                            buffer = buffer.slice(sep + 2);
                            const line = chunk.split('\n').find(l => l.startsWith('data: '));
                            if (!line) continue;
                            const evt = JSON.parse(line.slice(6));
                            if (evt.type === 'meta') tool = evt.tool;
                            else if (evt.type === 'token') { reply += evt.text; fillStreamingBubble(reply); requestAnimationFrame(scrollConversationToBottom); }
                            else if (evt.type === 'error') { streamError = evt.detail || 'Ошибка ИИ.'; }
                            else if (evt.type === 'done') { buffer = ''; break; }
                        }
                    }
                    if (streamError) throw new Error(streamError);
                    reply = reply.trim() || '(пустой ответ)';
                    fillStreamingBubble(reply);
                    finishStreamingBubble();

                    // 3) Persist the reply BEFORE dispatching a job — a page
                    // reload during the job must not lose the confirmation.
                    const stored = await jsonFetch(storeBase + '/' + dashboardId + '/chat/store-reply', {dashboard_id: dashboardId, content: reply});
                    const storedJson = await stored.json().catch(() => ({}));
                    const replyMessageId = storedJson.message_id || null;

                    // 4) Tool decisions start a dashboard job (chat just ends here).
                    if (['generate_dashboard', 'edit_dashboard', 'clear_dashboard', 'replace_dashboard'].includes(tool)) {
                        const dispatch = await jsonFetch(storeBase + '/' + dashboardId + '/chat/dispatch', {
                            dashboard_id: dashboardId, tool: tool, message: draft, message_id: replyMessageId,
                        });
                        if (!dispatch.ok) {
                            const err = await dispatch.json().catch(() => ({}));
                            throw new Error(err.error || 'dispatch ' + dispatch.status);
                        }
                        const job = await dispatch.json();
                        jobCancelUrl = streamBase + '/api/dashboard-jobs/' + encodeURIComponent(job.job_id) + '/cancel';
                        if (appState) { appState.chatStreaming = false; }
                        chatAbort = null;
                        // Бейдж «писателя» (вариант 14) + live-статус в пузыре.
                        assistantLabel.textContent = 'AI агент · строит дашборд…';
                        streamingStatus.classList.remove('hidden');
                        streamingStatus.classList.add('flex');
                        startStream(job.stream_url, job.complete_url, function (jobState) {
                            streamingStatus.textContent = '⚙ ' + (jobState.message || 'Строю дашборд…');
                            requestAnimationFrame(scrollConversationToBottom);
                        });
                        return;
                    }
                } catch (error) {
                    const aborted = error.name === 'AbortError';
                    finishStreamingBubble();
                    fillStreamingBubble(aborted ? '⏹ Остановлено пользователем.' : '⚠ ' + (error.message || 'Ошибка запроса к AI.'));
                    if (!aborted) console.warn('chat streaming failed:', error);
                } finally {
                    streamingBubbleText = null;
                    streamingTextNode = null;
                    streamingDots = null;
                    streamingStatus = null;
                    if (chatAbort === null) {
                        // job flow already reset the streaming state
                    } else {
                        chatAbort = null;
                    }
                    if (appState) { appState.chatStreaming = false; appState.chatThinking = false; }
                }
            }

            function stopChatStreaming() {
                if (chatAbort) {
                    chatAbort.abort();
                    return;
                }
                if (jobCancelUrl) {
                    fetch(jobCancelUrl, {method: 'POST'}).catch(() => {});
                }
            }

            // Expose for Alpine expressions inside the conversation panel.
            window.submitChatStreaming = submitChatStreaming;
            window.stopChatStreaming = stopChatStreaming;

            // A first message in a brand-new chat reloads the page once (the
            // conversation panel does not exist yet). Resume the pending AI
            // turn here, inside the freshly rendered panel.
            const pendingTurn = sessionStorage.getItem('analyticsPendingTurn');
            if (pendingTurn && document.getElementById('analytics-conversation-history')) {
                sessionStorage.removeItem('analyticsPendingTurn');
                try {
                    const turn = JSON.parse(pendingTurn);
                    runStreamingTurn(turn.prepared, turn.draft, turn);
                } catch (error) {
                    console.warn('pending AI turn failed:', error);
                }
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
