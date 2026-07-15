@extends('admin.layouts.app')

@section('title', 'Reviews')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Reviews</h1>
            <p class="text-gray-500 dark:text-dark-400 mt-1">Ваши назначенные проверки кода</p>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-dark-850 border-y border-gray-200 dark:border-dark-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-dark-400 uppercase tracking-wider">Студент</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-dark-400 uppercase tracking-wider">Проект</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-dark-400 uppercase tracking-wider">Статус</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-dark-400 uppercase tracking-wider">Дедлайн</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-dark-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-700">
                    @forelse($reviews as $review)
                    <tr class="hover:bg-gray-50 dark:hover:bg-dark-850 transition-colors">
                        <td class="px-4 py-3">
                            <div>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $review->submission->user->name ?? 'Unknown' }}</span>
                                <p class="text-xs text-gray-500 dark:text-dark-400">{{ $review->submission->user->username ?? '—' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('public.projects.show', $review->submission->project) }}"
                               class="text-brand-600 dark:text-brand-400 hover:underline text-sm font-medium">
                                {{ $review->submission->project->title ?? '—' }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $review->status === 'in_progress' ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300' }}">
                                {{ ucfirst(str_replace('_', ' ', $review->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-300">
                            {{ $review->completed_at ? $review->completed_at->format('d.m.Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('reviews.show', $review) }}"
                                   class="px-3 py-1 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 transition-colors">
                                    Review
                                </a>
                                <a href="{{ route('chats.show', $review->submission_id) }}"
                                   class="text-brand-600 dark:text-brand-400 hover:text-brand-700 text-sm font-medium">
                                    Chat
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-dark-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-dark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-lg font-medium">Нет назначенных проверок</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reviews->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-dark-700">
            {{ $reviews->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
