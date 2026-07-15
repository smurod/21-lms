@extends('public.layouts.app')
@section('name', 'page')
@section('nav-activities', 'active')
@section('content')

    <!-- Notifications overlay -->
    @include('public.components.notifications-overlay')

    @include('public.components.user-menu-overlay')

    <!-- Activities overlay -->
    @include('public.components.activities-overlay')

    <!-- Projects overlay -->
    @include('public.components.projects-overlay')

    <main class="tribes-page">
        <div class="tribes-header">
            <a href="{{ route('public.home') }}" class="tribes-back" aria-label="Back">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            </a>
            <h1 data-bind="tribePageTitle">{{ auth()->user()->username ?? auth()->user()->name ?? 'Tribe' }}</h1>
        </div>

        <div class="tribes-tabs">
            <button class="tribes-tab active" data-tab="tournament">Tribe Tournament</button>
            <button class="tribes-tab" data-tab="my-tribe">My tribe</button>
        </div>

        <!-- Tribe Tournament panel -->
        <section class="tribes-panel active" data-panel="tournament">
            <div class="tournament-banner">
                <div class="tournament-banner-bg" aria-hidden="true"></div>
                <div class="tournament-banner-content">
                    <h2>Tribe Tournament</h2>
                    <ul class="tournament-meta">
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span>{{ now()->format('j M Y') }} – {{ now()->addMonths(6)->format('j M Y') }}</span>
                        </li>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span>— tribes</span>
                        </li>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span>{{ \App\Models\User::count() }} members</span>
                        </li>
                    </ul>
                    <div class="tournament-my-place">
                        <div class="place-tribe">
                            <div class="place-icon">
                                <svg viewBox="0 0 64 64" width="28" height="28">
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
                            <span class="place-tribe-name">Computers</span>
                        </div>
                        <div class="place-divider"></div>
                        <div class="place-rank">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
                            <span>2 place</span>
                        </div>
                    </div>
                </div>
                <div class="tournament-trophy" aria-hidden="true">
                    <svg viewBox="0 0 200 200" width="180" height="180">
                        <defs>
                            <linearGradient id="gold" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#fde68a"/>
                                <stop offset="50%" stop-color="#fbbf24"/>
                                <stop offset="100%" stop-color="#d97706"/>
                            </linearGradient>
                            <linearGradient id="goldLight" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#fffbeb"/>
                                <stop offset="100%" stop-color="#f59e0b"/>
                            </linearGradient>
                        </defs>
                        <path d="M45 55h110v10c0 35-25 65-55 65s-55-30-55-65V55z" fill="url(#gold)"/>
                        <path d="M35 55h130v8a8 8 0 0 1-8 8h-114a8 8 0 0 1-8-8v-8z" fill="#b45309"/>
                        <path d="M45 55h110v5a5 5 0 0 1-5 5h-100a5 5 0 0 1-5-5v-5z" fill="#f59e0b"/>
                        <rect x="85" y="125" width="30" height="35" rx="4" fill="url(#goldLight)"/>
                        <path d="M70 160h60l-10 15h-40l-10-15z" fill="#b45309"/>
                        <path d="M30 63c-5 0-10-5-10-15s8-20 20-20" fill="none" stroke="#fbbf24" stroke-width="4" stroke-linecap="round"/>
                        <path d="M170 63c5 0 10-5 10-15s-8-20-20-20" fill="none" stroke="#fbbf24" stroke-width="4" stroke-linecap="round"/>
                        <circle cx="100" cy="85" r="22" fill="#fff7ed" opacity="0.35"/>
                    </svg>
                </div>
            </div>

            <div class="tournament-list" data-bind="tournamentList">
                <!-- Rendered by app.js -->
            </div>
        </section>

        <!-- My tribe panel -->
        <section class="tribes-panel" data-panel="my-tribe">
            <div class="tribes-body">
                <section class="tribes-members">
                    @php $me = auth()->user(); @endphp
                    <div class="member-card active">
                        <div class="member-avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
                        </div>
                        <span class="member-name">{{ $me->username ?? $me->name ?? 'you' }}</span>
                        <span class="member-level">level {{ $me->level ?? 1 }}</span>
                    </div>

                    @foreach(\App\Models\User::where('id','!=', $me->id)->latest()->take(3)->get() as $u)
                        <div class="member-card">
                            <div class="member-avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
                            </div>
                            <span class="member-name">{{ $u->username ?? $u->name }}</span>
                            <span class="member-level">level {{ $u->level ?? 1 }}</span>
                        </div>
                    @endforeach
                </section>

                <aside class="tribes-info-card">
                    <div class="tribes-info-top">
                        <div class="tribes-robot">
                            <svg viewBox="0 0 64 64" width="42" height="42">
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
                    </div>

                    <h2 class="tribes-info-title">Computers</h2>

                    <div class="tribes-about">
                        <h3>About</h3>
                        <ul class="tribes-meta">
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                <span>School 21</span>
                            </li>
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span>{{ now()->subMonths(3)->format('j F Y') }} – {{ now()->addMonths(3)->format('j F Y') }}</span>
                            </li>
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2\"/><circle cx=\"9\" cy=\"7\" r=\"4\"/><path d=\"M23 21v-2a4 4 0 0 0-3-3.87\"/><path d=\"M16 3.13a4 4 0 0 1 0 7.75\"/></svg>
                                <span>{{ \App\Models\User::count() }} members</span>
                            </li>
                            <li>
                                <svg xmlns=\"http://www.w3.org/2000/svg\" width=\"18\" height=\"18\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><circle cx=\"12\" cy=\"8\" r=\"7\"/><polyline points=\"8.21 13.89 7 23 12 20 17 23 15.79 13.88\"/></svg>
                                    <span>{{ auth()->user()->total_xp ?? 0 }} tribe points</span>
                            </li>
                        </ul>
                    </div>

                    <div class="tribes-elder">
                        <div class="elder-avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
                        </div>
                        <div class="elder-info">
                            <span class="elder-name">{{ auth()->user()->username ?? auth()->user()->name ?? '—' }}</span>
                            <span class="elder-role">{{ auth()->user()->isAdmin() ? 'Admin' : 'Member' }}</span>
                        </div>
                        <span class="elder-label">Tribe elder</span>
                    </div>
                </aside>
            </div>
        </section>
    </main>

@endsection
