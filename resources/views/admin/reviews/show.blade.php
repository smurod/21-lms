@extends('admin.layouts.app')

@section('title', 'Review Submission')

@section('content')
<div class="max-w-5xl space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('reviews.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Review Submission</h1>
            <p class="text-gray-500 dark:text-dark-400 mt-0.5">
                Student: <span class="font-medium">{{ $review->submission->user->name }}</span>
                / Project: <span class="font-medium">{{ $review->submission->project->title }}</span>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Submission Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Submission Details</h2>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-dark-400">Status</span>
                        <p class="font-medium">{{ ucfirst($review->submission->status) }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-dark-400">Attempt</span>
                        <p class="font-medium">#{{ $review->submission->attempt_number }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-dark-400">Git URL</span>
                        <a href="{{ $review->submission->git_url ?? '#' }}"
                           class="text-brand-600 dark:text-brand-400 hover:underline truncate block max-w-xs"
                           {{ !$review->submission->git_url ? 'aria-disabled' : '' }}>
                            {{ $review->submission->git_url ?? '—' }}
                        </a>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-dark-400">Submitted</span>
                        <p class="font-medium">{{ $review->submission->submitted_at?->format('d.m.Y H:i') ?? '—' }}</p>
                    </div>
                </div>

                @if($review->submission->testResults->count() > 0)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-dark-700">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Test Results</h3>
                    <div class="space-y-2">
                        @foreach($review->submission->testResults as $tr)
                        <div class="flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 {{ $tr->passed ? 'text-green-500' : 'text-red-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($tr->passed)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                @endif
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300">{{ $tr->test_name }}</span>
                            <span class="text-gray-500 dark:text-dark-400 ml-auto">
                                {{ $tr->points_earned }}/{{ $tr->points_possible }} — {{ round($tr->execution_time_ms) }}ms
                            </span>
                        </div>
                        @if($tr->error_message)
                        <pre class="ml-6 text-xs bg-gray-100 dark:bg-dark-900 rounded p-2 text-red-600 overflow-x-auto">{{ $tr->error_message }}</pre>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Review Form -->
            <form method="POST" action="{{ route('reviews.submit', $review) }}" class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-6 space-y-4">
                @csrf

                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Your Review</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Score (0-100)</label>
                        <input type="number" name="score" min="0" max="100" required
                               class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-dark-700 rounded-lg bg-gray-50 dark:bg-dark-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confidence (0-100)</label>
                        <input type="number" name="confidence_score" min="0" max="100"
                               class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-dark-700 rounded-lg bg-gray-50 dark:bg-dark-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Feedback</label>
                    <textarea name="feedback" rows="6" required
                              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-dark-700 rounded-lg bg-gray-50 dark:bg-dark-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Private Notes (only for mentor)</label>
                    <textarea name="private_notes" rows="3"
                              class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-dark-700 rounded-lg bg-gray-50 dark:bg-dark-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none"></textarea>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 transition-colors shadow-sm">
                        Submit Review
                    </button>
                    <form method="POST" action="{{ route('reviews.destroy', $review) }}"
                          onsubmit="return confirm('Decline this review?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700">
                            Decline
                        </button>
                    </form>
                </div>
            </form>
        </div>

        <!-- Right: Quick Info -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Other Reviews</h2>

                @if($review->submission->reviews->where('status', 'completed')->count() === 0)
                    <p class="text-sm text-gray-500 dark:text-dark-400">No reviews submitted yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach($review->submission->reviews->where('status', 'completed') as $r)
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-dark-900 border border-gray-200 dark:border-dark-700">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-brand-500 flex items-center justify-center text-white text-xs font-semibold">
                                    {{ substr($r->reviewer->name ?? '?', 0, 1) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ $r->reviewer->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-dark-400">{{ $r->completed_at?->diffForHumans() ?? '' }}</p>
                                </div>
                                <span class="text-sm font-semibold text-brand-600">{{ $r->score }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Average Score</h2>
                @php
                    $avgScore = $review->submission->reviews->where('status', 'completed')->avg('score') ?? 0;
                    $passing = $review->submission->project->passing_score ?? 70;
                @endphp
                <div class="text-3xl font-bold {{ $avgScore >= $passing ? 'text-green-600' : 'text-orange-600' }}">
                    {{ round($avgScore, 1) }}
                </div>
                <p class="text-sm text-gray-500 dark:text-dark-400 mt-1">Passing: {{ $passing }}</p>
                <div class="mt-2 w-full bg-gray-200 dark:bg-dark-700 rounded-full h-2">
                    <div class="bg-brand-600 h-2 rounded-full" style="width: {{ min($avgScore, 100) }}%"></div>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 rounded-xl border border-gray-200 dark:border-dark-700 p-6">
                <a href="{{ route('chats.show', $review->submission_id) }}"
                   class="flex items-center gap-3 text-brand-600 dark:text-brand-400 hover:text-brand-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    Open P2P Chat
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
