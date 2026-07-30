@extends('public.layouts.app')
@section('name', 'page')
@section('title', 'Peer reviews — 21-LMS')
@section('nav-projects', 'active')

@section('content')
    @include('public.components.notifications-overlay')
    @include('public.components.user-menu-overlay')
    @include('public.components.activities-overlay')
    @include('public.components.projects-overlay')

    <main class="review-page">
        <section class="review-hero">
            <div>
                <p class="public-kicker">Peer review</p>
                <h1>Reviews</h1>
                <p>Ваши назначенные P2P-проверки кода. Откройте review, проверьте репозиторий студента и отправьте score + feedback.</p>
            </div>
            <a class="review-hero-link" href="{{ route('public.projects.index') }}">Projects</a>
        </section>

        @if(session('success'))
            <div class="pd-toast show" role="status">
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error') || $errors->any())
            <div class="pd-toast show pd-toast-error" role="alert">
                <span>{{ session('error') ?? $errors->first() }}</span>
            </div>
        @endif

        <section class="review-card">
            <div class="review-card-head">
                <h2>Assigned reviews</h2>
                <span>{{ $reviews->total() }}</span>
            </div>

            <div class="review-list">
                @forelse($reviews as $review)
                    @php
                        $submission = $review->submission;
                        $student = $submission?->user;
                        $project = $submission?->project;
                        $repoUrl = $submission?->git_url;
                        $openRepoUrl = $repoUrl && str_ends_with($repoUrl, '.git') ? substr($repoUrl, 0, -4) : $repoUrl;
                        $startsAt = $review->started_at;
                        $canOpenReview = ! $startsAt || $startsAt->lessThanOrEqualTo(now());
                    @endphp
                    <article class="review-item">
                        <div class="review-avatar">{{ mb_substr($student->name ?? 'U', 0, 1) }}</div>
                        <div class="review-main">
                            <div class="review-title-row">
                                <h3>{{ $project->title ?? 'Project' }}</h3>
                                <span class="review-status review-status-{{ $review->status }}">{{ str_replace('_', ' ', $review->status) }}</span>
                            </div>
                            <p>Student: <strong>{{ $student->name ?? 'Unknown' }}</strong> / {{ $student->username ?? '—' }}</p>
                            <div class="review-meta">
                                <span>Attempt #{{ $submission->attempt_number ?? 1 }}</span>
                                <span>Tests: {{ $submission->tests_passed ?? 0 }}/{{ $submission->tests_total ?? 0 }} ({{ $submission->test_score ?? 0 }}%)</span>
                                <span>Начало: {{ $startsAt ? $startsAt->format('d.m.Y H:i') : '—' }}</span>
                                <span>Deadline: {{ $review->completed_at ? $review->completed_at->format('d.m.Y H:i') : '—' }}</span>
                            </div>
                        </div>
                        <div class="review-actions">
                            @if($canOpenReview)
                                @if($openRepoUrl)
                                    <a href="{{ $openRepoUrl }}" target="_blank" rel="noopener" class="review-secondary">Repo</a>
                                @endif
                                <a href="{{ route('reviews.show', $review) }}" class="review-primary">Review</a>
                            @else
                                <div class="review-countdown" data-starts-at="{{ $startsAt->toIso8601String() }}">
                                    До начала review: <strong>{{ $startsAt->diffForHumans(now(), true) }}</strong>
                                </div>
                                <div class="review-actions-ready" hidden>
                                    @if($openRepoUrl)
                                        <a href="{{ $openRepoUrl }}" target="_blank" rel="noopener" class="review-secondary">Repo</a>
                                    @endif
                                    <a href="{{ route('reviews.show', $review) }}" class="review-primary">Review</a>
                                </div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="public-empty-state">
                        <div>📝</div>
                        <h3>Нет назначенных проверок</h3>
                        <p>Когда другой студент сдаст проект на review и система назначит ваш слот, проверка появится здесь.</p>
                    </div>
                @endforelse
            </div>

            @if($reviews->hasPages())
                <div class="review-pagination">
                    {{ $reviews->links() }}
                </div>
            @endif
        </section>
    </main>
@endsection
