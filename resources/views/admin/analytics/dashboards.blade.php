@extends('admin.layouts.app')

@section('title', 'Мои dashboards — AI Analytics')

@section('content')
    <div class="mx-auto max-w-7xl space-y-7 anim-up" style="animation-delay: .1s">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-mono font-semibold uppercase tracking-[.18em] text-cyan-300">
                    <i data-lucide="layout-dashboard" class="h-3.5 w-3.5"></i>
                    AI Analytics
                </div>
                <h1 class="text-3xl font-semibold tracking-tighter text-white">Список dashboards</h1>
                <p class="mt-1 text-sm text-zinc-500">Ваши сохранённые DataLens dashboards и запросы, которыми они были созданы.</p>
            </div>
            <a href="{{ route('admin.analytics.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-zinc-950 transition-colors hover:bg-cyan-200">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Новый dashboard
            </a>
        </div>

        @if ($dashboards->isEmpty())
            <div class="rounded-3xl border border-white/5 bg-zinc-900/60 px-8 py-20 text-center shadow-xl shadow-black/20">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-3xl bg-cyan-400/10">
                    <i data-lucide="chart-no-axes-combined" class="h-7 w-7 text-cyan-300"></i>
                </div>
                <h2 class="mt-5 text-xl font-semibold text-white">Dashboards пока нет</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-zinc-500">Создайте первый dashboard обычным текстовым запросом — AI подготовит SQL и соберёт визуализации в DataLens.</p>
                <a href="{{ route('admin.analytics.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-2xl border border-cyan-400/25 bg-cyan-400/10 px-4 py-3 text-sm font-semibold text-cyan-200 transition-colors hover:bg-cyan-400/20">
                    <i data-lucide="sparkles" class="h-4 w-4"></i>
                    Создать первый dashboard
                </a>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($dashboards as $dashboard)
                    @php $chartCount = count($dashboard->charts ?? []); @endphp
                    <a href="{{ route('admin.analytics.index', ['dashboard' => $dashboard->id]) }}"
                       class="group flex min-h-56 flex-col rounded-3xl border border-white/5 bg-zinc-900/70 p-6 shadow-lg shadow-black/10 transition-all hover:-translate-y-1 hover:border-cyan-400/25 hover:bg-zinc-900">
                        <div class="flex items-start justify-between gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-cyan-400/10 text-cyan-200 transition-colors group-hover:bg-cyan-400/20">
                                <i data-lucide="chart-no-axes-combined" class="h-5 w-5"></i>
                            </span>
                            <span class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-emerald-300">
                                {{ $dashboard->status === 'ready' ? 'готов' : $dashboard->status }}
                            </span>
                        </div>
                        <h2 class="mt-5 line-clamp-2 text-lg font-semibold text-white">{{ $dashboard->title }}</h2>
                        <p class="mt-2 line-clamp-3 text-sm leading-6 text-zinc-500">{{ $dashboard->prompt }}</p>
                        <div class="mt-auto flex items-center justify-between border-t border-white/5 pt-5 text-xs text-zinc-500">
                            <span class="inline-flex items-center gap-1.5"><i data-lucide="bar-chart-3" class="h-3.5 w-3.5"></i>{{ $chartCount }} {{ trans_choice('график|графика|графиков', $chartCount) }}</span>
                            <span>{{ optional($dashboard->created_at)->format('d.m.Y') }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
