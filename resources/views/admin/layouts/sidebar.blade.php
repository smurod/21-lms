<aside class="flex shrink-0 flex-col overflow-hidden border-r border-white/10 bg-zinc-950 transition-[width] duration-300 ease-out"
       :class="sidebarOpen ? 'w-72' : 'w-[76px]'">
    <div class="flex items-center gap-3"
         :class="sidebarOpen ? 'p-8' : 'justify-center p-3'">
        <div x-show="sidebarOpen" x-transition.opacity class="flex min-w-0 flex-1 items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-violet-500">
                <span class="text-xl font-bold tracking-tighter">L</span>
            </div>
            <div class="min-w-0">
                <div class="font-semibold text-3xl tracking-tighter">21-LMS</div>
                <div class="-mt-1 text-[10px] text-zinc-500">LEARN • BUILD</div>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2" :class="sidebarOpen ? '' : 'flex-col'">
            <button type="button" @click="sidebarOpen = !sidebarOpen"
                    class="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-zinc-300 transition-colors hover:bg-white/10 hover:text-white"
                    :aria-label="sidebarOpen ? 'Скрыть боковое меню' : 'Открыть боковое меню'"
                    :title="sidebarOpen ? 'Скрыть меню' : 'Открыть меню'">
                <i x-show="sidebarOpen" x-cloak data-lucide="panel-left-close" class="h-5 w-5"></i>
                <i x-show="!sidebarOpen" x-cloak data-lucide="panel-left-open" class="h-5 w-5"></i>
            </button>

        </div>
    </div>

    <nav x-show="sidebarOpen" x-transition.opacity x-cloak
         class="mt-2 flex-1 overflow-y-auto px-3" aria-label="Admin navigation">
        <div class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-600">Главное</div>

        <a href="{{ route('admin.dashboard') }}"
           class="mb-1 flex items-center gap-3 rounded-3xl px-6 py-3.5 transition-colors duration-200
                  {{ request()->routeIs('admin.dashboard') ? 'bg-white text-black' : 'text-zinc-300 hover:bg-white/5' }}">
            <i data-lucide="target" class="h-5 w-5"></i>
            <span class="font-medium">Dashboard</span>
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="mb-1 flex items-center gap-3 rounded-3xl px-6 py-3.5 transition-colors duration-200
                  {{ request()->routeIs('admin.users.*') ? 'bg-white text-black' : 'text-zinc-300 hover:bg-white/5' }}">
            <i data-lucide="users" class="h-5 w-5"></i>
            <span class="font-medium">Пользователи</span>
        </a>

        <div class="px-3 pb-2 pt-4 text-[10px] font-semibold uppercase tracking-widest text-zinc-600">Контент</div>

        <a href="{{ route('admin.projects.index') }}"
           class="mb-1 flex items-center gap-3 rounded-3xl px-6 py-3.5 transition-colors duration-200
                  {{ request()->routeIs('admin.projects.*') ? 'bg-white text-black' : 'text-zinc-300 hover:bg-white/5' }}">
            <i data-lucide="folder-open" class="h-5 w-5"></i>
            <span class="font-medium">Проекты</span>
        </a>

        <a href="{{ route('admin.courses.index') }}"
           class="mb-1 flex items-center gap-3 rounded-3xl px-6 py-3.5 transition-colors duration-200
                  {{ request()->routeIs('admin.courses.*') ? 'bg-white text-black' : 'text-zinc-300 hover:bg-white/5' }}">
            <i data-lucide="book-open" class="h-5 w-5"></i>
            <span class="font-medium">Курсы</span>
        </a>

        <div class="mb-1">
            <button type="button" @click="analyticsMenuOpen = !analyticsMenuOpen"
                    :aria-expanded="analyticsMenuOpen.toString()"
                    class="flex w-full items-center gap-3 rounded-3xl px-6 py-3.5 text-left transition-colors duration-200
                           {{ request()->routeIs('admin.analytics.*') ? 'bg-white text-black' : 'text-zinc-300 hover:bg-white/5' }}">
                <i data-lucide="chart-no-axes-combined" class="h-5 w-5"></i>
                <span class="flex-1 font-medium">AI Analytics</span>
                <i data-lucide="chevron-down" class="h-4 w-4 transition-transform duration-200" :class="analyticsMenuOpen ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="analyticsMenuOpen" x-collapse x-cloak class="ml-7 mt-1 space-y-1 border-l border-cyan-400/20 pl-4">
                <a href="{{ route('admin.analytics.create') }}"
                   class="flex items-center gap-2 rounded-2xl px-3 py-2.5 text-sm transition-colors {{ request()->routeIs('admin.analytics.create') || request()->boolean('reset') ? 'bg-cyan-400/10 text-cyan-200' : 'text-zinc-400 hover:bg-white/5 hover:text-zinc-200' }}">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Новый dashboard
                </a>
                <a href="{{ route('admin.analytics.dashboards') }}"
                   class="flex items-center gap-2 rounded-2xl px-3 py-2.5 text-sm transition-colors {{ request()->routeIs('admin.analytics.dashboards') ? 'bg-cyan-400/10 text-cyan-200' : 'text-zinc-400 hover:bg-white/5 hover:text-zinc-200' }}">
                    <i data-lucide="list" class="h-4 w-4"></i>
                    Список dashboards
                </a>
            </div>
        </div>

        <a href="{{ route('admin.reviews.index') }}"
           class="mb-1 flex items-center gap-3 rounded-3xl px-6 py-3.5 transition-colors duration-200
                  {{ request()->routeIs('admin.reviews.*') ? 'bg-white text-black' : 'text-zinc-300 hover:bg-white/5' }}">
            <i data-lucide="clipboard-check" class="h-5 w-5"></i>
            <span class="font-medium">Reviews</span>
        </a>

        <a href="{{ config('services.gitlab.login_url') }}"
           target="_blank" rel="noopener noreferrer"
           class="mb-1 flex items-center gap-3 rounded-3xl px-6 py-3.5 text-zinc-300 transition-colors duration-200 hover:bg-white/5">
            <span class="flex h-5 w-5 items-center justify-center rounded-md bg-[#fc6d26] text-[11px] font-black leading-none text-white" aria-hidden="true">G</span>
            <span class="font-medium">GitLab Login</span>
        </a>
    </nav>

    <div x-show="sidebarOpen" x-transition.opacity x-cloak
         class="flex items-center justify-between border-t border-white/10 p-6 text-xs text-zinc-500">
        <div>© {{ now()->year }} 21-LMS</div>
        <div class="flex gap-4">
            <a href="#" class="transition-colors duration-300 hover:text-white">Docs</a>
            <a href="#" class="transition-colors duration-300 hover:text-white">Support</a>
        </div>
    </div>
</aside>
