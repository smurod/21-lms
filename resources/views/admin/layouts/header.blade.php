<header class="h-20 border-b border-white/10 bg-zinc-950/80 backdrop-blur-xl flex items-center px-10 z-50 sticky top-0 shrink-0">
    <!-- Для админки убрано верхнее меню навигации, оставлено пустое пространство для баланса -->
    <div class="flex-1"></div>

    <div class="flex-1 max-w-md relative group mx-auto">
        <div class="absolute left-5 top-1/2 -translate-y-1/2 text-zinc-500">
            <i data-lucide="search" class="w-4 h-4"></i>
        </div>
        <input
            type="text"
            placeholder="Search tasks, projects..."
            class="w-full bg-zinc-900 border border-white/10 focus:border-white/30 pl-12 py-3 rounded-2xl text-sm placeholder:text-zinc-500 focus:outline-none transition-colors duration-500"
        />
    </div>

    <div class="flex-1 flex items-center justify-end gap-6">
        <div class="relative cursor-pointer hover:scale-110 transition-transform duration-300">
            <i data-lucide="bell" class="w-5 h-5 text-zinc-400"></i>
            <div class="absolute -top-1 -right-1 w-4 h-4 bg-rose-500 rounded-full flex items-center justify-center text-[9px] font-mono">3</div>
        </div>

        <!-- User Menu -->
        <div x-data="{ open: false }" @click.away="open = false" class="relative">
            <button
                @click="open = !open"
                class="flex items-center gap-3 focus:outline-none"
                aria-expanded="false"
                aria-haspopup="true"
                aria-label="User menu"
            >
                <div class="text-right">
                    <div class="text-sm font-medium">{{ auth()->user()->name ?? 'Admin' }}</div>
                    <div class="text-xs text-emerald-400">Level {{ auth()->user()->level ?? 12 }} • Explorer</div>
                </div>
                <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin') }}&background=0D8ABC&color=fff" alt="Avatar" class="w-9 h-9 rounded-2xl object-cover ring-2 ring-white/20"/>
                <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
            </button>

            <!-- Dropdown -->
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                class="absolute right-0 top-full mt-2 w-48 rounded-2xl bg-zinc-900 border border-white/10 shadow-xl py-1 z-50"
                style="display: none;"
            >
                <a
                    href="#"
                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-zinc-300 hover:bg-white/5 hover:text-white transition-colors"
                >
                    <i data-lucide="user" class="w-4 h-4"></i>
                    Профиль
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-rose-400 hover:bg-white/5 hover:text-rose-300 transition-colors"
                    >
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        Выход
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
