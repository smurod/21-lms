@extends('public.layouts.app')
@section('name', 'page')
@section('title', 'Autotest run #' . $testRun->id . ' — 21-LMS')
@section('nav-projects', 'active')

@section('content')
    @include('public.components.notifications-overlay')
    @include('public.components.user-menu-overlay')
    @include('public.components.activities-overlay')
    @include('public.components.projects-overlay')

    <main class="test-page">
        <section class="test-hero">
            <div>
                <p class="public-kicker">Autotest logs</p>
                <h1>Run #{{ $testRun->id }}</h1>
                <p>{{ $submission->project->title }} — {{ $submission->user->username }} — submission #{{ $submission->id }}</p>
            </div>
            <a class="review-hero-link" href="{{ route('public.submissions.tests.index', $submission) }}">All runs</a>
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
                    <span>Status</span>
                    <strong>{{ $testRun->status }}</strong>
                    <em>exit code: {{ $testRun->exit_code ?? '—' }}</em>
                </div>
                <div class="test-summary-item">
                    <span>Score</span>
                    <strong>{{ $testRun->score }}%</strong>
                    <em>{{ $testRun->results->sum('points_earned') }}/{{ $testRun->results->sum('points_possible') }} points</em>
                </div>
                <div class="test-summary-item">
                    <span>Runner</span>
                    <strong title="{{ $testRun->runner }}">{{ \Illuminate\Support\Str::limit($testRun->runner, 22) }}</strong>
                    <em title="{{ $testRun->image ?: 'host process' }}">{{ \Illuminate\Support\Str::limit($testRun->image ?: 'host process', 22) }}</em>
                </div>
                <div class="test-summary-item">
                    <span>Duration</span>
                    <strong>{{ $testRun->duration_ms ? $testRun->duration_ms . 'ms' : '—' }}</strong>
                    <em>{{ $testRun->finished_at ? $testRun->finished_at->format('d.m.Y H:i') : 'not finished' }}</em>
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
            <h2>Checks</h2>
            <div class="test-result-list">
                @foreach($testRun->results as $result)
                    <article class="test-result-row {{ $result->passed ? 'passed' : 'failed' }}">
                        <div>
                            <h3>{{ $result->test_name }}</h3>
                            <p>
                                {{ $result->projectTest?->test_type ?? 'runtime' }}
                                @if($result->projectTest?->is_hidden)
                                    · hidden
                                @endif
                                · {{ $result->points_earned }}/{{ $result->points_possible }} points
                                · {{ $result->execution_time_ms ?? $testRun->duration_ms }}ms
                            </p>
                            @if($result->error_message)
                                <p class="test-error-text">{{ $result->error_message }}</p>
                            @endif
                        </div>
                        <span class="test-status test-status-{{ $result->passed ? 'passed' : 'failed' }}">
                            {{ $result->passed ? 'passed' : 'failed' }}
                        </span>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="test-card">
            <h2>Runner logs</h2>
            <pre class="test-log-box">{{ $testRun->logs ?: 'No logs captured.' }}</pre>
        </section>
    </main>
@endsection
