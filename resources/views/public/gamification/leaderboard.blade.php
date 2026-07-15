@extends('public.layouts.app')

@section('title', 'Leaderboard')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Leaderboard</h1>
            <p class="text-gray-500 dark:text-dark-400 mt-1">Рейтинг студентов по заработанным XP</p>
        </div>

        <!-- Period Tabs -->
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-dark-800 rounded-lg p-1">
            @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'all_time' => 'All Time'] as $period => $label)
            <a href="{{ route('gamification.leaderboard', ['period' => $period]) }}"
               class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors
                   {{ $period === $period ? 'bg-white dark:bg-dark-700 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-500 dark:text-dark-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    <!-- Top 3 Podium -->
    @php $top3 = $leaderboard->take(3); @endphp
    @if($top3->count() > 0)
    <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 overflow-hidden">
        <div class="p-6 grid grid-cols-3 gap-4">
            @php
                $sorted = $top3->sortByDesc(function ($entry) { return $entry->xp_earned ?? $entry->projects_completed ?? 0; });
                $rankMap = $leaderboard->pluck('rank', 'user_id');
            @endphp
            <!-- 2nd Place -->
            @php $second = $sorted->skip(1)->first(); @endphp
            @if($second)
            <div class="text-center pt-8">
                <div class="w-16 h-16 rounded-full bg-gray-300 dark:bg-dark-600 flex items-center justify-center text-white text-2xl font-bold mx-auto mb-2">
                    {{ substr($second->user->name ?? '?', 0, 1) }}
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">{{ $second->user->name ?? 'Unknown' }}</h3>
                <p class="text-xs text-gray-500 dark:text-dark-400">{{ $second->user->username ?? '' }}</p>
                <p class="text-lg font-bold text-gray-600 dark:text-dark-300 mt-1">
                    {{ $second->xp_earned ?? $second->projects_completed ?? 0 }}
                </p>
            </div>
            @endif
            <!-- 1st Place -->
            @php $first = $sorted->first(); @endphp
            @if($first)
            <div class="text-center -mt-4">
                <div class="text-3xl mb-1">🥇</div>
                <div class="w-20 h-20 rounded-full bg-yellow-400 flex items-center justify-center text-white text-2xl font-bold mx-auto mb-2 border-4 border-yellow-200">
                    {{ substr($first->user->name ?? '?', 0, 1) }}
                </div>
                <h3 class="font-bold text-gray-900 dark:text-gray-100">{{ $first->user->name ?? 'Unknown' }}</h3>
                <p class="text-xs text-gray-500 dark:text-dark-400">{{ $first->user->username ?? '' }}</p>
                <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                    {{ $first->xp_earned ?? $first->projects_completed ?? 0 }}
                </p>
            </div>
            @endif
            <!-- 3rd Place -->
            @php $third = $sorted->last(); @endphp
            @if($third)
            <div class="text-center pt-4">
                <div class="w-14 h-14 rounded-full bg-orange-400 flex items-center justify-center text-white text-xl font-bold mx-auto mb-2">
                    {{ substr($third->user->name ?? '?', 0, 1) }}
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">{{ $third->user->name ?? 'Unknown' }}</h3>
                <p class="text-xs text-gray-500 dark:text-dark-400">{{ $third->user->username ?? '' }}</p>
                <p class="text-lg font-bold text-orange-600 dark:text-orange-400 mt-1">
                    {{ $third->xp_earned ?? $third->projects_completed ?? 0 }}
                </p>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Full Table -->
    <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-dark-850 border-y border-gray-200 dark:border-dark-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-dark-400 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-dark-400 uppercase">Студент</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-dark-400 uppercase">XP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-700">
                    @forelse($leaderboard as $entry)
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-850 transition-colors
                        {{ auth()->id() === $entry->user_id ? 'bg-brand-50 dark:bg-brand-900/10' : '' }}">
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-400">
                            @if($entry->rank <= 3)
                            <span class="text-lg">{{ match($entry->rank) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' } }}</span>
                            @else
                            {{ $entry->rank }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full {{ auth()->id() === $entry->user_id ? 'bg-brand-500' : 'bg-gray-300 dark:bg-dark-600' }} flex items-center justify-center text-white text-sm font-semibold">
                                    {{ substr($entry->user->name ?? '?', 0, 1) }}
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $entry->user->name ?? 'Unknown' }}</span>
                                    <p class="text-xs text-gray-500 dark:text-dark-400">{{ $entry->user->username ?? '' }}</p>
                                </div>
                                @if(auth()->id() === $entry->user_id)
                                <span class="text-xs bg-brand-100 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 px-2 py-0.5 rounded-full">Вы</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-semibold text-brand-600 dark:text-brand-400">
                                {{ $entry->xp_earned ?? $entry->projects_completed ?? 0 }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-4 py-12 text-center text-gray-500 dark:text-dark-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-dark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <p class="text-lg font-medium">Нет данных</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
