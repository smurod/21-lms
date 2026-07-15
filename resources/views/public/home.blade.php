@extends('public.layouts.app')
@section('name', 'page')
@section('nav-dashboard', 'active')
@section('content')

    <!-- Notifications overlay -->
    @include('public.components.notifications-overlay')

    <!-- User menu overlay -->
    @include('public.components.user-menu-overlay')

    <!-- Activities overlay -->
    @include('public.components.activities-overlay')

    <!-- Projects overlay -->
    @include('public.components.projects-overlay')

    <!-- Hero section -->
    <section class="hero">
        <div class="hero-bg" aria-hidden="true"></div>

        <a class="hero-search" href="{{ route('public.projects.index') }}#search">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <span>Search</span>
        </a>

        <div class="hero-row">
            <div class="hero-side hero-side-left">
                <div class="level-label">lvl {{ $currentLevel }}: {{ $xpProgress }}%</div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="--progress-width: {{ $xpProgress }}%;"></div>
                </div>
            </div>

            <div class="hero-avatar">
                @if($user->profile_photo_url)
                    <img src="{{ $user->profile_photo_url }}" alt="User avatar" />
                @else
                    <div class="avatar-fallback">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
                    </div>
                @endif
            </div>

            <div class="hero-side hero-side-right">
                <h1 class="user-name">{{ $user->username ?? $user->name }}</h1>
                <div class="user-role">{{ $levels[$currentLevel]['name'] ?? 'Core program' }}</div>
            </div>
        </div>
    </section>

    <!-- Stats strip -->
    <section class="stats-section">
        <div class="stats-card">
            <div class="robot-icon">
                <svg viewBox="0 0 64 64" width="36" height="36">
                    <rect x="12" y="8" width="40" height="32" rx="8" fill="#2a3142" stroke="#3d4559" stroke-width="2"/>
                    <circle cx="22" cy="22" r="5" fill="#8bea9a"/>
                    <circle cx="42" cy="22" r="5" fill="#8bea9a"/>
                    <circle cx="22" cy="22" r="2" fill="#171d2b"/>
                    <circle cx="42" cy="22" r="2" fill="#171d2b"/>
                    <rect x="20" y="14" width="24" height="3" rx="1.5" fill="#434a5d"/>
                    <rect x="28" y="32" width="8" height="4" rx="2" fill="#7dd3fc"/>
                    <rect x="18" y="42" width="6" height="12" rx="3" fill="#2a3142" stroke="#3d4559" stroke-width="1.5"/>
                    <rect x="40" y="42" width="6" height="12" rx="3" fill="#2a3142" stroke="#3d4559" stroke-width="1.5"/>
                    <rect x="26" y="40" width="12" height="4" rx="2" fill="#434a5d"/>
                </svg>
            </div>

            <div class="stats-list">
                <div class="stat-item">
                    <h3>{{ $completedCourses }} / {{ $totalCourses }}</h3>
                    <p class="highlight">Courses</p>
                </div>
                <div class="stat-item">
                    <h3>{{ $projectsStarted }}</h3>
                    <p class="highlight">In Progress</p>
                </div>
                <div class="stat-item">
                    <h3>{{ $stat ? $stat->current_streak_days : 0 }}</h3>
                    <p class="muted">Day streak</p>
                </div>
                <div class="stat-item">
                    <h3>{{ $totalXp }} ¢</h3>
                    <p class="muted">Coins (XP)</p>
                </div>
                <div class="stat-item">
                    <h3>{{ $peerReviewPoints }}</h3>
                    <p class="muted">Peer Review points</p>
                </div>
                <div class="stat-item">
                    <h3>{{ $codeReviewPoints }}</h3>
                    <p class="muted">Code Review points</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard cards -->
    <main class="dashboard">
        <article class="card">
            <div class="card-head">
                <h2>Events</h2>
                <a href="{{ route('public.events.index') }}">All events</a>
            </div>
            <div class="card-body">
                @if(count($events) > 0)
                    @foreach($events as $event)
                        <div class="agenda-item">
                            <div class="agenda-time">
                                <div class="start">{{ $event->start_time ?? '' }}</div>
                                <span class="end">{{ $event->end_time ?? '' }}</span>
                            </div>
                            <div class="agenda-bar"></div>
                            <div>
                                <div class="agenda-title">{{ $event->title }}</div>
                                <div class="agenda-desc">{{ $event->description ?? '' }}</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty-title">No upcoming events</div>
                    <p class="empty-text">You don't have any events available, try coming back later</p>
                @endif
            </div>
        </article>

        <article class="card">
            <div class="card-head">
                <h2>Your agenda</h2>
                <a href="{{ route('public.calendar') }}">Schedule</a>
            </div>
            <div class="card-body">
                @if(count($agenda) > 0)
                    @foreach($agenda as $day => $dayAgenda)
                        <div class="agenda-date">{{ $day }}</div>
                        @foreach($dayAgenda as $item)
                            <div class="agenda-item">
                                <div class="agenda-time">
                                    <div class="start">{{ $item['start'] }}</div>
                                    <span class="end">{{ $item['end'] }}</span>
                                </div>
                                <div class="agenda-bar"></div>
                                <div>
                                    <div class="agenda-title">{{ $item['title'] }}</div>
                                    <div class="agenda-desc">{!! $item['desc'] ?? '' !!}</div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                @else
                    <p class="empty-text">There are no events for today</p>
                @endif
            </div>
        </article>
    </main>

    <!-- Badges section -->
    <section class="badges-section">
        <div class="badges-header">
            <h2>Badges</h2>
            <a href="{{ route('gamification.achievements') }}">See all</a>
        </div>
        <div class="badges-list">
            @if(count($unlockedAchievements) > 0)
                @foreach($unlockedAchievements as $userAchievement)
                    @php $achievement = $userAchievement->achievement; @endphp
                    <div class="badge-card">
                        <div class="badge-icon">
                            <svg viewBox="0 0 40 40" width="40" height="40" shape-rendering="crispEdges">
                                @if($achievement && $achievement->icon)
                                    {!! $achievement->icon !!}
                                @else
                                    <rect x="12" y="4" width="16" height="4" fill="#f9a8d4"/>
                                    <rect x="8" y="8" width="24" height="8" fill="#f9a8d4"/>
                                    <rect x="12" y="16" width="16" height="4" fill="#f9a8d4"/>
                                    <rect x="12" y="20" width="16" height="4" fill="#fbbf24"/>
                                    <rect x="16" y="24" width="8" height="4" fill="#fbbf24"/>
                                    <rect x="16" y="28" width="8" height="4" fill="#f59e0b"/>
                                @endif
                            </svg>
                        </div>
                        <div class="badge-info">
                            <h3>{{ $achievement ? $achievement->name : 'Unknown' }}</h3>
                            <p class="muted">
                                @if($userAchievement->unlocked_at)
                                    Unlocked {{ $userAchievement->unlocked_at->diffForHumans() }}
                                @else
                                    In progress
                                @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            @else
                <p class="empty-text">No achievements yet. Start completing projects to earn badges!</p>
            @endif
        </div>
    </section>

@endsection
