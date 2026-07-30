@extends('public.layouts.app')
@section('name', 'page')
@section('title', 'Review — 21-LMS')
@section('nav-projects', 'active')

@section('content')
    @include('public.components.notifications-overlay')
    @include('public.components.user-menu-overlay')
    @include('public.components.activities-overlay')
    @include('public.components.projects-overlay')

    @php
        $submission = $review->submission;
        $student = $submission->user;
        $project = $submission->project;
        $repoUrl = $submission->git_url;
        $openRepoUrl = $repoUrl && str_ends_with($repoUrl, '.git') ? substr($repoUrl, 0, -4) : $repoUrl;
        $completedReviews = $submission->reviews->where('status', 'completed');
        $avgScore = $completedReviews->avg('score') ?? 0;
        $passing = $project->passing_score ?? 70;
    @endphp

    <main class="review-page">
        <section class="review-hero review-hero-small">
            <div>
                <p class="public-kicker">P2P review</p>
                <h1>{{ $project->title }}</h1>
                <p>Студент: <strong>{{ $student->name }}</strong> / {{ $student->username ?? '—' }}</p>
            </div>
            <a class="review-hero-link" href="{{ route('reviews.index') }}">Назад</a>
        </section>

        @if(session('success'))
            <div class="pd-toast show" role="status"><span>{{ session('success') }}</span></div>
        @endif
        @if(session('error') || $errors->any())
            <div class="pd-toast show pd-toast-error" role="alert"><span>{{ session('error') ?? $errors->first() }}</span></div>
        @endif

        <section class="review-layout">
            <div class="review-card review-card-form">
                <div class="review-card-head">
                    <h2>Детали сдачи</h2>
                    <span>{{ ucfirst($submission->status) }}</span>
                </div>

                <div class="review-details-grid">
                    <div><span>Статус</span><strong>{{ ucfirst($submission->status) }}</strong></div>
                    <div><span>Попытка</span><strong>#{{ $submission->attempt_number }}</strong></div>
                    <div><span>Автотесты</span><strong>{{ $submission->tests_passed }}/{{ $submission->tests_total }} — {{ $submission->test_score }}%</strong></div>
                    <div><span>Сдано</span><strong>{{ $submission->submitted_at?->format('d.m.Y H:i') ?? '—' }}</strong></div>
                </div>

                @if($repoUrl)
                    <div class="review-repo-box">
                        <span title="{{ $repoUrl }}">{{ $repoUrl }}</span>
                        <a href="{{ $openRepoUrl }}" target="_blank" rel="noopener">Открыть репозиторий</a>
                    </div>
                @endif

                <form method="POST" action="{{ route('reviews.submit', $review) }}" class="review-form">
                    @csrf

                    <div class="review-checklist review-form-wide">
                        <h2>Чек-лист проверки</h2>
                        <p>Обязательный пункт означает «обязательно оценить», а не «обязательно поставить Да». Если студент не выполнил критерий, выберите «Нет» и отразите это в score/feedback.</p>
                        @forelse($project->checklists as $item)
                            @php $oldResult = old('checklist.' . $item->item_key); @endphp
                            <div class="review-checklist-item">
                                <div class="review-checklist-text">
                                    <strong>{{ $item->item_label }} @if($item->is_required)<em>обязательно</em>@endif</strong>
                                    <small>{{ $item->description }}</small>
                                </div>
                                <div class="review-checklist-options">
                                    <label>
                                        <input type="radio" name="checklist[{{ $item->item_key }}]" value="passed" @checked($oldResult === 'passed')>
                                        <span>Да</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="checklist[{{ $item->item_key }}]" value="failed" @checked($oldResult === 'failed')>
                                        <span>Нет</span>
                                    </label>
                                    @if(! $item->is_required)
                                        <label>
                                            <input type="radio" name="checklist[{{ $item->item_key }}]" value="not_applicable" @checked($oldResult === 'not_applicable')>
                                            <span>Не применимо</span>
                                        </label>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="review-checklist-empty">Для этого проекта чек-лист ещё не настроен.</div>
                        @endforelse
                    </div>

                    <label>
                        <span>Оценка (0-100)</span>
                        <input type="number" name="score" min="0" max="100" required value="{{ old('score') }}">
                    </label>

                    <label>
                        <span>Уверенность (0-100)</span>
                        <input type="number" name="confidence_score" min="0" max="100" value="{{ old('confidence_score', 100) }}">
                    </label>

                    <label class="review-form-wide">
                        <span>Комментарий для студента</span>
                        <textarea name="feedback" rows="7" required>{{ old('feedback') }}</textarea>
                    </label>

                    <label class="review-form-wide">
                        <span>Приватные заметки</span>
                        <textarea name="private_notes" rows="3">{{ old('private_notes') }}</textarea>
                    </label>

                    <div class="review-form-actions">
                        <button type="submit" class="review-primary">Отправить review</button>
                    </div>
                </form>
            </div>

            <aside class="review-side">
                <div class="review-card">
                    <div class="review-card-head">
                        <h2>Прогресс</h2>
                        <span>{{ $completedReviews->count() }}/{{ $project->required_reviews_count ?? 2 }}</span>
                    </div>
                    <div class="review-score {{ $avgScore >= $passing ? 'is-pass' : 'is-wait' }}">
                        {{ round($avgScore, 1) }}
                    </div>
                    <p class="review-side-text">Проходной балл: {{ $passing }}</p>
                </div>

                <div class="review-card">
                    <div class="review-card-head"><h2>Завершённые проверки</h2></div>
                    @forelse($completedReviews as $completed)
                        <div class="review-completed-item">
                            <strong>{{ $completed->reviewer->name ?? 'Reviewer' }}</strong>
                            <span>{{ $completed->score }}/100</span>
                        </div>
                    @empty
                        <p class="review-side-text">Пока нет отправленных review.</p>
                    @endforelse
                </div>
            </aside>
        </section>
    </main>
@endsection
