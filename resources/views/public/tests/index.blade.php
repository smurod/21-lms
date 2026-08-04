@extends('public.layouts.app')
@section('name', 'page')
@section('title', 'Autotest runs — 21-LMS')
@section('nav-projects', 'active')

@section('content')
    @include('public.components.notifications-overlay')
    @include('public.components.user-menu-overlay')
    @include('public.components.activities-overlay')
    @include('public.components.projects-overlay')

    <main class="test-page">
        <section class="test-hero">
            <div>
                <p class="public-kicker">Autotests</p>
                <h1>{{ $submission->project->title }}</h1>
                <p>История запусков автотестов для submission #{{ $submission->id }}. Здесь видны commit, runner, score и отдельные результаты проверок.</p>
            </div>
            <a class="review-hero-link" href="{{ route('public.projects.show', $submission->project) }}?view=task">Project</a>
        </section>

        @if(session('success'))
            <div class="pd-toast show" role="status"><span>{{ session('success') }}</span></div>
        @endif
        @if($errors->any())
            <div class="pd-toast show pd-toast-error" role="alert"><span>{{ $errors->first() }}</span></div>
        @endif

        <section class="test-card">
            <div class="test-summary-grid">
                <div class="test-summary-item">
                    <span>Student</span>
                    <strong>{{ $submission->user->name }}</strong>
                    <em>{{ $submission->user->username }}</em>
                </div>
                <div class="test-summary-item">
                    <span>Submission</span>
                    <strong>{{ str_replace('_', ' ', $submission->status) }}</strong>
                    <em>Attempt #{{ $submission->attempt_number }}</em>
                </div>
                <div class="test-summary-item">
                    <span>Score</span>
                    <strong>{{ $submission->test_score }}%</strong>
                    <em>{{ $submission->tests_passed }}/{{ $submission->tests_total }} points</em>
                </div>
                <div class="test-summary-item">
                    <span>Commit</span>
                    <strong>{{ $submission->git_commit_hash ? substr($submission->git_commit_hash, 0, 12) : '—' }}</strong>
                    <em>{{ $submission->tested_at ? $submission->tested_at->format('d.m.Y H:i') : 'not tested' }}</em>
                </div>
            </div>
            <div class="test-rerun-row">
                @if($canRerun)
                    <form method="POST" action="{{ route('public.submissions.tests.rerun', $submission) }}">
                        @csrf
                        <button class="review-primary" type="submit">Rerun autotests</button>
                    </form>
                @else
                    <button class="review-primary" type="button" disabled>Rerun unavailable</button>
                    <span>{{ $rerunReason }}</span>
                @endif
            </div>
        </section>

        <section class="test-card">
            <div class="review-card-head">
                <h2>Test runs</h2>
                <span>{{ $testRuns->count() }}</span>
            </div>

            <div class="test-run-list">
                @forelse($testRuns as $run)
                    <article class="test-run-item test-run-{{ $run->status }}">
                        <div class="test-run-main">
                            <div class="test-run-title-row">
                                <h3>Run #{{ $run->id }}</h3>
                                <span class="test-status test-status-{{ $run->status }}">{{ $run->status }}</span>
                            </div>
                            <div class="test-run-meta">
                                <span title="{{ $run->runner }}">{{ \Illuminate\Support\Str::limit($run->runner, 26) }}</span>
                                <span title="{{ $run->image ?: 'host process' }}">{{ \Illuminate\Support\Str::limit($run->image ?: 'host process', 22) }}</span>
                                <span>{{ $run->score }}%</span>
                                <span>{{ $run->duration_ms ? $run->duration_ms . 'ms' : '—' }}</span>
                                <span title="{{ $run->commit_hash }}">{{ $run->commit_hash ? substr($run->commit_hash, 0, 12) : '—' }}</span>
                            </div>
                            <div class="test-result-pills">
                                @foreach($run->results as $result)
                                    <span class="test-result-pill {{ $result->passed ? 'passed' : 'failed' }}">
                                        {{ $result->test_name }}: {{ $result->points_earned }}/{{ $result->points_possible }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <a class="review-primary" href="{{ route('public.submissions.tests.show', [$submission, $run]) }}">Logs</a>
                    </article>
                @empty
                    <div class="public-empty-state">
                        <div>🧪</div>
                        <h3>Автотесты ещё не запускались</h3>
                        <p>После сдачи проекта здесь появятся queued/running/passed/failed запуски.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </main>
@endsection
