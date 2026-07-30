@extends('public.layouts.app')
@section('name', 'page')
@section('title', 'Progress — 21-LMS')
@section('nav-progress', 'active')
@section('content')

    <!-- Notifications overlay -->
    @include('public.components.notifications-overlay')

    @include('public.components.user-menu-overlay')

    <!-- Activities overlay -->
    @include('public.components.activities-overlay')

    <!-- Projects overlay -->
    @include('public.components.projects-overlay')

    @php
        $displayName = $user->username ?? $user->name ?? 'Guest';
        $roleName = $user->isAdmin() ? 'Admin' : 'Core program';
        $initials = collect(preg_split('/[^a-zA-Z0-9а-яА-ЯёЁ]+/u', $displayName))->filter()->take(2)->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('') ?: '?';
        $maxSkill = max(max($skills ?: [1]), 1);
        $skillItems = collect($skills)->map(fn($xp, $name) => ['name' => $name, 'xp' => $xp])->values();
        $radarSize = 560;
        $radarCenter = $radarSize / 2;
        $radarRadius = 160;
        $skillCount = max(1, $skillItems->count());
        $radarPoint = function ($value, $index) use ($radarCenter, $radarRadius, $skillCount) {
            $angle = (2 * pi() / $skillCount) * $index - pi() / 2;
            $r = $radarRadius * $value;
            return [$radarCenter + $r * cos($angle), $radarCenter + $r * sin($angle)];
        };
        $radarRings = collect([0.25, 0.5, 0.75, 1])->map(function ($ratio) use ($skillItems, $radarPoint) {
            return $skillItems->map(function ($skill, $i) use ($ratio, $radarPoint) {
                [$x, $y] = $radarPoint($ratio, $i);
                return round($x, 2) . ',' . round($y, 2);
            })->join(' ');
        });
        $radarDataPoints = $skillItems->map(function ($skill, $i) use ($maxSkill, $radarPoint) {
            [$x, $y] = $radarPoint(min(1, $skill['xp'] / $maxSkill), $i);
            return round($x, 2) . ',' . round($y, 2);
        })->join(' ');

        $graphWidth = 640;
        $graphHeight = 260;
        $padLeft = 60;
        $padRight = 30;
        $padTop = 30;
        $padBottom = 40;
        $chartWidth = $graphWidth - $padLeft - $padRight;
        $chartHeight = $graphHeight - $padTop - $padBottom;
        $maxGraphXp = max(1, collect($xpGraph)->max('xp') ?: 1);
        $graphPoints = collect($xpGraph)->values()->map(function ($point, $i) use ($xpGraph, $padLeft, $padTop, $chartWidth, $chartHeight, $maxGraphXp) {
            $count = max(1, count($xpGraph) - 1);
            $x = $padLeft + ($i / $count) * $chartWidth;
            $y = $padTop + $chartHeight - (($point['xp'] ?? 0) / $maxGraphXp) * $chartHeight;
            return ['x' => $x, 'y' => $y, 'date' => $point['date'], 'xp' => $point['xp'] ?? 0];
        });
        $graphLine = $graphPoints->map(fn($p, $i) => ($i === 0 ? 'M' : 'L') . ' ' . round($p['x'], 2) . ' ' . round($p['y'], 2))->join(' ');
    @endphp

    <main class="profile-page">
        <div class="profile-header">
            <div class="profile-header-top">
                <a href="{{ route('public.home') }}" class="profile-back" aria-label="Back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                </a>
                <h1>{{ $displayName }}</h1>
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
                    <div class="profile-avatar">
                        <span style="font-size:42px;font-weight:900;color:#94a3b8;">{{ $initials }}</span>
                    </div>
                    <h2 class="profile-name">{{ $displayName }}</h2>
                    <p class="profile-role">{{ $roleName }}</p>

                    <div class="profile-level-row">
                        <span>lvl <span>{{ $currentLevel }}</span></span>
                        <span class="level-percent">{{ $levelProgress }}%</span>
                    </div>
                    <div class="profile-progress-bar">
                        <div class="profile-progress-fill" style="--progress-width: {{ $levelProgress }}%;"></div>
                    </div>

                    <h3 class="profile-points-title">Points</h3>
                    <div class="profile-points">
                        <span class="point-badge xp">{{ $totalXp }} XP</span>
                        <span class="point-badge prp">{{ $stats['reviews_given'] }} PRP</span>
                        <span class="point-badge crp">{{ $stats['reviews_received'] }} CRP</span>
                        <span class="point-badge coins">{{ $stats['passed_projects'] }} Passed</span>
                    </div>

                    <h3 class="profile-feedback-title">Peer feedback</h3>
                    <div class="peer-feedback-list">
                        <div class="peer-feedback-row"><div class="peer-feedback-label"><span class="peer-feedback-icon">✓</span>Reviews given</div><span class="peer-feedback-value">{{ $stats['reviews_given'] }}</span></div>
                        <div class="peer-feedback-row"><div class="peer-feedback-label"><span class="peer-feedback-icon">✓</span>Reviews received</div><span class="peer-feedback-value">{{ $stats['reviews_received'] }}</span></div>
                        <div class="peer-feedback-row"><div class="peer-feedback-label"><span class="peer-feedback-icon">✓</span>Projects passed</div><span class="peer-feedback-value">{{ $stats['passed_projects'] }}</span></div>
                        <div class="peer-feedback-row"><div class="peer-feedback-label"><span class="peer-feedback-icon">✓</span>Projects failed</div><span class="peer-feedback-value">{{ $stats['failed_projects'] }}</span></div>
                    </div>

                    <div class="profile-reviews-row">
                        <span>All Peer Reviews</span>
                        <span>{{ $stats['reviews_given'] + $stats['reviews_received'] }}</span>
                    </div>

                    <div class="profile-contacts">
                        <div class="profile-contact-row">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <span>{{ $user->email }}</span>
                        </div>
                        <div class="profile-status-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            {{ $nextLevel ? 'Next level: ' . $nextRequired . ' XP' : 'MAX LEVEL' }}
                        </div>
                    </div>

                    <div class="profile-section-card">
                        <div class="profile-section-head">
                            <h3>Penalties</h3>
                            <a href="#penalties">See all</a>
                        </div>
                        <p class="empty-text">You have no penalties</p>
                    </div>

                    <div class="tribe-contribution-card">
                        <div class="tribe-contribution-icon">
                            <svg viewBox="0 0 64 64" width="34" height="34"><rect x="12" y="8" width="40" height="32" rx="8" fill="#2a3142" stroke="#3d4559" stroke-width="2"/><circle cx="22" cy="22" r="5" fill="#8bea9a"/><circle cx="42" cy="22" r="5" fill="#8bea9a"/><circle cx="22" cy="22" r="2" fill="#171d2b"/><circle cx="42" cy="22" r="2" fill="#171d2b"/><rect x="28" y="32" width="8" height="4" rx="2" fill="#7dd3fc"/></svg>
                        </div>
                        <div class="tribe-contribution-info">
                            <div class="tribe-contribution-name">Computers <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg> {{ $stats['passed_projects'] + $stats['reviews_given'] }}</div>
                            <p>The contribution is {{ $stats['passed_projects'] + $stats['reviews_given'] }} tribe points</p>
                        </div>
                    </div>

                    <div class="profile-section-card">
                        <div class="profile-section-head">
                            <h3>Badges</h3>
                            <a href="{{ route('gamification.achievements') }}">See all</a>
                        </div>
                        <div class="profile-badges-list">
                            @forelse($recentAchievements->take(3) as $userAchievement)
                                <div class="profile-badge-item">
                                    <div class="profile-badge-icon"><span style="font-size:30px;">{{ $userAchievement->achievement->icon ?? '🏆' }}</span></div>
                                    <span class="profile-badge-title">{{ $userAchievement->achievement->name }}</span>
                                </div>
                            @empty
                                <p class="empty-text">No badges yet</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="profile-section-card">
                        <div class="profile-section-head">
                            <h3>Logtime on iMac</h3>
                            <button class="profile-dropdown">Weekly</button>
                        </div>
                        <div class="logtime-imac">
                            <div class="logtime-imac-ring">
                                @php $circ = 2 * pi() * 42; $offset = $circ * (1 - min(1, ($logtime['average'] ?? 0) / 10)); @endphp
                                <svg viewBox="0 0 100 100" width="100" height="100">
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="#e5e7eb" stroke-width="8" />
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="#7dd3fc" stroke-width="8" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="round" transform="rotate(-90 50 50)" />
                                    <text x="50" y="56" text-anchor="middle" font-size="22" font-weight="800" fill="#111827">{{ $logtime['average'] ?? 0 }}</text>
                                </svg>
                            </div>
                            <p class="logtime-imac-label">AVERAGE HOURS PER DAY</p>
                            <div class="logtime-imac-week">{{ $logtime['week'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="profile-content">
                    <div class="profile-content-card">
                        <div class="profile-content-head">
                            <h2>Skills</h2>
                        </div>
                        <div class="skills-radar">
                            <svg viewBox="0 0 {{ $radarSize }} {{ $radarSize }}" width="100%" height="100%">
                                @foreach($radarRings as $ring)
                                    <polygon points="{{ $ring }}" fill="none" stroke="#e5e7eb" stroke-width="1" />
                                @endforeach
                                @foreach($skillItems as $skill)
                                    @php [$x, $y] = $radarPoint(1, $loop->index); @endphp
                                    <line x1="{{ $radarCenter }}" y1="{{ $radarCenter }}" x2="{{ $x }}" y2="{{ $y }}" stroke="#e5e7eb" stroke-width="1" />
                                @endforeach
                                <polygon points="{{ $radarDataPoints }}" fill="rgba(139, 234, 154, 0.25)" stroke="#8bea9a" stroke-width="2" />
                                @foreach($skillItems as $skill)
                                    @php [$x, $y] = $radarPoint(min(1, $skill['xp'] / $maxSkill), $loop->index); [$lx, $ly] = $radarPoint(1.28, $loop->index); $anchor = $lx > $radarCenter + 5 ? 'start' : ($lx < $radarCenter - 5 ? 'end' : 'middle'); @endphp
                                    <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="#8bea9a" stroke="#ffffff" stroke-width="2" />
                                    <text x="{{ $lx }}" y="{{ $ly }}" text-anchor="{{ $anchor }}" font-size="9" fill="#6b7280" font-weight="600">{{ $skill['name'] }}</text>
                                @endforeach
                            </svg>
                        </div>
                    </div>

                    <div class="profile-content-card">
                        <div class="profile-content-head">
                            <h2>XP graph</h2>
                        </div>
                        <div class="xp-graph">
                            <svg viewBox="0 0 {{ $graphWidth }} {{ $graphHeight }}" width="100%" height="100%">
                                <line x1="{{ $padLeft }}" y1="{{ $padTop + $chartHeight }}" x2="{{ $padLeft + $chartWidth }}" y2="{{ $padTop + $chartHeight }}" stroke="#e5e7eb" stroke-width="1" />
                                <line x1="{{ $padLeft }}" y1="{{ $padTop }}" x2="{{ $padLeft }}" y2="{{ $padTop + $chartHeight }}" stroke="#e5e7eb" stroke-width="1" />
                                <line x1="{{ $padLeft }}" y1="{{ $padTop + $chartHeight / 2 }}" x2="{{ $padLeft + $chartWidth }}" y2="{{ $padTop + $chartHeight / 2 }}" stroke="#f3f4f6" stroke-width="1" />
                                <text x="{{ $padLeft - 10 }}" y="{{ $padTop + $chartHeight + 4 }}" text-anchor="end" font-size="11" fill="#9ca3af" font-weight="600">0</text>
                                <text x="{{ $padLeft - 10 }}" y="{{ $padTop + $chartHeight / 2 + 4 }}" text-anchor="end" font-size="11" fill="#9ca3af" font-weight="600">{{ round($maxGraphXp / 2) }} XP</text>
                                <path d="{{ $graphLine }}" fill="none" stroke="#7dd3fc" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                @foreach($graphPoints as $point)
                                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5" fill="#7dd3fc" stroke="#fff" stroke-width="2" />
                                    <text x="{{ $point['x'] }}" y="{{ $padTop + $chartHeight + 18 }}" text-anchor="middle" font-size="11" fill="#9ca3af" font-weight="600">{{ $point['date'] }}</text>
                                @endforeach
                            </svg>
                        </div>
                    </div>

                    <div class="profile-content-card">
                        <div class="profile-content-head">
                            <h2>Logtime</h2>
                        </div>
                        <div class="logtime-chart">
                            @php $barWidth = 36; $gap = 68; $maxHours = max(10, collect($logtime['days'])->max('imac') ?: 10); @endphp
                            <svg viewBox="0 0 640 260" width="100%" height="100%">
                                <line x1="50" y1="210" x2="610" y2="210" stroke="#e5e7eb" stroke-width="1" />
                                <line x1="50" y1="30" x2="50" y2="210" stroke="#e5e7eb" stroke-width="1" />
                                <text x="40" y="214" text-anchor="end" font-size="11" fill="#9ca3af" font-weight="600">0H</text>
                                <text x="40" y="124" text-anchor="end" font-size="11" fill="#9ca3af" font-weight="600">{{ round($maxHours / 2) }}H</text>
                                @foreach($logtime['days'] as $i => $day)
                                    @php $x = 80 + $i * $gap; $h = ($day['imac'] / $maxHours) * 180; $y = 210 - $h; @endphp
                                    <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $h }}" rx="6" fill="#8bea9a" opacity="0.75" />
                                    <text x="{{ $x + $barWidth / 2 }}" y="230" text-anchor="middle" font-size="11" fill="#9ca3af" font-weight="600">{{ $day['day'] }}</text>
                                @endforeach
                            </svg>
                        </div>
                        <div class="logtime-legend">
                            <span><span class="legend-dot campus"></span>Campus</span>
                            <span><span class="legend-dot imac"></span>iMac</span>
                        </div>
                        <div class="logtime-week">{{ $logtime['week'] }}</div>
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
