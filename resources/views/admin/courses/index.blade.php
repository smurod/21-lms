@extends('admin.layouts.app')

@section('title', 'Courses')

@section('content')
    <div class="space-y-8 anim-up" style="animation-delay: 0.1s">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-4xl font-semibold tracking-tighter text-white">Courses</h1>
                <p class="text-zinc-400 mt-1 font-medium">Управление учебными курсами и направлениями</p>
            </div>

            <a href="{{ route('admin.courses.create') }}" class="flex items-center gap-2 bg-white text-black px-6 py-3 rounded-2xl text-sm font-semibold hover:bg-white/90 transition-all duration-300 active:scale-95 shadow-lg shadow-white/10">
                <i data-lucide="plus" class="w-4 h-4"></i> Новый курс
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

        <!-- Courses Table -->
        <div class="bg-zinc-900 rounded-3xl border border-white/5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="border-b border-white/10 bg-zinc-950/50">
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Курс</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Статус</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Сложность</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Часы</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Модули</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold text-right">Действия</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">

                    @forelse($courses ?? [['id'=>1, 'title'=>'Работа со строками в C', 'slug'=>'c-strings', 'is_published'=>true, 'is_mandatory'=>true, 'difficulty'=>'beginner', 'estimated_hours'=>24, 'modules_count'=>4]] as $course)
                        <!-- Demo row fallback for preview -->
                        @php $course = (object) $course; @endphp

                        <tr class="hover:bg-white/5 transition-colors duration-200 group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    @if(isset($course->cover_image) && $course->cover_image)
                                        <img src="{{ asset('storage/' . $course->cover_image) }}" class="w-12 h-12 rounded-xl object-cover ring-2 ring-white/10">
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-violet-500/10 flex items-center justify-center ring-1 ring-violet-500/20">
                                            <i data-lucide="book-open" class="w-5 h-5 text-violet-400"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.courses.edit', $course->id ?? 1) }}" class="font-semibold text-white hover:text-cyan-400 transition-colors text-[15px]">
                                            {{ $course->title }}
                                        </a>
                                        <div class="text-xs text-zinc-500 font-mono mt-0.5">{{ $course->slug }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if($course->is_published)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Published
                                    </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-zinc-500/10 text-zinc-400 border border-zinc-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span> Draft
                                    </span>
                                    @endif

                                    @if($course->is_mandatory ?? false)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase bg-violet-500/10 text-violet-400 border border-violet-500/20">
                                        Mandatory
                                    </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                @php
                                    $diffColors = [
                                        'beginner' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
                                        'intermediate' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                        'advanced' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
                                        'expert' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                    ];
                                    $diffClass = $diffColors[$course->difficulty] ?? $diffColors['beginner'];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $diffClass }}">
                                {{ ucfirst($course->difficulty) }}
                            </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-zinc-300 tabular-nums">{{ $course->estimated_hours ?? '—' }}h</span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-zinc-300 tabular-nums">{{ $course->modules_count ?? 0 }}</span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <a href="{{ route('admin.courses.modules.index', $course->id ?? 1) }}" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white transition-colors" title="Modules">
                                        <i data-lucide="layers" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('admin.courses.edit', $course->id ?? 1) }}" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white transition-colors" title="Edit">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.courses.destroy', $course->id ?? 1) }}" onsubmit="return confirm('Delete course?')" class="inline">
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
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="mx-auto w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mb-4">
                                    <i data-lucide="book-x" class="w-8 h-8 text-zinc-500"></i>
                                </div>
                                <p class="text-lg font-medium text-zinc-300 mb-1">Курсы не найдены</p>
                                <p class="text-sm text-zinc-500 mb-4">Здесь пока ничего нет. Создайте свой первый курс.</p>
                                <a href="{{ route('admin.courses.create') }}" class="inline-flex items-center gap-2 text-cyan-400 font-medium hover:text-cyan-300 transition-colors">
                                    Создать курс <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($courses) && $courses->hasPages())
                <div class="px-6 py-4 border-t border-white/5 bg-zinc-950/30">
                    {{ $courses->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
