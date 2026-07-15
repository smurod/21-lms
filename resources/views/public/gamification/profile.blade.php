@extends('public.layouts.app')

@section('title', 'Gamification')

@section('content')
<div class="max-w-4xl space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Your Progress</h1>
        <p class="text-gray-500 dark:text-dark-400 mt-1">Ваш прогресс в геймификации</p>
    </div>

    <!-- XP Progress -->
    <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-6">
            <!-- Level Badge -->
            <div class="text-center">
                @php
                    $currentLevel = 1;
                    $levels = $xpLevels;
                    ksort($levels);
                    foreach ($levels as $lvl => $data) {
                        if ($user->total_xp >= $data['xp_required']) {
                            $currentLevel = $lvl;
                        } else {
                            break;
                        }
                    }
                    $nextLevel = null;
                    foreach ($levels as $lvl => $data) {
                        if ($user->total_xp < $data['xp_required']) {
                            $nextLevel = $lvl;
                            break;
                        }
                    }
                    $levelNames = [
                        1 => 'Beginner',
                        2 => 'Intermediate',
                        3 => 'Advanced',
                        4 => 'Expert',
                        5 => 'Master',
                        6 => 'Grandmaster',
                    ];
                    $levelIcons = [
                        1 => '🌱',
                        2 => '🌿',
                        3 => '🌳',
                        4 => '🔥',
                        5 => '⭐',
                        6 => '👑',
                    ];
                    $currentName = $levelNames[$currentLevel] ?? 'Beginner';
                    $currentIcon = $levelIcons[$currentLevel] ?? '🌱';
                @endphp
                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center text-white text-4xl mx-auto mb-2">
                    {{ $currentIcon }}
                </div>
                <h2 class="font-bold text-gray-900 dark:text-gray-100">{{ $currentName }}</h2>
                <p class="text-sm text-gray-500 dark:text-dark-400">Уровень {{ $currentLevel }}</p>
            </div>

            <!-- XP Bar -->
            <div class="flex-1">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $user->total_xp }} XP</span>
                    @if($nextLevel)
                    <span class="text-sm text-gray-500 dark:text-dark-400">{{ $levels[$nextLevel]['xp_required'] }} XP needed</span>
                    @else
                    <span class="text-sm text-green-600 dark:text-green-400 font-medium">MAX LEVEL</span>
                    @endif
                </div>
                <div class="w-full bg-gray-200 dark:bg-dark-700 rounded-full h-4 overflow-hidden">
                    @php
                        $progress = 0;
                        if ($nextLevel && isset($levels[$nextLevel]) && isset($levels[$currentLevel])) {
                            $currentXp = $levels[$currentLevel]['xp_required'] ?? 0;
                            $nextXp = $levels[$nextLevel]['xp_required'] ?? ($currentXp + 1);
                            $progress = $currentXp < $nextXp
                                ? (($user->total_xp - $currentXp) / ($nextXp - $currentXp)) * 100
                                : 100;
                        }
                    @endphp
                    <div class="h-4 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 transition-all" style="width: {{ min(max($progress, 0), 100) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-4 text-center">
            <div class="text-2xl font-bold text-brand-600 dark:text-brand-400">{{ $stats->projects_started ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-dark-400">Started</div>
        </div>
        <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-4 text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats->projects_completed ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-dark-400">Completed</div>
        </div>
        <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-4 text-center">
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $stats->reviews_given ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-dark-400">Reviews</div>
        </div>
        <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-4 text-center">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats->longest_streak_days ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-dark-400">Streak</div>
        </div>
    </div>

    <!-- Recent Achievements -->
    <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-dark-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Achievements</h2>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($unlocked as $userAchievement)
            <div class="p-4 rounded-xl border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/10 flex items-center gap-3">
                <div class="text-3xl">{{ $userAchievement->achievement->icon ?? '🏆' }}</div>
                <div>
                    <h3 class="font-semibold text-sm text-gray-900 dark:text-gray-100">{{ $userAchievement->achievement->name }}</h3>
                    <p class="text-xs text-gray-500 dark:text-dark-400">{{ $userAchievement->unlocked_at->diffForHumans() }}</p>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-4 text-gray-500 dark:text-dark-400">
                Пока нет ачивок
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
