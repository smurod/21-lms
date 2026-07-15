@extends('admin.layouts.app')

@section('title', 'Edit Project')

@section('content')
    <div x-data="{
    isPublished: {{ ($project->is_published ?? false) ? 'true' : 'false' }},
    hasTests: {{ ($project->has_automated_tests ?? false) ? 'true' : 'false' }},
    requiresReview: {{ ($project->requires_peer_review ?? false) ? 'true' : 'false' }},
    isMandatory: {{ ($project->is_mandatory ?? false) ? 'true' : 'false' }},
    loading: false,
    submit() { this.loading = true; }
}" class="max-w-4xl space-y-8 anim-up" style="animation-delay: 0.1s">

        <!-- Page Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.projects.index') }}" class="w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition-all duration-300">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-white">Редактировать проект</h1>
                <p class="text-zinc-400 mt-1 font-medium text-sm">{{ $project->title ?? 'Название проекта' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.projects.update', $project ?? 1) }}" @submit="submit" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main Info (Left Column) -->
                <div class="lg:col-span-2 space-y-6">

                    <div class="bg-zinc-900 rounded-3xl border border-white/5 p-8 space-y-6 shadow-xl shadow-black/20">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-1 h-5 bg-cyan-400 rounded-full"></div>
                            <h2 class="text-lg font-semibold text-white tracking-wide">Основная информация</h2>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Название</label>
                            <input type="text" name="title" required value="{{ $project->title ?? '' }}"
                                   class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Slug</label>
                            <input type="text" name="slug" required value="{{ $project->slug ?? '' }}"
                                   oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '')"
                                   class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 font-mono">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Описание</label>
                            <textarea name="description" required rows="3"
                                      class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 resize-none">{{ $project->description ?? '' }}</textarea>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Инструкция</label>
                            <textarea name="instructions" required rows="6"
                                      class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 resize-y font-mono">{{ $project->instructions ?? '' }}</textarea>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Подсказки (hints)</label>
                            <textarea name="hints" rows="4"
                                      class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 resize-y font-mono">{{ $project->hints ?? '' }}</textarea>
                        </div>
                    </div>

                </div>

                <!-- Settings (Right Column) -->
                <div class="space-y-6">

                    <div class="bg-zinc-900 rounded-3xl border border-white/5 p-8 space-y-6 shadow-xl shadow-black/20">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-1 h-5 bg-amber-400 rounded-full"></div>
                            <h2 class="text-lg font-semibold text-white tracking-wide">Настройки</h2>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Сложность</label>
                            <div class="relative">
                                <select name="difficulty" required class="appearance-none w-full bg-zinc-950 border border-white/10 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 px-4 py-3 pr-10 rounded-xl text-sm text-white outline-none transition-all duration-300 cursor-pointer">
                                    @foreach(['beginner','intermediate','advanced','expert'] as $d)
                                        <option value="{{ $d }}" {{ ($project->difficulty ?? '') === $d ? 'selected' : '' }} class="bg-zinc-900">
                                            {{ ucfirst($d) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-zinc-500">
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Язык</label>
                            <input type="text" name="language" required value="{{ $project->language ?? '' }}"
                                   class="w-full bg-zinc-950 border border-white/10 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Версия языка</label>
                            <input type="text" name="language_version" value="{{ $project->language_version ?? '' }}"
                                   class="w-full bg-zinc-950 border border-white/10 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">XP Reward</label>
                            <input type="number" name="xp_reward" min="0" value="{{ $project->xp_reward ?? 100 }}"
                                   class="w-full bg-zinc-950 border border-white/10 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 font-mono">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Passing Score</label>
                            <div class="relative">
                                <input type="number" name="passing_score" min="0" max="100" value="{{ $project->passing_score ?? 70 }}"
                                       class="w-full bg-zinc-950 border border-white/10 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 px-4 py-3 pr-8 rounded-xl text-sm text-white outline-none transition-all duration-300 font-mono">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-zinc-500 font-mono text-sm">%</div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Estimated Hours</label>
                            <input type="number" name="estimated_hours" min="1" value="{{ $project->estimated_hours ?? '' }}"
                                   class="w-full bg-zinc-950 border border-white/10 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 font-mono">
                        </div>
                    </div>

                    <!-- Toggles Options -->
                    <div class="bg-zinc-900 rounded-3xl border border-white/5 p-8 space-y-6 shadow-xl shadow-black/20">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-5 bg-emerald-400 rounded-full"></div>
                            <h2 class="text-lg font-semibold text-white tracking-wide">Опции</h2>
                        </div>

                        <label class="flex items-center justify-between cursor-pointer group">
                            <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">Опубликовать</span>
                            <div class="relative flex items-center">
                                <input type="checkbox" name="is_published" value="1" x-model="isPublished" class="peer sr-only">
                                <div class="w-10 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </div>
                        </label>

                        <label class="flex items-center justify-between cursor-pointer group">
                            <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">Автотесты</span>
                            <div class="relative flex items-center">
                                <input type="checkbox" name="has_automated_tests" value="1" x-model="hasTests" class="peer sr-only">
                                <div class="w-10 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-500"></div>
                            </div>
                        </label>

                        <label class="flex items-center justify-between cursor-pointer group">
                            <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">P2P ревью</span>
                            <div class="relative flex items-center">
                                <input type="checkbox" name="requires_peer_review" value="1" x-model="requiresReview" class="peer sr-only">
                                <div class="w-10 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-500"></div>
                            </div>
                        </label>

                        <label class="flex items-center justify-between cursor-pointer group">
                            <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">Обязательный</span>
                            <div class="relative flex items-center">
                                <input type="checkbox" name="is_mandatory" value="1" x-model="isMandatory" class="peer sr-only">
                                <div class="w-10 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-500"></div>
                            </div>
                        </label>
                    </div>

                    @if(isset($project->repository_url) && $project->repository_url)
                        <div class="bg-zinc-900 rounded-3xl border border-white/5 p-6 space-y-2 shadow-xl shadow-black/20 text-center">
                            <p class="text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">GitLab Repository</p>
                            <a href="{{ $project->repository_url }}" target="_blank" class="text-sm text-cyan-400 hover:text-cyan-300 transition-colors truncate block">
                                {{ $project->repository_url }}
                            </a>
                            <p class="text-xs font-mono text-zinc-500">ID: {{ $project->gitlab_project_id }}</p>
                        </div>
                    @endif

                    <!-- Submit Area -->
                    <div class="bg-zinc-900 rounded-3xl border border-white/5 p-8 space-y-4 shadow-xl shadow-black/20">
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-2 px-6 py-4 bg-white text-black rounded-2xl text-sm font-bold hover:scale-[1.02] active:scale-95 transition-all shadow-lg shadow-white/10 disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="loading">
                            <span x-show="!loading" class="flex items-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> Сохранить изменения</span>
                            <span x-show="loading" x-cloak class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Saving...
                        </span>
                        </button>
                        <a href="{{ route('admin.projects.index') }}" class="block text-center text-sm font-semibold text-zinc-500 hover:text-white transition-colors py-2">
                            Отмена
                        </a>
                    </div>

                </div>
            </div>
        </form>
    </div>
@endsection
