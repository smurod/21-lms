<div x-show="analyticsChatOpen" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
     class="fixed inset-y-0 right-0 z-50 flex w-[min(25vw,430px)] min-w-[320px] flex-col border-l border-white/10 bg-[#0b0d14] shadow-2xl shadow-black/60">
    <div class="flex shrink-0 items-center gap-3 border-b border-white/10 px-5 py-5">
        <button type="button" @click="analyticsChatOpen = false"
                aria-label="Свернуть AI чат" title="Свернуть AI чат"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-zinc-300 transition-colors hover:bg-white/10 hover:text-white">
            <i data-lucide="chevrons-left-right" class="h-4 w-4"></i>
        </button>
        <div class="min-w-0">
            <p class="text-[10px] font-mono font-semibold uppercase tracking-[.18em] text-cyan-300">AI Analytics</p>
            <h2 class="truncate text-sm font-semibold text-white">История диалога</h2>
        </div>
    </div>

    <div id="analytics-conversation-history" class="flex-1 space-y-4 overflow-y-auto px-4 py-5">
        @foreach ($conversationMessages as $message)
            <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[92%] rounded-2xl px-4 py-3 text-sm leading-6 {{ $message['role'] === 'user' ? 'rounded-br-md bg-violet-400 text-zinc-950' : 'rounded-bl-md border border-white/10 bg-white/5 text-zinc-200' }}">
                    <p class="mb-1 text-[10px] font-mono font-semibold uppercase tracking-wide {{ $message['role'] === 'user' ? 'text-zinc-800/75' : 'text-cyan-300' }}">
                        {{ $message['role'] === 'user' ? 'Вы' : 'AI агент' }}
                    </p>
                    <p class="whitespace-pre-wrap break-words">{{ $message['content'] }}</p>
                </div>
            </div>
        @endforeach
        <div x-show="chatPendingMessage" x-cloak class="flex justify-end">
            <div class="max-w-[92%] rounded-2xl rounded-br-md bg-violet-400 px-4 py-3 text-sm leading-6 text-zinc-950">
                <p class="mb-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-zinc-800/75">Вы</p>
                <p x-text="chatPendingMessage" class="whitespace-pre-wrap break-words"></p>
            </div>
        </div>
        <div x-show="chatThinking" x-cloak class="flex justify-start">
            <div class="flex items-center gap-2 rounded-2xl rounded-bl-md border border-white/10 bg-white/5 px-4 py-3 text-cyan-200">
                <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-cyan-300"></span>
                <span class="text-xs font-medium">AI думает…</span>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ $isInitialConversation ? route('admin.analytics.agent') : route('admin.analytics.chat', $selectedDashboard) }}" target="analytics-job-launcher"
          @if (!empty($selectedDashboard)) data-dashboard-id="{{ $selectedDashboard->id }}" @endif
          data-prepare-url="{{ route('admin.analytics.chat.prepare') }}"
          data-stream-base="{{ rtrim(config('datalens_ai.base_url'), '/') }}"
          data-store-base="{{ url('admin/analytics') }}"
          @submit.prevent="submitChatStreaming($el)"
          class="shrink-0 border-t border-white/10 p-4">
        @csrf
        <input type="hidden" name="message" :value="chatSubmittedMessage">
        <div class="flex items-center gap-2 rounded-2xl border border-white/15 bg-zinc-950 px-2 py-2 shadow-xl shadow-black/30 focus-within:border-violet-400/50">
            <input type="text" x-model="chatDraft" required maxlength="2000" autocomplete="off"
                   placeholder="Попросите AI изменить dashboard…"
                   class="analytics-prompt-input min-w-0 flex-1 bg-transparent px-2 py-2 text-sm text-white outline-none placeholder:text-zinc-600">
            <button type="submit" x-show="!chatStreaming" {{ $serviceUnavailable ? 'disabled' : '' }}
            aria-label="Отправить сообщение" title="Отправить сообщение"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-400 text-zinc-950 transition-colors hover:bg-violet-300 disabled:cursor-not-allowed disabled:opacity-40">
                <i data-lucide="arrow-up" class="h-4 w-4"></i>
            </button>
            <button type="button" x-show="chatStreaming" x-cloak @click="stopChatStreaming()"
            aria-label="Остановить ответ" title="Остановить ответ"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/20 bg-zinc-800 text-white transition-colors hover:bg-zinc-700">
                <span class="h-3 w-3 rounded-[3px] bg-white"></span>
            </button>
        </div>
    </form>

    <div x-show="analyticsLoading" x-cloak x-transition.opacity
         class="absolute inset-0 z-20 flex flex-col border-l border-cyan-400/15 bg-[#0b0d14] px-5 py-6 shadow-2xl shadow-black/60">
        <div class="flex items-start gap-3 border-b border-cyan-400/15 pb-5">
            <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-400/15">
                <span class="absolute inset-0 animate-ping rounded-xl bg-cyan-400/15"></span>
                <i data-lucide="bot" class="relative h-5 w-5 text-cyan-200"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-mono font-semibold uppercase tracking-[.16em] text-cyan-300">AI Agent работает</p>
                <h2 data-progress-message class="mt-1 text-sm font-semibold leading-5 text-white">Передаём запрос AI-агенту…</h2>
                <p class="mt-1 text-xs leading-5 text-zinc-500">Диалог и dashboard останутся на месте.</p>
            </div>
        </div>
        <div class="mt-5 space-y-2 overflow-y-auto">
            @foreach ([
                1 => 'Подключение к DataLens', 2 => 'Рабочая тетрадь', 3 => 'Подключение к базе',
                4 => 'Анализ структуры', 5 => 'AI и SQL', 6 => 'Создание chart',
                7 => 'Сетка dashboard', 8 => 'Публикация', 9 => 'Dashboard готов'
            ] as $step => $title)
                <div data-progress-row="{{ $step }}" class="flex items-center gap-2 rounded-xl px-3 py-2">
                    <span data-progress-indicator="{{ $step }}" class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-white/10 bg-zinc-950 text-xs font-bold text-zinc-600">·</span>
                    <p data-progress-title="{{ $step }}" class="text-xs font-semibold text-zinc-600">{{ $title }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>

<button x-show="!analyticsChatOpen" x-cloak x-transition.opacity type="button" @click="analyticsChatOpen = true"
        aria-label="Развернуть AI чат" title="Развернуть AI чат"
        class="fixed right-5 top-5 z-50 flex h-10 w-10 items-center justify-center rounded-xl border border-cyan-400/25 bg-zinc-900 text-cyan-200 shadow-xl shadow-black/50 transition-colors hover:bg-cyan-400/10">
    <i data-lucide="chevrons-left-right" class="h-4 w-4"></i>
</button>
