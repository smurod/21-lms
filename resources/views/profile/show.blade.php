@extends('public.layouts.app')
@section('name', 'page')
@section('content')

    <!-- Notifications overlay -->
    @include('public.components.notifications-overlay')

    @include('public.components.user-menu-overlay')

    <!-- Activities overlay -->
    @include('public.components.activities-overlay')

    <!-- Projects overlay -->
    @include('public.components.projects-overlay')

    <main class="profile-page">
        <div class="profile-header">
            <div class="profile-header-top">
                <a href="{{ route('public.home') }}" class="profile-back" aria-label="Back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                </a>
                <h1 data-bind="userName">{{ auth()->user()->username ?? auth()->user()->name ?? 'Guest' }}</h1>
            </div>

            <div class="profile-tabs">
                <button class="profile-tab active" data-tab="about">About a participant</button>
                <button class="profile-tab" data-tab="notifications">Notifications</button>
                <button class="profile-tab" data-tab="password">Change password</button>
                <button class="profile-tab" data-tab="wallet">Wallet</button>
            </div>
        </div>

        <div class="profile-body">
            <section class="profile-panel active" data-panel="about">
                <div class="profile-sidebar">
                    <div class="profile-avatar" data-bind="avatarFallback">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
                    </div>
                    <h2 class="profile-name" data-bind="userName">{{ auth()->user()->username ?? auth()->user()->name ?? 'Guest' }}</h2>
                    <p class="profile-role" data-bind="userRole">{{ auth()->user()->isAdmin() ? 'Admin' : 'Core program' }}</p>

                    <div class="profile-level-row">
                        <span>lvl <span data-bind="profileLevel">{{ auth()->user()->level ?? 1 }}</span></span>
                        <span class="level-percent" data-bind="levelPercent">{{
                            (function(){
                                $u = auth()->user();
                                $levels = config('xp.levels', []);
                                $lvl = $u->current_level ?? $u->level ?? 1;
                                $curXp = $u->total_xp ?? 0;
                                $curReq = $levels[$lvl]['xp_required'] ?? 0;
                                $nextReq = $levels[$lvl+1]['xp_required'] ?? ($curReq+1000);
                                $span = max(1, $nextReq - $curReq);
                                $into = max(0, $curXp - $curReq);
                                return min(99, round($into / $span * 100));
                            })()
                        }}%</span>
                    </div>
                    <div class="profile-progress-bar">
                        <div class="profile-progress-fill" data-bind="profileProgress" style="--progress-width: {{
                            (function(){
                                $u = auth()->user();
                                $levels = config('xp.levels', []);
                                $lvl = $u->current_level ?? $u->level ?? 1;
                                $curXp = $u->total_xp ?? 0;
                                $curReq = $levels[$lvl]['xp_required'] ?? 0;
                                $nextReq = $levels[$lvl+1]['xp_required'] ?? ($curReq+1000);
                                $span = max(1, $nextReq - $curReq);
                                $into = max(0, $curXp - $curReq);
                                return min(99, round($into / $span * 100));
                            })()
                        }}%;"></div>
                    </div>

                    <h3 class="profile-points-title">Points</h3>
                    <div class="profile-points">
                        <span class="point-badge xp" data-bind="userTotalXp">{{ auth()->user()->total_xp ?? 0 }} XP</span>
                        <span class="point-badge prp">5 PRP</span>
                        <span class="point-badge crp">5 CRP</span>
                        <span class="point-badge coins">210 Coins</span>
                    </div>

                    <h3 class="profile-feedback-title">Peer feedback</h3>
                    <div class="peer-feedback-list" data-bind="peerFeedback">
                        <!-- Rendered by app.js -->
                    </div>

                    <div class="profile-reviews-row">
                        <span>All Peer Reviews</span>
                        <span data-bind="peerReviews">2</span>
                    </div>

                    <div class="profile-contacts" data-bind="profileContacts">
                        <!-- Rendered by app.js -->
                    </div>

                    <div class="profile-section-card">
                        <div class="profile-section-head">
                            <h3>Penalties</h3>
                            <a href="#penalties">See all</a>
                        </div>
                        <div data-bind="profilePenalties">
                            <!-- Rendered by app.js -->
                        </div>
                    </div>

                    <div data-bind="profileTribeContribution">
                        <!-- Rendered by app.js -->
                    </div>

                    <div class="profile-section-card">
                        <div class="profile-section-head">
                            <h3>Badges</h3>
                            <a href="#">See all</a>
                        </div>
                        <div class="profile-badges-list" data-bind="profileBadges">
                            <!-- Rendered by app.js -->
                        </div>
                    </div>

                    <div class="profile-section-card">
                        <div class="profile-section-head">
                            <h3>Logtime on iMac</h3>
                            <button class="profile-dropdown">Weekly</button>
                        </div>
                        <div class="logtime-imac" data-bind="profileLogtimeOniMac">
                            <!-- Rendered by app.js -->
                        </div>
                    </div>
                </div>

                <div class="profile-content">
                    <div class="profile-content-card">
                        <div class="profile-content-head">
                            <h2>Skills</h2>
                        </div>
                        <div class="skills-radar" data-bind="skillsRadar">
                            <!-- Rendered by app.js -->
                        </div>
                    </div>

                    <div class="profile-content-card">
                        <div class="profile-content-head">
                            <h2>XP graph</h2>
                        </div>
                        <div class="xp-graph" data-bind="profileXpGraph">
                            <!-- Rendered by app.js -->
                        </div>
                    </div>

                    <div class="profile-content-card">
                        <div class="profile-content-head">
                            <h2>Logtime</h2>
                        </div>
                        <div class="logtime-chart" data-bind="profileLogtime">
                            <!-- Rendered by app.js -->
                        </div>
                        <div class="logtime-legend">
                            <span><span class="legend-dot campus"></span>Campus</span>
                            <span><span class="legend-dot imac"></span>iMac</span>
                        </div>
                        <div class="logtime-week">13 March – 19 March 2023</div>
                    </div>
                </div>
            </section>

            <section class="profile-panel" data-panel="notifications">
                <div class="profile-content">
                    <div class="profile-content-head">
                        <h2>Notifications</h2>
                    </div>
                    <p class="empty-text">Notification settings will appear here.</p>
                </div>
            </section>

            <section class="profile-panel" data-panel="password">
                <div class="profile-content">
                    <div class="profile-content-head">
                        <h2>Change password</h2>
                    </div>
                    <p class="empty-text">Password change form will appear here.</p>
                </div>
            </section>

            <section class="profile-panel" data-panel="wallet">
                <div class="profile-content">
                    <div class="profile-content-head">
                        <h2>Wallet</h2>
                    </div>
                    <p class="empty-text">Wallet information will appear here.</p>
                </div>
            </section>
        </div>
    </main>

@endsection
