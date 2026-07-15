@extends('admin.layouts.app')

@section('title', 'Edit Module')

@section('content')
    <div class="max-w-4xl space-y-8 anim-up" style="animation-delay: 0.1s">

        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.courses.modules.index', $course ?? 1) }}" class="w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition-all duration-300">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-white">Edit Module</h1>
                <p class="text-zinc-400 mt-1 font-medium text-sm">{{ $module->title ?? 'Название модуля' }}</p>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('admin.courses.modules.update', [$course ?? 1, $module ?? 1]) }}" class="bg-zinc-900 rounded-3xl border border-white/5 divide-y divide-white/5 shadow-xl shadow-black/20">
            @method('PUT')
            @csrf

            <div class="p-8 space-y-6">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-1 h-5 bg-cyan-400 rounded-full"></div>
                    <h2 class="text-lg font-semibold text-white tracking-wide">Module Info</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Title</label>
                        <input type="text" name="title" value="{{ old('title', $module->title ?? '') }}" required
                               class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                        @error('title')<p class="text-xs text-rose-400 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $module->slug ?? '') }}" required
                               class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 font-mono">
                        @error('slug')<p class="text-xs text-rose-400 font-medium">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 resize-none">{{ old('description', $module->description ?? '') }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-end">
                    <div class="space-y-2">
                        <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Order Position</label>
                        <input type="number" name="order_position" value="{{ old('order_position', $module->order_position ?? 0) }}" min="0"
                               class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                    </div>

                    <div class="pt-4">
                        <label class="flex items-center gap-4 p-4 rounded-2xl border border-white/5 bg-zinc-950/50 hover:bg-white/5 transition-colors cursor-pointer group">
                            <div class="relative flex items-center">
                                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $module->is_published ?? false) ? 'checked' : '' }} class="peer sr-only">
                                <div class="w-10 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </div>
                            <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">Published</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="p-8 flex items-center gap-4 justify-end bg-zinc-950/30 rounded-b-3xl">
                <a href="{{ route('admin.courses.modules.index', $course ?? 1) }}" class="px-6 py-3 text-sm font-semibold text-zinc-400 hover:text-white transition-colors">
                    Отмена
                </a>
                <button type="submit" class="flex items-center gap-2 px-8 py-3 text-sm font-semibold text-black bg-white rounded-2xl hover:bg-white/90 transition-all active:scale-95 shadow-lg shadow-white/10">
                    <i data-lucide="save" class="w-4 h-4"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
@endsection
