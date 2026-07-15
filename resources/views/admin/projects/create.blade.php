@extends('admin.layouts.app')

@section('title', 'New Project')

@section('content')
    <div x-data="{
    isPublished: false,
    hasTests: false,
    requiresReview: true,
    isMandatory: false,
    loading: false,
    submit() {
        this.loading = true;
    }
}" class="max-w-4xl space-y-8 anim-up" style="animation-delay: 0.1s">

        <!-- Page Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.projects.index') }}" class="w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition-all duration-300">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-white">Новый проект</h1>
                <p class="text-zinc-400 mt-1 font-medium text-sm">Создание учебного проекта с GitLab интеграцией</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.projects.store') }}" @submit="submit" class="space-y-6">
            @csrf

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
                            <input type="text" name="title" required placeholder="Название проекта"
                                   class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Slug</label>
                            <input type="text" name="slug" required placeholder="my-awesome-project"
                                   oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '')"
                                   class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 font-mono">
                            <p class="text-[11px] text-zinc-500 font-mono mt-1.5">Только строчные буквы, цифры и дефис</p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Описание</label>
                            <textarea name="description" required rows="3" placeholder="Краткое описание проекта"
                                      class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 resize-none"></textarea>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Инструкция</label>
                            <textarea name="instructions" required rows="6" placeholder="Подробная инструкция для студентов"
                                      class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 resize-y font-mono"></textarea>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Подсказки (hints)</label>
                            <textarea name="hints" rows="4" placeholder="Подсказки для решения (опционально)"
                                      class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 resize-y font-mono"></textarea>
                        </div>
                    </div>

                    <!-- Course & Module -->
                    <div class="bg-zinc-900 rounded-3xl border border-white/5 p-8 space-y-6 shadow-xl shadow-black/20">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-1 h-5 bg-violet-400 rounded-full"></div>
                            <h2 class="text-lg font-semibold text-white tracking-wide">Курс и модуль</h2>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Курс ID</label>
                                <input type="number" name="course_id" min="1" placeholder="Опционально"
                                       class="w-full bg-zinc-950 border border-white/10 focus:border-violet-500/50 focus:ring-1 focus:ring-violet-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Модуль ID</label>
                                <input type="number" name="module_id" min="1" placeholder="Опционально"
                                       class="w-full bg-zinc-950 border border-white/10 focus:border-violet-500/50 focus:ring-1 focus:ring-violet-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                            </div>
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
                                    <option value="beginner" class="bg-zinc-900">Beginner</option>
                                    <option value="intermediate" class="bg-zinc-900">Intermediate</option>
                                    <option value="advanced" class="bg-zinc-900">Advanced</option>
                                    <option value="expert" class="bg-zinc-900">Expert</option>
                                </select>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-zinc-500">
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Язык</label>
                            <input type="text" name="language" required placeholder="python"
                                   class="w-full bg-zinc-950 border border-white/10 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Версия языка</label>
                            <input type="text" name="language_version" placeholder="3.12"
                                   class="w-full bg-zinc-950 border border-white/10 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">XP Reward</label>
                            <input type="number" name="xp_reward" min="0" value="100"
                                   class="w-full bg-zinc-950 border border-white/10 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 font-mono">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Passing Score</label>
                            <div class="relative">
                                <input type="number" name="passing_score" min="0" max="100" value="70"
                                       class="w-full bg-zinc-950 border border-white/10 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/50 px-4 py-3 pr-8 rounded-xl text-sm text-white outline-none transition-all duration-300 font-mono">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-zinc-500 font-mono text-sm">%</div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Estimated Hours</label>
                            <input type="number" name="estimated_hours" min="1" placeholder="8"
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

                    <!-- Submit Area -->
                    <div class="bg-zinc-900 rounded-3xl border border-white/5 p-8 space-y-4 shadow-xl shadow-black/20">
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-2 px-6 py-4 bg-white text-black rounded-2xl text-sm font-bold hover:scale-[1.02] active:scale-95 transition-all shadow-lg shadow-white/10 disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="loading">
                            <span x-show="!loading" class="flex items-center gap-2"><i data-lucide="check" class="w-4 h-4"></i> Создать проект</span>
                            <span x-show="loading" x-cloak class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Creating...
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
