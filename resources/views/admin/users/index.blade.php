@extends('admin.layouts.app')

@section('title', 'Users')

@section('content')
    <div class="space-y-8 anim-up" style="animation-delay: 0.1s">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-4xl font-semibold tracking-tighter text-white">Users</h1>
                <p class="text-zinc-400 mt-1 font-medium">Управление пользователями</p>
            </div>

            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 bg-zinc-900 border border-white/10 text-white px-6 py-3 rounded-2xl text-sm font-semibold hover:bg-white/5 transition-all duration-300">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Назад
            </a>
        </div>

        <!-- Filters: a native GET form avoids requests while the administrator is typing. -->
        <form method="GET" action="{{ route('admin.users.index') }}" class="bg-zinc-900 rounded-3xl p-6 border border-white/5">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex flex-1 gap-3">
                    <div class="relative flex-1 group">
                        <div class="absolute left-5 top-1/2 -translate-y-1/2 text-zinc-500 group-focus-within:text-cyan-400 transition-colors">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Поиск по имени или email..."
                               class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 pl-12 pr-4 py-3.5 rounded-2xl text-sm placeholder:text-zinc-500 text-white outline-none transition-all duration-300">
                    </div>

                    <button type="submit"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-semibold text-black transition-all duration-300 hover:bg-white/90 active:scale-95">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        Поиск
                    </button>
                </div>

                <div class="relative">
                    <select name="role" class="appearance-none w-full sm:w-48 bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-5 py-3.5 pr-10 rounded-2xl text-sm text-white outline-none transition-all duration-300 cursor-pointer">
                        <option value="" class="bg-zinc-900">Все роли</option>
                        @foreach($roles ?? [] as $role)
                            <option value="{{ $role->name ?? $role }}" @selected(request('role') === ($role->name ?? $role)) class="bg-zinc-900">{{ $role->name ?? $role }}</option>
                        @endforeach
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-zinc-500">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </div>
            </div>
        </form>

        <!-- Users Table -->
        <div class="bg-zinc-900 rounded-3xl border border-white/5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="border-b border-white/10 bg-zinc-950/50">
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Пользователь</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Email</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Роль</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Уровень</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">XP</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold text-right">Действия</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                    @forelse($users ?? [] as $user)
                        <tr class="hover:bg-white/5 transition-colors duration-200 group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-400 to-violet-500 flex items-center justify-center text-white font-bold text-lg shrink-0">
                                        {{ mb_substr($user->name ?? 'A', 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="font-semibold text-white text-[15px] block">{{ $user->name ?? 'Alex Rivera' }}</span>
                                        @if(method_exists($user, 'trashed') && $user->trashed())
                                            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                                Удалён
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-zinc-400">{{ $user->email ?? 'email@example.com' }}</span>
                            </td>

                            <td class="px-6 py-4">
                                @php
                                    $rolesArr = isset($user->roles) ? $user->roles->pluck('name')->toArray() : [];
                                    $rolesArr = count($rolesArr) > 0 ? $rolesArr : ['user'];
                                @endphp
                                <div class="flex flex-wrap gap-2">
                                    @foreach($rolesArr as $r)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase border
                                    {{ $r === 'admin' ? 'bg-violet-500/10 text-violet-400 border-violet-500/20' : 'bg-white/5 text-zinc-300 border-white/10' }}">
                                    {{ $r }}
                                </span>
                                    @endforeach
                                </div>
                            </td>

                            <td class="px-6 py-4">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/5 border border-white/10 text-sm font-semibold text-white tabular-nums">
                                {{ $user->level ?? 1 }}
                            </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-sm font-mono font-bold text-amber-400">{{ number_format($user->total_xp ?? 0) }} XP</span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                @if(method_exists($user, 'trashed') && $user->trashed())
                                    <form method="POST" action="{{ route('admin.users.restore', $user->id) }}" class="inline-block">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-500/10 text-xs font-semibold text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-colors"
                                                title="Восстановить пользователя">
                                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                            Восстановить
                                        </button>
                                    </form>
                                @elseif(auth()->id() === $user->id)
                                    <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 text-xs font-semibold text-zinc-500 border border-white/10 cursor-not-allowed" title="Нельзя изменить собственную роль или удалить свой аккаунт">
                                        <i data-lucide="lock" class="w-4 h-4"></i>
                                        Это вы
                                    </span>
                                @else
                                    <div x-data="{ open: false }" class="relative inline-block text-left">
                                        <button @click="open = !open" @click.outside="open = false"
                                                class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white transition-colors" title="Действия пользователя">
                                            <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                        </button>

                                        <div x-show="open" x-cloak
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             class="absolute right-0 mt-2 w-52 bg-zinc-900 rounded-2xl shadow-xl border border-white/10 z-50 overflow-hidden origin-top-right">
                                            <div class="p-2">
                                                <p class="px-3 py-2 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold border-b border-white/5 mb-1">Назначить роль:</p>
                                                <form method="POST" action="{{ route('admin.users.role', $user->id ?? 1) }}" class="flex flex-col gap-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" name="role" value="admin"
                                                            class="w-full text-left px-3 py-2 text-sm rounded-xl transition-colors
                                                    {{ in_array('admin', $rolesArr) ? 'bg-violet-500/10 text-violet-400 font-semibold' : 'text-zinc-300 hover:bg-white/5 hover:text-white font-medium' }}">
                                                        Admin
                                                    </button>
                                                    <button type="submit" name="role" value="user"
                                                            class="w-full text-left px-3 py-2 text-sm rounded-xl transition-colors
                                                    {{ in_array('user', $rolesArr) ? 'bg-white/10 text-white font-semibold' : 'text-zinc-300 hover:bg-white/5 hover:text-white font-medium' }}">
                                                        User
                                                    </button>
                                                </form>

                                                <div class="border-t border-white/5 mt-2 pt-2">
                                                    <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                onclick="return confirm('Удалить пользователя {{ addslashes($user->name ?? $user->email) }}? Его можно будет восстановить через Soft Delete.')"
                                                                class="w-full text-left px-3 py-2 text-sm rounded-xl text-rose-400 hover:bg-rose-500/10 font-semibold transition-colors">
                                                            Удалить
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="mx-auto w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mb-4">
                                    <i data-lucide="users-x" class="w-8 h-8 text-zinc-500"></i>
                                </div>
                                <p class="text-lg font-medium text-zinc-300">Пользователи не найдены</p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($users) && $users->hasPages())
                <div class="px-6 py-4 border-t border-white/5 bg-zinc-950/30">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
