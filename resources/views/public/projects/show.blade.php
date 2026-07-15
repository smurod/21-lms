@extends('public.layouts.app')
@section('name', 'page')
@section('title', $project->title . ' — Project details — 21-LMS')
@section('nav-projects', 'active')
@section('content')

    <!-- Notifications overlay -->
    @include('public.components.notifications-overlay')

    @include('public.components.user-menu-overlay')

    <!-- Activities overlay -->
    @include('public.components.activities-overlay')

    <!-- Projects overlay -->
    @include('public.components.projects-overlay')

    @php
        /* Server-rendered page. Data from Public\ProjectController@show:
           $project, $gitlabProject, $activeSubmission, $completedSubmission,
           $reviewStatus, $testStatus, $levelLocked, $userLevel, $projectStats. */

        $status = $activeSubmission?->status;

        $isInReview   = in_array($status, ['tested', 'in_review', 'reviewed'], true);
        $isWorking    = in_array($status, ['pending', 'queued', 'testing', 'in_progress', 'resubmitted'], true);
        $isPassed     = $completedSubmission?->status === 'passed' && !$activeSubmission;
        $isFailed     = $completedSubmission?->status === 'failed' && !$activeSubmission;

        if ($isPassed)        { $pillClass = 'prj-pill-done';     $pillText = 'Принят'; }
        elseif ($isFailed)    { $pillClass = 'prj-pill-review';   $pillText = 'Провален'; }
        elseif ($isInReview)  { $pillClass = 'prj-pill-review';   $pillText = 'Ожидает review'; }
        elseif ($isWorking)   { $pillClass = 'prj-pill-progress'; $pillText = 'В процессе'; }
        else                  { $pillClass = 'prj-pill-open';     $pillText = 'Запись открыта'; }

        /* Active view: task becomes available once enrolled */
        $activeView = request('view', $isWorking || $isInReview ? 'task' : 'about');
        if ($activeView === 'task' && !($isWorking || $isInReview)) $activeView = 'about';

        $repoUrl = $gitlabProject['web_url'] ?? $project->repository_url;
        $cloneUrl = $gitlabProject['ssh_url_to_repo'] ?? ($gitlabProject['http_url_to_repo'] ?? $repoUrl);

        /* General skills — derived server-side from project data */
        $xp = max(1, (int) $project->xp_reward);
        $skills = [
            strtoupper($project->language ?? 'C') => (int) round($xp * 0.35),
            'Algorithms'              => (int) round($xp * 0.25),
            'Structured programming'  => (int) round($xp * 0.20),
            'DB & Data'               => (int) round($xp * 0.10),
            'Linux'                   => (int) round($xp * 0.10),
        ];

        /* Timeline states (real, from the user's submission) */
        $anySub          = $activeSubmission ?? $completedSubmission;
        $stepSubDone     = (bool) $anySub;
        $stepImplActive  = $isWorking;
        $stepImplDone    = $isInReview || (bool) $completedSubmission;
        $stepRevActive   = $isInReview;
        $stepRevDone     = (bool) $completedSubmission;

        $fmt = fn ($d) => $d ? $d->format('H:i, j M') : null;
        $subscribedAt = $fmt($anySub?->created_at);
        $submittedAt  = $fmt($activeSubmission?->submitted_at ?? $completedSubmission?->submitted_at);

        $peerText = $stepRevActive
            ? 'In Progress (' . $reviewStatus['received'] . '/' . $reviewStatus['required'] . ')'
            : ($stepRevDone ? 'Done (' . $reviewStatus['received'] . '/' . $reviewStatus['required'] . ')' : 'Not started');
        $autoText = $testStatus['testsTotal'] > 0
            ? $testStatus['testsPassed'] . '/' . $testStatus['testsTotal'] . ' passed'
            : 'Not started';
    @endphp

    {{-- Flash messages from SubscriptionController / CalendarController --}}
    @if (session('success') || session('info'))
        <div class="pd-toast show" id="calendarFlash" role="status">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            <span>{{ session('success') ?? session('info') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="pd-toast show pd-toast-error" role="alert">
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <main class="pd-page">
        <!-- Dark hero band -->
        <section class="pd-hero">
            <div class="pd-hero-inner">
                <a href="{{ route('public.projects.index') }}" class="prj-back" aria-label="Back to projects">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                </a>
                <div class="pd-hero-info">
                    <div class="pd-hero-pills">
                        <span class="prj-pill {{ $pillClass }}">{{ $pillText }}</span>
                        @if ($project->is_mandatory)
                            <span class="prj-req prj-req-mandatory">Mandatory</span>
                        @else
                            <span class="prj-req prj-req-optional">Optional</span>
                        @endif
                    </div>
                    <h1>{{ $project->title }} <span class="prj-xp">{{ $project->xp_reward }} XP</span></h1>
                    <p>{{ $project->description }}</p>
                </div>
                <div class="pd-hero-action">
                    @if ($levelLocked ?? false)
                        <button class="pd-subscribe subscribed" type="button" disabled>🔒 Уровень {{ $project->min_level }}+</button>
                        <div class="pd-subscribe-note">Ваш уровень: {{ $userLevel }}. Завершайте проекты, чтобы открыть этот.</div>
                    @else
                        @auth
                            @if (!$activeSubmission)
                                {{-- Native form → SubscriptionController (creates submission + GitLab branch) --}}
                                <form method="POST" action="{{ route('public.projects.subscribe', $project) }}">
                                    @csrf
                                    <button class="pd-subscribe" type="submit">
                                        {{ $completedSubmission ? 'Записаться снова' : 'Записаться' }}
                                    </button>
                                </form>
                                @if ($completedSubmission)
                                    <div class="pd-subscribe-note">Попытка №{{ $completedSubmission->attempt_number + 1 }}</div>
                                @endif
                            @elseif ($isWorking)
                                {{-- Submit for review → CalendarController@submitProjectForReview (BookingService FIFO) --}}
                                <form method="POST" action="{{ route('calendar.submit-review') }}">
                                    @csrf
                                    <input type="hidden" name="project_id" value="{{ $project->id }}" />
                                    <input type="hidden" name="submission_id" value="{{ $activeSubmission->id }}" />
                                    <button class="pd-subscribe" type="submit">Сдать на review</button>
                                </form>
                                <div class="pd-subscribe-note">Попытка №{{ $activeSubmission->attempt_number }}</div>
                            @else
                                <button class="pd-subscribe subscribed" type="button" disabled>Ожидает review</button>
                                <div class="pd-subscribe-note">Reviews: {{ $reviewStatus['received'] }}/{{ $reviewStatus['required'] }}</div>
                            @endif
                        @endauth
                        @guest
                            <a class="pd-subscribe" href="{{ route('register') }}">Записаться</a>
                            <div class="pd-subscribe-note">Нужен аккаунт School 21</div>
                        @endguest
                    @endif
                </div>
            </div>

            <!-- Tabs: server links; Task unlocks after enrollment -->
            <div class="pd-tabs">
                <a class="pd-tab {{ $activeView === 'about' ? 'active' : '' }}"
                   href="{{ route('public.projects.show', $project) }}?view=about">About</a>
                @if ($isWorking || $isInReview)
                    <a class="pd-tab {{ $activeView === 'task' ? 'active' : '' }}"
                       href="{{ route('public.projects.show', $project) }}?view=task">Task</a>
                @else
                    <span class="pd-tab" aria-disabled="true">Task</span>
                @endif
            </div>
        </section>

        @if ($activeView === 'about')
            <!-- ===== About view ===== -->
            <section class="pd-content">
                <div class="pd-main-col">
                    <!-- Description -->
                    <article class="pd-card">
                        <h2>Description</h2>
                        <p class="pd-about-text">{{ $project->description }}</p>
                    </article>

                    <!-- Execution conditions -->
                    <article class="pd-card">
                        <h2>Execution conditions</h2>
                        <div class="pd-conditions">
                            <div class="pd-condition">Language: {{ strtoupper($project->language ?? '—') }}</div>
                            <span class="pd-cond-op">AND</span>
                            <div class="pd-condition">Level {{ $project->min_level }}+</div>
                            @if ($project->has_automated_tests)
                                <span class="pd-cond-op">AND</span>
                                <div class="pd-condition">Autotests: passing score {{ $project->passing_score }}%</div>
                            @endif
                        </div>
                    </article>

                    <!-- General skills -->
                    <article class="pd-card">
                        <h2>General skills</h2>
                        <div class="pd-skills">
                            @foreach ($skills as $skillName => $gain)
                                <div class="pd-skill">
                                    <div class="pd-skill-row"><span>{{ $skillName }}</span><span class="pd-skill-gain">+{{ $gain }}</span></div>
                                    <div class="pd-skill-bar"><div class="pd-skill-fill" style="width: {{ min(100, round($gain / $xp * 100)) }}%;"></div></div>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    <!-- Statistics (collapsible) -->
                    <article class="pd-card pd-stats-card">
                        <button class="pd-stats-toggle" type="button" aria-expanded="true">
                            <h2>Statistics</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
                        </button>
                        <div class="pd-stats-body">
                            <div class="pd-stat-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M2 21c0-4 3-6 7-6"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                                <span>{{ $projectStats['registered'] }} participants have registered for the project</span>
                            </div>
                            <div class="pd-stat-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M2 21c0-4 3-6 7-6"/></svg>
                                <span>{{ $projectStats['working'] }} participants are currently working on this project</span>
                            </div>
                            <div class="pd-stat-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 10h.01"/><path d="M12 10h.01"/><path d="M8 14h5"/></svg>
                                <span>{{ $projectStats['waiting'] }} participants are waiting for Peer Review</span>
                            </div>
                            <div class="pd-stat-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                <span>{{ $projectStats['passed'] + $projectStats['failed'] }} participants have completed the project: {{ $projectStats['passed'] }} successfully and {{ $projectStats['failed'] }} failed</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Sidebar: facts + timeline + peer review facts -->
                <aside class="pd-sidebar">
                    <div class="pd-facts">
                        <div class="pd-fact">
          <span class="pd-fact-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 9h8"/><path d="M8 13h6"/></svg>
            Type of project
          </span>
                            <span class="pd-fact-value">Individual</span>
                        </div>
                        <div class="pd-fact">
          <span class="pd-fact-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/></svg>
            Duration
          </span>
                            <span class="pd-fact-value">{{ $project->estimated_hours }} hours</span>
                        </div>
                        <div class="pd-fact">
          <span class="pd-fact-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="16" r="3"/><circle cx="16" cy="8" r="1.5"/></svg>
            XP
          </span>
                            <span class="pd-fact-value">{{ $project->xp_reward }}</span>
                        </div>
                    </div>

                    <div class="pd-divider"></div>

                    <div class="pd-timeline">
                        <div class="pd-step {{ $stepSubDone ? 'done' : 'active' }}">
                            <span class="pd-step-dot"></span>
                            <div class="pd-step-body">
                                <div class="pd-step-title">Subscription</div>
                                <div class="pd-step-date">{{ $subscribedAt ?? 'Open now' }}</div>
                            </div>
                        </div>
                        <div class="pd-step {{ $stepImplDone ? 'done' : ($stepImplActive ? 'active' : '') }}">
                            <span class="pd-step-dot"></span>
                            <div class="pd-step-body">
                                <div class="pd-step-title">Project implementation</div>
                                <div class="pd-step-date">{{ $stepImplActive ? 'In progress' : ($stepImplDone ? ($submittedAt ?? 'Done') : 'Not started') }}</div>
                            </div>
                        </div>
                        <div class="pd-step {{ $stepRevDone ? 'done' : ($stepRevActive ? 'active' : '') }}">
                            <span class="pd-step-dot"></span>
                            <div class="pd-step-body">
                                <div class="pd-step-title">Reviews</div>
                                <div class="pd-step-date">{{ $stepRevActive ? 'In progress' : ($stepRevDone ? 'Done' : 'Not started') }}</div>
                            </div>
                        </div>
                        <div class="pd-step pd-step-sub {{ $stepRevDone ? 'done' : ($stepRevActive ? 'active' : '') }}">
                            <span class="pd-step-dot"></span>
                            <div class="pd-step-body">
                                <div class="pd-step-title">Peer Review</div>
                                <div class="pd-step-date">{{ $peerText }}</div>
                            </div>
                        </div>
                        <div class="pd-step pd-step-sub {{ $testStatus['testsTotal'] > 0 ? 'done' : '' }}">
                            <span class="pd-step-dot"></span>
                            <div class="pd-step-body">
                                <div class="pd-step-title">Autotest</div>
                                <div class="pd-step-date">{{ $autoText }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="pd-divider"></div>

                    <div class="pd-facts">
                        <div class="pd-fact">
          <span class="pd-fact-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/><path d="M5 3 2 6"/><path d="m22 6-3-3"/></svg>
            Peer Review duration
          </span>
                            <span class="pd-fact-value">30 min</span>
                        </div>
                        <div class="pd-fact">
          <span class="pd-fact-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            Cost of one Peer Review
          </span>
                            <span class="pd-fact-value">1 PRP</span>
                        </div>
                        <div class="pd-fact">
          <span class="pd-fact-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 10h.01"/><path d="M12 10h.01"/><path d="M8 14h5"/></svg>
            Number of Peer Reviews
          </span>
                            <span class="pd-fact-value">{{ $project->required_reviews_count }}</span>
                        </div>
                        <div class="pd-fact">
          <span class="pd-fact-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M2 21c0-4 3-6 7-6"/><path d="m16 11 2 2 4-4"/></svg>
            Peer Reviews by participants
          </span>
                            <span class="pd-fact-value">{{ $reviewStatus['received'] }}</span>
                        </div>
                        <div class="pd-fact">
          <span class="pd-fact-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="7" r="4"/><path d="M2 21c0-4 3-6 7-6"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
            Peer Review by staff
          </span>
                            <span class="pd-fact-value">0</span>
                        </div>
                    </div>
                </aside>
            </section>
        @else
            <!-- ===== Task view (enrolled users) ===== -->
            <section class="pd-content pd-task-view">
                <div class="pd-main-col">
                    <!-- Git repository -->
                    <article class="pd-card">
                        <h2>Git repository</h2>
                        <div class="pd-git-row">
                            <span class="pd-git-url" title="{{ $cloneUrl }}">{{ \Illuminate\Support\Str::limit($cloneUrl, 64) }}</span>
                            <button class="pd-git-copy" type="button" data-copy-text="{{ $cloneUrl }}">Copy link</button>
                            @if ($repoUrl)
                                <a class="pd-git-open" href="{{ $repoUrl }}" target="_blank" rel="noopener">Open</a>
                            @endif
                        </div>
                        @auth
                            @if (auth()->user()->username)
                                <p class="pd-about-text">Ваша ветка: <strong>developer-{{ auth()->user()->username }}</strong></p>
                            @endif
                        @endauth
                    </article>

                    <!-- Task -->
                    <article class="pd-card">
                        <h2>Task</h2>
                        <div class="pd-task-doc">
                            {!! nl2br(e($project->instructions)) !!}
                        </div>
                    </article>
                </div>

                <aside class="pd-sidebar-col">
                    <div class="pd-card pd-submit-card">
                        <h2>Submit the project</h2>
                        @if ($isWorking && $activeSubmission)
                            <form method="POST" action="{{ route('calendar.submit-review') }}">
                                @csrf
                                <input type="hidden" name="project_id" value="{{ $project->id }}" />
                                <input type="hidden" name="submission_id" value="{{ $activeSubmission->id }}" />
                                <button class="pd-finish-btn" type="submit">Finish project</button>
                            </form>
                        @else
                            <button class="pd-finish-btn" type="button" disabled>Ожидает review</button>
                        @endif
                    </div>

                    <div class="pd-repo-card">
                        <div class="pd-repo-label">Git project</div>
                        <div class="pd-repo-name">{{ $gitlabProject['path'] ?? $project->slug }}</div>
                    </div>
                </aside>
            </section>
        @endif
    </main>

@endsection
