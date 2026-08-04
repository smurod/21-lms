@extends('admin.layouts.app')

@section('title', 'Autotests — ' . $project->title)

@section('content')
    <div class="max-w-7xl space-y-8 anim-up" style="animation-delay: 0.1s">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.projects.edit', $project->id) }}" class="w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition-all duration-300">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight text-white">Autotests</h1>
                    <p class="text-zinc-400 mt-1 font-medium text-sm">{{ $project->title }} · {{ $project->tests->count() }} checks</p>
                </div>
            </div>
            <a href="{{ route('public.projects.show', $project) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white transition-colors">
                <i data-lucide="external-link" class="w-4 h-4"></i> Public page
            </a>
        </div>

        @if(session('success'))
            <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-300 px-5 py-4 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 text-rose-300 px-5 py-4 text-sm font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                @forelse($project->tests as $test)
                    <form method="POST" action="{{ route('admin.projects.tests.update', [$project->id, $test->id]) }}" class="bg-zinc-900 rounded-3xl border border-white/5 p-6 shadow-xl shadow-black/20 space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $test->is_hidden ? 'bg-violet-500/10 text-violet-300 border-violet-500/20' : 'bg-cyan-500/10 text-cyan-300 border-cyan-500/20' }}">
                                        {{ $test->is_hidden ? 'hidden' : 'public' }}
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border bg-emerald-500/10 text-emerald-300 border-emerald-500/20">
                                        {{ $test->test_type }} · {{ $test->points }} pts
                                    </span>
                                </div>
                                <h2 class="text-xl font-semibold text-white">{{ $test->name }}</h2>
                                <p class="text-sm text-zinc-500 font-mono">#{{ $test->id }} · order {{ $test->order_position }} · timeout {{ $test->timeout_seconds ?? $project->test_timeout_seconds ?? 120 }}s</p>
                            </div>
                            <button form="delete-test-{{ $test->id }}" type="submit" onclick="return confirm('Delete autotest?')" class="p-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 transition-colors" title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="space-y-2">
                                <span class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Name</span>
                                <input name="name" value="{{ $test->name }}" required class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none">
                            </label>
                            <label class="space-y-2">
                                <span class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Type</span>
                                <select name="test_type" class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none">
                                    @foreach(['unit','integration','performance','style'] as $type)
                                        <option value="{{ $type }}" @selected($test->test_type === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="space-y-2">
                                <span class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Points</span>
                                <input type="number" min="1" max="1000" name="points" value="{{ $test->points }}" required class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none">
                            </label>
                            <label class="space-y-2">
                                <span class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Timeout seconds</span>
                                <input type="number" min="1" max="3600" name="timeout_seconds" value="{{ $test->timeout_seconds }}" placeholder="{{ $project->test_timeout_seconds ?? 120 }}" class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none">
                            </label>
                            <label class="space-y-2">
                                <span class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Order</span>
                                <input type="number" min="0" max="10000" name="order_position" value="{{ $test->order_position }}" class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none">
                            </label>
                            <label class="flex items-center gap-3 pt-8">
                                <input type="checkbox" name="is_hidden" value="1" @checked($test->is_hidden) class="w-4 h-4 rounded bg-zinc-950 border-white/10 text-cyan-500">
                                <span class="text-sm text-zinc-300">Hidden server-side test</span>
                            </label>
                        </div>

                        <label class="space-y-2 block">
                            <span class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Description</span>
                            <textarea name="description" rows="2" class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none resize-y">{{ $test->description }}</textarea>
                        </label>

                        <label class="space-y-2 block">
                            <span class="block text-xs font-mono tracking-widest text-zinc-400 uppercase font-semibold">Test script</span>
                            <textarea name="test_code" rows="12" required class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none resize-y font-mono">{{ $test->test_code }}</textarea>
                        </label>

                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-cyan-500 hover:bg-cyan-400 text-black font-bold transition-colors">
                            <i data-lucide="save" class="w-4 h-4"></i> Save test
                        </button>
                    </form>
                    <form id="delete-test-{{ $test->id }}" method="POST" action="{{ route('admin.projects.tests.destroy', [$project->id, $test->id]) }}" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @empty
                    <div class="bg-zinc-900 rounded-3xl border border-white/5 p-12 text-center">
                        <div class="mx-auto w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mb-4">
                            <i data-lucide="flask-conical" class="w-8 h-8 text-zinc-500"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-white mb-2">No autotests yet</h2>
                        <p class="text-zinc-500">Create the first hidden or public server-side check.</p>
                    </div>
                @endforelse
            </div>

            <aside class="space-y-6">
                <form method="POST" action="{{ route('admin.projects.tests.store', $project->id) }}" class="bg-zinc-900 rounded-3xl border border-white/5 p-6 shadow-xl shadow-black/20 space-y-5 sticky top-6">
                    @csrf
                    <div>
                        <h2 class="text-xl font-semibold text-white">New autotest</h2>
                        <p class="text-sm text-zinc-500 mt-1">Script runs outside the student repo and receives <code class="text-cyan-300">$WORKSPACE</code>.</p>
                    </div>

                    <input name="name" required placeholder="Hidden functional check" class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none">
                    <textarea name="description" rows="2" placeholder="What this check verifies" class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none resize-y"></textarea>

                    <div class="grid grid-cols-2 gap-3">
                        <input type="number" min="1" max="1000" name="points" value="100" required class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none" placeholder="Points">
                        <input type="number" min="0" max="10000" name="order_position" value="10" class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none" placeholder="Order">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <select name="test_type" class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none">
                            <option value="unit">unit</option>
                            <option value="integration" selected>integration</option>
                            <option value="performance">performance</option>
                            <option value="style">style</option>
                        </select>
                        <input type="number" min="1" max="3600" name="timeout_seconds" value="30" class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-sm text-white outline-none" placeholder="Timeout">
                    </div>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="is_hidden" value="1" checked class="w-4 h-4 rounded bg-zinc-950 border-white/10 text-cyan-500">
                        <span class="text-sm text-zinc-300">Hidden server-side test</span>
                    </label>

                    <textarea name="test_code" rows="12" required class="w-full bg-zinc-950 border border-white/10 focus:border-cyan-500/50 px-4 py-3 rounded-xl text-xs text-white outline-none resize-y font-mono">#!/usr/bin/env bash
set -euo pipefail
cd "$WORKSPACE"

# Write assertions here.
echo "PASS: custom check"</textarea>

                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-emerald-400 hover:bg-emerald-300 text-black font-bold transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i> Create test
                    </button>
                </form>
            </aside>
        </div>
    </div>
@endsection
