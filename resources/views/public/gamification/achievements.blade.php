@extends('public.layouts.app')
@section('name', 'page')
@section('title', 'Achievements')

@section('content')
    @include('public.components.notifications-overlay')
    @include('public.components.user-menu-overlay')
    @include('public.components.activities-overlay')
    @include('public.components.projects-overlay')

    <main class="achievements-page">
        <section class="achievements-hero">
            <div>
                <p class="public-kicker">Gamification</p>
                <h1>Achievements</h1>
                <p>Разблокируй ачивки, проходя курсы, проекты и peer-review.</p>
            </div>
            <div class="achievements-summary">
                <div>
                    <strong>{{ $unlocked->count() }}</strong>
                    <span>Unlocked</span>
                </div>
                <div>
                    <strong>{{ $locked->count() }}</strong>
                    <span>Available</span>
                </div>
                <div>
                    <strong>{{ $hidden->count() }}</strong>
                    <span>Secret</span>
                </div>
            </div>
        </section>

        <section class="achievement-section achievement-section--unlocked">
            <div class="achievement-section-head">
                <h2>Разблокированные</h2>
                <span>{{ $unlocked->count() }}</span>
            </div>
            <div class="achievement-grid">
                @forelse($unlocked as $userAchievement)
                    <article class="achievement-card achievement-card--unlocked">
                        <div class="achievement-icon">{{ $userAchievement->achievement->icon ?? '🏆' }}</div>
                        <h3>{{ $userAchievement->achievement->name }}</h3>
                        <p>{{ $userAchievement->achievement->description }}</p>
                        <div class="achievement-meta achievement-meta--success">
                            Разблокирована {{ $userAchievement->unlocked_at->format('d.m.Y') }}
                        </div>
                    </article>
                @empty
                    <div class="public-empty-state">
                        <div>🏁</div>
                        <h3>Пока нет разблокированных ачивок</h3>
                        <p>Продолжай учиться и закрывать проекты, чтобы получить первые награды.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="achievement-section">
            <div class="achievement-section-head">
                <h2>Доступные</h2>
                <span>{{ $locked->count() }}</span>
            </div>
            <div class="achievement-grid">
                @forelse($locked as $achievement)
                    <article class="achievement-card">
                        <div class="achievement-icon achievement-icon--locked">{{ $achievement->icon ?? '🏆' }}</div>
                        <h3>{{ $achievement->name }}</h3>
                        <p>{{ $achievement->description }}</p>
                        <div class="achievement-meta-row">
                            @if($achievement->xp_reward > 0)
                                <span class="achievement-pill achievement-pill--xp">+{{ $achievement->xp_reward }} XP</span>
                            @endif
                            <span class="achievement-pill achievement-pill--rarity achievement-rarity-{{ $achievement->rarity }}">
                                {{ ucfirst($achievement->rarity) }}
                            </span>
                        </div>
                    </article>
                @empty
                    <div class="public-empty-state">
                        <div>✨</div>
                        <h3>Все ачивки разблокированы</h3>
                        <p>Новых доступных ачивок пока нет.</p>
                    </div>
                @endforelse
            </div>
        </section>

        @if($hidden->count() > 0)
            <section class="achievement-section achievement-section--hidden">
                <div class="achievement-section-head">
                    <h2>Секретные</h2>
                    <span>{{ $hidden->count() }}</span>
                </div>
                <div class="achievement-grid">
                    @foreach($hidden as $achievement)
                        <article class="achievement-card achievement-card--hidden">
                            <div class="achievement-icon">🔒</div>
                            <h3>???</h3>
                            <p>Секретная ачивка</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
@endsection
