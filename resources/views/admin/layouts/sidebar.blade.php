<div class="w-72 bg-zinc-950 border-r border-white/10 flex flex-col shrink-0">
    <div class="p-8 flex items-center gap-3">
        <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-cyan-400 to-violet-500 flex items-center justify-center">
            <span class="text-xl font-bold tracking-tighter">L</span>
        </div>
        <div>
            <div class="font-semibold text-3xl tracking-tighter">lumina</div>
            <div class="text-[10px] text-zinc-500 -mt-1">GROW•FOCUS•REPEAT</div>
        </div>
    </div>

    <!-- Навигация -->
    <nav class="px-3 mt-2 flex-1 overflow-y-auto" aria-label="Admin navigation">
        <div class="mb-2 px-3 text-[10px] uppercase tracking-widest text-zinc-600 font-semibold">Главное</div>

        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center gap-3 px-6 py-3.5 rounded-3xl mb-1 transition-colors duration-200
                  {{ request()->routeIs('admin.dashboard') ? 'bg-white text-black' : 'text-zinc-300 hover:bg-white/5' }}">
            <i data-lucide="target" class="w-5 h-5"></i>
            <span class="font-medium">Dashboard</span>
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="flex items-center gap-3 px-6 py-3.5 rounded-3xl mb-1 transition-colors duration-200
                  {{ request()->routeIs('admin.users.*') ? 'bg-white text-black' : 'text-zinc-300 hover:bg-white/5' }}">
            <i data-lucide="users" class="w-5 h-5"></i>
            <span class="font-medium">Пользователи</span>
        </a>

        <div class="pt-4 pb-2 px-3 text-[10px] uppercase tracking-widest text-zinc-600 font-semibold">Контент</div>

        <a href="{{ route('admin.projects.index') }}"
           class="flex items-center gap-3 px-6 py-3.5 rounded-3xl mb-1 transition-colors duration-200
                  {{ request()->routeIs('admin.projects.*') ? 'bg-white text-black' : 'text-zinc-300 hover:bg-white/5' }}">
            <i data-lucide="folder-open" class="w-5 h-5"></i>
            <span class="font-medium">Проекты</span>
        </a>

        <a href="{{ route('admin.courses.index') }}"
           class="flex items-center gap-3 px-6 py-3.5 rounded-3xl mb-1 transition-colors duration-200
                  {{ request()->routeIs('admin.courses.*') ? 'bg-white text-black' : 'text-zinc-300 hover:bg-white/5' }}">
            <i data-lucide="book-open" class="w-5 h-5"></i>
            <span class="font-medium">Курсы</span>
        </a>

        <a href="{{ route('admin.reviews.index') }}"
           class="flex items-center gap-3 px-6 py-3.5 rounded-3xl mb-1 transition-colors duration-200
                  {{ request()->routeIs('admin.reviews.*') ? 'bg-white text-black' : 'text-zinc-300 hover:bg-white/5' }}">
            <i data-lucide="clipboard-check" class="w-5 h-5"></i>
            <span class="font-medium">Reviews</span>
        </a>

        <a href="{{ config('services.gitlab.login_url') }}"
           target="_blank"
           rel="noopener noreferrer"
           class="flex items-center gap-3 px-6 py-3.5 rounded-3xl mb-1 text-zinc-300 hover:bg-white/5 transition-colors duration-200">
            <i data-lucide="gitlab" class="w-5 h-5"></i>
            <span class="font-medium">GitLab Login</span>
        </a>
    </nav>

    <div class="p-6 border-t border-white/10 text-xs text-zinc-500 flex items-center justify-between">
        <div>© {{ now()->year }} Lumina</div>
        <div class="flex gap-4">
            <a href="#" class="hover:text-white transition-colors duration-300">Docs</a>
            <a href="#" class="hover:text-white transition-colors duration-300">Support</a>
        </div>
    </div>
</div>
