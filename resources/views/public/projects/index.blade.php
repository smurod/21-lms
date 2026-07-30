@extends('public.layouts.app')
@section('name', 'page')
@section('title', 'Projects — 21-LMS')
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
        /* Server-rendered page. Data comes from Public\ProjectController@index:
           $projects (enriched), $activeTab, $tabCounts. */
        $activeTab = $activeTab ?? 'open';
        $tabCounts = $tabCounts ?? [];
        $tabs = [
            'open'       => 'Запись открыта',
            'inprogress' => 'В процессе',
            'review'     => 'Ожидает review',
            'done'       => 'Завершенные',
        ];
    @endphp

    {{-- Flash messages --}}
    @if (session('success') || session('info'))
        <div class="pd-toast show" id="calendarFlash" role="status">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            <span>{{ session('success') ?? session('info') }}</span>
        </div>
    @endif

    <main class="prj-page">
        <!-- Dark hero band with title + tabs -->
        <section class="prj-hero">
            <div class="prj-hero-head">
                <a href="{{ route('public.home') }}" class="prj-back" aria-label="Back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                </a>
                <h1>Projects</h1>
            </div>

            {{-- Tabs are server links: the active tab comes from ?tab= --}}
            <div class="prj-tabs" role="tablist">
                @foreach ($tabs as $key => $label)
                    <a class="prj-tab {{ $activeTab === $key ? 'active' : '' }}"
                       href="{{ route('public.projects.index', $key === 'open' ? [] : ['tab' => $key]) }}"
                       role="tab" @if($activeTab === $key) aria-current="page" @endif>
                        {{ $label }}
                        @if (($tabCounts[$key] ?? 0) > 0)
                            <span class="prj-tab-badge">{{ $tabCounts[$key] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>

        <!-- Light content area -->
        <section class="prj-content">
            <div class="prj-panel active">
                @if ($projects->isEmpty())
                    <div class="prj-empty">
                        <div class="empty-title">
                            @switch($activeTab)
                                @case('inprogress') Нет проектов в работе @break
                                @case('review') Нет проектов на review @break
                                @case('done') Пока нет завершённых проектов @break
                                @default Нет доступных проектов
                            @endswitch
                        </div>
                        <div class="empty-text">
                            @if ($activeTab === 'open')
                                Все доступные проекты уже в работе или завершены.
                            @else
                                Загляните во вкладку «Запись открыта», чтобы выбрать новый проект.
                            @endif
                        </div>
                    </div>
                @else
                    <div class="prj-grid">
                        @foreach ($projects as $item)
                            @php
                                $project = $item['project'];
                                $status  = $item['test_status']['result'];
                                $locked  = ($userLevel ?? 1) < $project->min_level;

                                /* Pill: what the user sees on the card */
                                if (($item['is_retry'] ?? false) || $status === 'failed') {
                                    $pillClass = 'prj-pill-review';
                                    $pillText  = 'Провален';
                                } elseif ($item['is_completed']) {
                                    $pillClass = 'prj-pill-done';
                                    $pillText  = 'Принят';
                                } elseif ($item['is_enrolled']) {
                                    $inReview  = in_array($status, ['tested', 'in_review', 'reviewed'], true);
                                    $pillClass = $inReview ? 'prj-pill-review' : 'prj-pill-progress';
                                    $pillText  = $inReview ? 'Ожидает review' : 'В процессе';
                                } else {
                                    $pillClass = 'prj-pill-open';
                                    $pillText  = 'Запись открыта';
                                }
                            @endphp

                            <a class="prj-card" href="{{ route('public.projects.show', $project) }}">
                                <div class="prj-card-top">
                                    <span class="prj-pill {{ $pillClass }}">{{ $pillText }}</span>
                                    <span class="prj-meta">
                  <span class="prj-kind">{{ strtoupper($project->language ?? '—') }}</span>
                  @if ($project->is_mandatory)
                                            <span class="prj-req prj-req-mandatory">Mandatory</span>
                                        @else
                                            <span class="prj-req prj-req-optional">Optional</span>
                                        @endif
                </span>
                                </div>

                                <h3 class="prj-card-title">{{ $project->title }} <span class="prj-xp">{{ $project->xp_reward }} XP</span></h3>
                                <p class="prj-card-desc">{{ \Illuminate\Support\Str::limit($project->description, 100) }}</p>

                                <div class="prj-card-time">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/><path d="M5 3 2 6"/><path d="m22 6-3-3"/></svg>
                                    <span>{{ $project->estimated_hours }} hours</span>
                                    @if ($project->min_level > 1)
                                        <span class="prj-kind" title="Минимальный уровень">{{ $locked ? '🔒 ' : '' }}lvl {{ $project->min_level }}+</span>
                                    @endif
                                </div>

                                @if ($item['is_enrolled'] && $item['review_status']['required'] > 0 && in_array($status, ['tested', 'in_review', 'reviewed'], true))
                                    <div class="prj-card-time">
                                        <span>Reviews: {{ $item['review_status']['received'] }}/{{ $item['review_status']['required'] }}</span>
                                    </div>
                                @endif
                                @if (($item['is_completed'] || ($item['is_retry'] ?? false)) && $item['test_status']['testsTotal'] > 0)
                                    <div class="prj-card-time">
                                        <span>Tests: {{ $item['test_status']['testsPassed'] }}/{{ $item['test_status']['testsTotal'] }} ({{ $item['test_status']['percent'] }}%)</span>
                                    </div>
                                @endif
                                @if (($item['is_retry'] ?? false) && $item['review_status']['required'] > 0)
                                    <div class="prj-card-time">
                                        <span>Last review: {{ $item['review_status']['received'] }}/{{ $item['review_status']['required'] }} — можно переделать</span>
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </main>

@endsection
