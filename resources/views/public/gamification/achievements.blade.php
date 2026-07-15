@extends('public.layouts.app')

@section('title', 'Achievements')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Achievements</h1>
        <p class="text-gray-500 dark:text-dark-400 mt-1">Разблокируй ачивки, проходя курсы и проекты</p>
    </div>

    <!-- Unlocked -->
    <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-dark-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Разблокированные ({{ $unlocked->count() }})</h2>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($unlocked as $userAchievement)
            <div class="p-4 rounded-xl border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/10 text-center">
                <div class="text-4xl mb-2">{{ $userAchievement->achievement->icon ?? '🏆' }}</div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $userAchievement->achievement->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-dark-400 mt-1">{{ $userAchievement->achievement->description }}</p>
                <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                    Разблокирована {{ $userAchievement->unlocked_at->format('d.m.Y') }}
                </p>
            </div>
            @empty
            <div class="col-span-full text-center py-8 text-gray-500 dark:text-dark-400">
                Пока нет разблокированных ачивок. Продолжай учиться!
            </div>
            @endforelse
        </div>
    </div>

    <!-- Locked -->
    <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-dark-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Доступные ({{ $locked->count() }})</h2>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($locked as $achievement)
            <div class="p-4 rounded-xl border border-gray-200 dark:border-dark-700 text-center opacity-80">
                <div class="text-4xl mb-2">{{ $achievement->icon ?? '🏆' }}</div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $achievement->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-dark-400 mt-1">{{ $achievement->description }}</p>
                @if($achievement->xp_reward > 0)
                <span class="inline-block mt-2 px-2 py-0.5 rounded-full text-xs font-medium bg-brand-100 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300">
                    +{{ $achievement->xp_reward }} XP
                </span>
                @endif
                <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-medium
                    {{ match($achievement->rarity) {
                        'common' => 'bg-gray-100 dark:bg-dark-700 text-gray-600 dark:text-gray-400',
                        'uncommon' => 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300',
                        'rare' => 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300',
                        'legendary' => 'bg-purple-100 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300',
                        default => 'bg-gray-100 dark:bg-dark-700 text-gray-600 dark:text-gray-400',
                    } }}">
                    {{ ucfirst($achievement->rarity) }}
                </span>
            </div>
            @empty
            <div class="col-span-full text-center py-8 text-gray-500 dark:text-dark-400">
                Все ачивки разблокированы!
            </div>
            @endforelse
        </div>
    </div>

    <!-- Hidden -->
    @if($hidden->count() > 0)
    <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-dark-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Секретные ({{ $hidden->count() }})</h2>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($hidden as $achievement)
            <div class="p-4 rounded-xl border border-gray-200 dark:border-dark-700 text-center opacity-50">
                <div class="text-4xl mb-2">🔒</div>
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">??? </h3>
                <p class="text-sm text-gray-500 dark:text-dark-400">Секретная ачивка</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
