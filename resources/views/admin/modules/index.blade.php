@extends('admin.layouts.app')

@section('title', 'Modules')

@section('content')
    <div class="space-y-8 anim-up" style="animation-delay: 0.1s">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.courses.index') }}" class="w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition-all duration-300">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight text-white">Modules</h1>
                    <p class="text-sm font-medium mt-1">Course: <span class="text-cyan-400">{{ $course->title ?? 'Название курса' }}</span></p>
                </div>
            </div>

            <a href="{{ route('admin.courses.modules.create', $course ?? 1) }}" class="flex items-center gap-2 bg-white text-black px-6 py-3 rounded-2xl text-sm font-semibold hover:bg-white/90 transition-all duration-300 active:scale-95 shadow-lg shadow-white/10">
                <i data-lucide="plus" class="w-4 h-4"></i> Новый модуль
            </a>
        </div>

        <!-- Modules List -->
        <div class="bg-zinc-900 rounded-3xl border border-white/5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="border-b border-white/10 bg-zinc-950/50">
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold w-16">#</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Модуль</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Статус</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Уроки</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold">Projects</th>
                        <th class="px-6 py-5 text-xs font-mono tracking-widest text-zinc-500 uppercase font-semibold text-right">Действия</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                    @forelse($modules ?? [] as $index => $module)
                        <tr class="hover:bg-white/5 transition-colors duration-200 group">
                            <td class="px-6 py-4 text-sm font-mono text-zinc-500">{{ $index + 1 }}</td>

                            <td class="px-6 py-4">
                                <a href="{{ route('admin.modules.lessons.index', $module->id ?? 1) }}" class="font-semibold text-white hover:text-cyan-400 transition-colors text-[15px] block mb-0.5">
                                    {{ $module->title ?? 'Название модуля' }}
                                </a>
                                <p class="text-xs text-zinc-500 font-mono mb-1">{{ $module->slug ?? 'module-slug' }}</p>
                                @if(isset($module->description) && $module->description)
                                    <p class="text-xs text-zinc-400 line-clamp-1">{{ Str::limit($module->description, 60) }}</p>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                @if($module->is_published ?? true)
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
                                <span class="text-sm font-medium text-zinc-300 tabular-nums">{{ $module->lessons_count ?? 0 }}</span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-zinc-300 tabular-nums">{{ $module->projects_count ?? 0 }}</span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <a href="{{ route('admin.modules.lessons.index', $module->id ?? 1) }}" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white transition-colors" title="Lessons">
                                        <i data-lucide="layers" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('admin.courses.modules.edit', [$course ?? 1, $module->id ?? 1]) }}" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white transition-colors" title="Edit">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.courses.modules.destroy', [$course ?? 1, $module->id ?? 1]) }}" onsubmit="return confirm('Delete module?')" class="inline">
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
                                    <i data-lucide="folder-x" class="w-8 h-8 text-zinc-500"></i>
                                </div>
                                <p class="text-lg font-medium text-zinc-300 mb-1">Модули не найдены</p>
                                <a href="{{ route('admin.courses.modules.create', $course ?? 1) }}" class="inline-flex items-center gap-2 text-cyan-400 font-medium hover:text-cyan-300 transition-colors mt-2">
                                    Создать первый модуль <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
