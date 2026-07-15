@extends('admin.layouts.app')

@section('title', 'Edit Lesson')

@section('content')
    <div class="max-w-4xl space-y-8 anim-up" style="animation-delay: 0.1s">

        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.modules.lessons.index', $module ?? 1) }}" class="w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition-all duration-300">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-white">Edit Lesson</h1>
                <p class="text-zinc-400 mt-1 font-medium text-sm">{{ $lesson->title ?? 'Название урока' }}</p>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('admin.modules.lessons.update', [$module ?? 1, $lesson ?? 1]) }}" class="bg-zinc-900 rounded-3xl border border-white/5 divide-y divide-white/5 shadow-xl shadow-black/20">
            @method('PUT')
            @csrf

            <div class="p-8 space-y-6">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-1 h-5 bg-cyan-400 rounded-full"></div>
                    <h2 class="text-lg font-semibold text-white tracking-wide">Lesson Info</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Title</label>
                        <input type="text" name="title" value="{{ old('title', $lesson->title ?? '') }}" required
                               class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                        @error('title')<p class="text-xs text-rose-400 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $lesson->slug ?? '') }}" required
                               class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 font-mono">
                        @error('slug')<p class="text-xs text-rose-400 font-medium">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Content Type</label>
                    <div class="relative">
                        <select name="content_type" id="content_type" required class="appearance-none w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 pr-10 rounded-xl text-sm text-white outline-none transition-all duration-300 cursor-pointer">
                            @foreach(['text' => 'Text (Markdown)', 'video' => 'Video', 'interactive' => 'Interactive'] as $val => $label)
                                <option value="{{ $val }}" {{ old('content_type', $lesson->content_type ?? 'text') === $val ? 'selected' : '' }} class="bg-zinc-900">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-zinc-500">
                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>

                <div id="video_url_field" class="{{ ($lesson->content_type ?? 'text') === 'video' ? '' : 'hidden' }} space-y-2 anim-fade">
                    <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Video URL</label>
                    <input type="url" name="video_url" value="{{ old('video_url', $lesson->video_url ?? '') }}"
                           class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300"
                           placeholder="https://youtube.com/embed/...">
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Content (Markdown)</label>
                    <textarea name="content" rows="12" required
                              class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300 font-mono resize-y">{{ old('content', $lesson->content ?? '') }}</textarea>
                    @error('content')<p class="text-xs text-rose-400 font-medium">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Estimated Minutes</label>
                        <input type="number" name="estimated_minutes" value="{{ old('estimated_minutes', $lesson->estimated_minutes ?? '') }}" min="1"
                               class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Order Position</label>
                        <input type="number" name="order_position" value="{{ old('order_position', $lesson->order_position ?? 0) }}" min="0"
                               class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none transition-all duration-300">
                    </div>
                </div>

                <!-- Toggles -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                    <label class="flex items-center gap-4 p-4 rounded-2xl border border-white/5 bg-zinc-950/50 hover:bg-white/5 transition-colors cursor-pointer group">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $lesson->is_published ?? false) ? 'checked' : '' }} class="peer sr-only">
                            <div class="w-10 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        </div>
                        <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">Published</span>
                    </label>

                    <label class="flex items-center gap-4 p-4 rounded-2xl border border-white/5 bg-zinc-950/50 hover:bg-white/5 transition-colors cursor-pointer group">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="is_free" value="1" {{ old('is_free', $lesson->is_free ?? false) ? 'checked' : '' }} class="peer sr-only">
                            <div class="w-10 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-500"></div>
                        </div>
                        <span class="text-sm font-medium text-zinc-300 group-hover:text-white transition-colors">Free (preview)</span>
                    </label>
                </div>
            </div>

            <div class="p-8 flex items-center gap-4 justify-end bg-zinc-950/30 rounded-b-3xl">
                <a href="{{ route('admin.modules.lessons.index', $module ?? 1) }}" class="px-6 py-3 text-sm font-semibold text-zinc-400 hover:text-white transition-colors">
                    Отмена
                </a>
                <button type="submit" class="flex items-center gap-2 px-8 py-3 text-sm font-semibold text-black bg-white rounded-2xl hover:bg-white/90 transition-all active:scale-95 shadow-lg shadow-white/10">
                    <i data-lucide="save" class="w-4 h-4"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var contentType = document.getElementById('content_type');
            var videoField = document.getElementById('video_url_field');
            function toggleVideo() {
                if (contentType.value === 'video') {
                    videoField.classList.remove('hidden');
                } else {
                    videoField.classList.add('hidden');
                }
            }
            contentType.addEventListener('change', toggleVideo);
            // Инициализация при загрузке (на случай если old или $lesson уже video)
            toggleVideo();
        });
    </script>
@endsection
