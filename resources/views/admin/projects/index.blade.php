@extends('admin.layouts.app')

@section('title', 'Projects')

@section('content')
    <div class="space-y-8 anim-up" style="animation-delay: 0.1s">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-4xl font-semibold tracking-tighter text-white">Projects</h1>
                <p class="text-zinc-400 mt-1 font-medium">Управление учебными проектами</p>
            </div>

            <a href="{{ route('admin.projects.create') }}" class="flex items-center gap-2 bg-white text-black px-6 py-3 rounded-2xl text-sm font-semibold hover:bg-white/90 transition-all duration-300 active:scale-95 shadow-lg shadow-white/10">
                <i data-lucide="plus" class="w-4 h-4"></i> Новый проект
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-zinc-900 rounded-3xl p-6 border border-white/5">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1 group">
                    <div class="absolute left-5 top-1/2 -translate-y-1/2 text-zinc-500 group-focus-within:text-cyan-400 transition-colors">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text"
                           value="{{ request()->search ?? '' }}"
                           onchange="const url = new URL(window.location); url.searchParams.set('search', this.value); if (!this.value) url.searchParams.delete('search'); window.location = url;"
                           placeholder="Поиск по названию или slug..."
                           class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 pl-12 pr-4 py-3.5 rounded-2xl text-sm placeholder:text-zinc-500 text-white outline-none transition-all duration-300">
                </div>

                <div class="relative">
                    <select onchange="const url = new URL(window.location); url.searchParams.set('status', this.value); if (!this.value) url.searchParams.delete('status'); window.location = url;"
                            class="appearance-none w-full sm:w-48 bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-5 py-3.5 pr-10 rounded-2xl text-sm text-white outline-none transition-all duration-300 cursor-pointer">
                        <option value="" class="bg-zinc-900">Все статусы</option>
                        <option value="published" {{ request()->status === 'published' ? 'selected' : '' }} class="bg-zinc-900">Опубликовано</option>
                        <option value="draft" {{ request()->status === 'draft' ? 'selected' : '' }} class="bg-zinc-900">Черновик</option>
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-zinc-500">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="bg-zinc-900 rounded-3xl border border-white/5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="border-b border-white/10 bg-zinc-950/50">
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Проект</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Статус</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Язык</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Сложность</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">XP</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">GitLab</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold text-right">Действия</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                    @forelse($projects ?? [] as $project)
                        <tr class="hover:bg-white/5 transition-colors duration-200 group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-violet-500/10 flex items-center justify-center shrink-0 border border-violet-500/20">
                                        <i data-lucide="folder-code" class="w-5 h-5 text-violet-400"></i>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-white text-[15px] block mb-0.5">{{ $project->title ?? 'Название проекта' }}</span>
                                        <p class="text-xs text-zinc-500 font-mono">{{ $project->slug ?? 'project-slug' }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                @if($project->is_published ?? true)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Published
                                </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-zinc-500/10 text-zinc-400 border border-zinc-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span> Draft
                                </span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-zinc-300">{{ ucfirst($project->language ?? '—') }}</span>
                            </td>

                            <td class="px-6 py-4">
                                @php
                                    $diffColors = [
                                        'beginner' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
                                        'intermediate' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                        'advanced' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
                                        'expert' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                    ];
                                    $diffClass = $diffColors[$project->difficulty ?? 'beginner'] ?? $diffColors['beginner'];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $diffClass }}">
                                {{ ucfirst($project->difficulty ?? 'beginner') }}
                            </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-sm font-mono font-bold text-amber-400">{{ $project->xp_reward ?? 0 }}</span>
                            </td>

                            <td class="px-6 py-4">
                                @php $sync = $project->gitlab_sync_status ?? 'pending'; @endphp
                                @if($sync === 'synced' && $project->gitlab_project_id)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded-full border border-emerald-500/20">
                                        <i data-lucide="git-merge" class="w-3.5 h-3.5"></i> Synced
                                    </span>
                                @elseif($sync === 'pending')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-full border border-amber-500/20">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i> Pending
                                    </span>
                                @elseif($sync === 'failed')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-400 bg-rose-500/10 px-2.5 py-1 rounded-full border border-rose-500/20" title="GitLab sync failed — check logs">
                                        <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> Failed
                                    </span>
                                @else
                                    <span class="text-xs font-semibold text-zinc-500 bg-white/5 px-2 py-1 rounded-full border border-white/5">
                                        No
                                    </span>
                                @endif
                                @if($project->gitlab_project_id)
                                    <div class="text-[10px] text-zinc-500 font-mono mt-1">#{{ $project->gitlab_project_id }}</div>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <a href="{{ route('admin.projects.tests', $project->id ?? 1) }}" class="p-2 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-300 hover:text-cyan-200 transition-colors" title="Autotests">
                                        <i data-lucide="flask-conical" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('admin.projects.edit', $project->id ?? 1) }}" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white transition-colors" title="Edit">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.projects.destroy', $project->id ?? 1) }}" onsubmit="return confirm('Delete project?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 transition-colors" title="Delete">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="mx-auto w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mb-4">
                                    <i data-lucide="folder-kanban" class="w-8 h-8 text-zinc-500"></i>
                                </div>
                                <p class="text-lg font-medium text-zinc-300 mb-1">Проекты не найдены</p>
                                <p class="text-sm text-zinc-500 mb-4">Здесь пока ничего нет. Создайте свой первый проект.</p>
                                <a href="{{ route('admin.projects.create') }}" class="inline-flex items-center gap-2 text-cyan-400 font-medium hover:text-cyan-300 transition-colors">
                                    Создать проект <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($projects) && $projects->hasPages())
                <div class="px-6 py-4 border-t border-white/5 bg-zinc-950/30">
                    {{ $projects->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
