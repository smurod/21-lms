@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div x-data="{
    stats: @js($stats ?? ['total_users' => 1250, 'total_projects' => 45, 'pending_submissions' => 12, 'completed_submissions' => 890, 'published_projects' => 38, 'active_users' => 340, 'pending_reviews' => 8, 'total_reviews' => 156]),
    loading: false
}" class="space-y-8 anim-up" style="animation-delay: 0.1s">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-4xl font-semibold tracking-tighter text-white">Dashboard</h1>
                <p class="text-zinc-400 mt-1 font-medium">Обзор системы LMS</p>
            </div>
            <div class="flex items-center gap-2">
            <span class="text-sm text-zinc-500 font-medium bg-white/5 px-4 py-2 rounded-2xl border border-white/10">
                Обновлено: <span class="font-semibold text-white">{{ now()->format('H:i') }}</span>
            </span>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Users -->
            <div class="bg-zinc-900/70 border border-white/5 rounded-3xl p-7 group hover:border-white/20 transition-colors duration-500">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-11 h-11 rounded-2xl bg-cyan-500/10 flex items-center justify-center group-hover:scale-110 transition-transform duration-[600ms] ease-[cubic-bezier(0.1,0.9,0.2,1)]">
                        <i data-lucide="users" class="w-5 h-5 text-cyan-400"></i>
                    </div>
                </div>
                <div>
                    <p class="text-5xl font-semibold tabular-nums tracking-tighter mb-1 text-white" x-text="stats.total_users"></p>
                    <p class="text-zinc-400 text-sm tracking-wider uppercase font-semibold">Всего пользователей</p>
                </div>
            </div>

            <!-- Total Projects -->
            <div class="bg-zinc-900/70 border border-white/5 rounded-3xl p-7 group hover:border-white/20 transition-colors duration-500">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-11 h-11 rounded-2xl bg-violet-500/10 flex items-center justify-center group-hover:scale-110 transition-transform duration-[600ms] ease-[cubic-bezier(0.1,0.9,0.2,1)]">
                        <i data-lucide="folder-open" class="w-5 h-5 text-violet-400"></i>
                    </div>
                </div>
                <div>
                    <p class="text-5xl font-semibold tabular-nums tracking-tighter mb-1 text-white" x-text="stats.total_projects"></p>
                    <p class="text-zinc-400 text-sm tracking-wider uppercase font-semibold">Всего проектов</p>
                </div>
            </div>

            <!-- Pending Submissions -->
            <div class="bg-zinc-900/70 border border-white/5 rounded-3xl p-7 group hover:border-white/20 transition-colors duration-500">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-11 h-11 rounded-2xl bg-amber-500/10 flex items-center justify-center group-hover:scale-110 transition-transform duration-[600ms] ease-[cubic-bezier(0.1,0.9,0.2,1)]">
                        <i data-lucide="clock" class="w-5 h-5 text-amber-400"></i>
                    </div>
                </div>
                <div>
                    <p class="text-5xl font-semibold tabular-nums tracking-tighter mb-1 text-white" x-text="stats.pending_submissions"></p>
                    <p class="text-zinc-400 text-sm tracking-wider uppercase font-semibold">Ожидают проверки</p>
                </div>
            </div>

            <!-- Completed -->
            <div class="bg-zinc-900/70 border border-white/5 rounded-3xl p-7 group hover:border-white/20 transition-colors duration-500">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-500/10 flex items-center justify-center group-hover:scale-110 transition-transform duration-[600ms] ease-[cubic-bezier(0.1,0.9,0.2,1)]">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400"></i>
                    </div>
                </div>
                <div>
                    <p class="text-5xl font-semibold tabular-nums tracking-tighter mb-1 text-white" x-text="stats.completed_submissions"></p>
                    <p class="text-zinc-400 text-sm tracking-wider uppercase font-semibold">Завершено задач</p>
                </div>
            </div>
        </div>

        <!-- Published vs Draft & Quick Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Published / Draft Chart -->
            <div class="lg:col-span-2 bg-zinc-900 rounded-3xl p-8 border border-white/5 flex flex-col">
                <h3 class="uppercase text-xs font-mono tracking-widest mb-8 text-violet-400 font-semibold">Проекты в системе</h3>

                <div class="flex gap-8 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.6)]"></div>
                        <span class="text-sm font-medium text-zinc-400">Опубликовано: <strong class="text-white ml-1" x-text="stats.published_projects"></strong></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-zinc-600"></div>
                        <span class="text-sm font-medium text-zinc-400">Черновики: <strong class="text-white ml-1" x-text="stats.total_projects - stats.published_projects"></strong></span>
                    </div>
                </div>

                <div class="mt-auto relative h-4 bg-zinc-800 rounded-3xl overflow-hidden shadow-inner w-full">
                    <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-3xl transition-all ease-[cubic-bezier(0.1,0.9,0.2,1)] duration-[1400ms]"
                         x-data="{ w: '0%' }"
                         x-init="setTimeout(() => { w = Math.round((stats.published_projects / Math.max(stats.total_projects, 1)) * 100) + '%' }, 300)"
                         :style="`width: ${w}`"></div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="bg-zinc-900 rounded-3xl p-8 border border-white/5">
                <h3 class="uppercase text-xs font-mono tracking-widest mb-6 text-cyan-400 font-semibold">Статистика</h3>

                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-zinc-400">Активных пользователей</span>
                        <span class="text-lg font-semibold text-white tabular-nums" x-text="stats.active_users"></span>
                    </div>
                    <div class="h-px bg-white/5"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-zinc-400">Ожидают ревью</span>
                        <span class="text-lg font-semibold text-white tabular-nums" x-text="stats.pending_reviews"></span>
                    </div>
                    <div class="h-px bg-white/5"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-zinc-400">Всего ревью</span>
                        <span class="text-lg font-semibold text-white tabular-nums" x-text="stats.total_reviews"></span>
                    </div>

                    <div class="pt-4">
                        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold tracking-wider text-cyan-400 hover:text-cyan-300 transition-colors uppercase">
                            Смотреть всех <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Actions -->
        <div class="bg-zinc-900 rounded-3xl p-8 border border-white/5">
            <h3 class="uppercase text-xs font-mono tracking-widest mb-6 text-zinc-500 font-semibold">Быстрые действия</h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="{{ route('admin.projects.create') }}" class="group flex items-center gap-4 p-5 rounded-2xl bg-zinc-950 border border-white/5 hover:border-white/20 transition-all duration-300 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-violet-500/10 flex items-center justify-center group-hover:bg-violet-500/20 transition-colors">
                        <i data-lucide="file-plus" class="w-5 h-5 text-violet-400"></i>
                    </div>
                    <span class="font-medium text-zinc-300 group-hover:text-white transition-colors">Новый проект</span>
                </a>

                <a href="{{ route('admin.users.index') }}" class="group flex items-center gap-4 p-5 rounded-2xl bg-zinc-950 border border-white/5 hover:border-white/20 transition-all duration-300 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 flex items-center justify-center group-hover:bg-cyan-500/20 transition-colors">
                        <i data-lucide="users" class="w-5 h-5 text-cyan-400"></i>
                    </div>
                    <span class="font-medium text-zinc-300 group-hover:text-white transition-colors">Управление Users</span>
                </a>

                <a href="{{ route('admin.projects.index') }}" class="group flex items-center gap-4 p-5 rounded-2xl bg-zinc-950 border border-white/5 hover:border-white/20 transition-all duration-300 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-500/20 transition-colors">
                        <i data-lucide="layout-list" class="w-5 h-5 text-emerald-400"></i>
                    </div>
                    <span class="font-medium text-zinc-300 group-hover:text-white transition-colors">Все проекты</span>
                </a>
            </div>
        </div>

    </div>
@endsection
