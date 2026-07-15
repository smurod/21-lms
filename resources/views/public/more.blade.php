@extends('public.layouts.app')
@section('name', 'page')
@section('title', 'More — School 21')
@section('content')

    <!-- Notifications overlay -->
    @include('public.components.notifications-overlay')

    @include('public.components.user-menu-overlay')

    <!-- Activities overlay -->
    @include('public.components.activities-overlay')

    <!-- Projects overlay -->
    @include('public.components.projects-overlay')

    @php $items = [
        ['icon'=>'user','title'=>'Account','desc'=>'Profile settings, notifications and privacy.','route'=>'public.home'],
        ['icon'=>'book-open','title'=>'Courses','desc'=>'Browse all available learning courses.','route'=>'public.courses.index'],
        ['icon'=>'folder','title'=>'Projects','desc'=>'View all your current and past projects.','route'=>'public.projects.index'],
        ['icon'=>'settings','title'=>'Settings','desc'=>'Application preferences and configuration.','route'=>'public.settings'],
        ['icon'=>'help-circle','title'=>'Support','desc'=>'Documentation and help center links.','route'=> Route::has('public.support') ? 'public.support' : 'public.more'],
        ['icon'=>'log-out','title'=>'Logout','desc'=>'Sign out of your account.','route'=>'logout'],
    ]; @endphp

    <main class="more-page">
        <div class="more-header">
            <h1>More</h1>
            <p>Everything else in one place</p>
        </div>

        <div class="more-grid">
            @foreach($items as $item)
                @php
                    /* Inline SVG per item — single source, no external assets */
                    $icons = [
                        'user'        => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
                        'book-open'   => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
                        'folder'      => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',
                        'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
                        'help-circle' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
                        'log-out'     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
                    ];
                    $svg = $icons[$item['icon']] ?? $icons['user'];
                @endphp

                @if($item['route'] === 'logout')
                    <form method="POST" action="{{ route('logout') }}" class="more-card-form">
                        @csrf
                        <button type="submit" class="more-card more-card--logout">
                            <div class="more-icon more-icon--logout">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $svg !!}</svg>
                            </div>
                            <div class="more-body">
                                <h3>{{ $item['title'] }}</h3>
                                <p>{{ $item['desc'] }}</p>
                            </div>
                        </button>
                    </form>
                @else
                    <a href="{{ route($item['route']) }}" class="more-card">
                        <div class="more-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $svg !!}</svg>
                        </div>
                        <div class="more-body">
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['desc'] }}</p>
                        </div>
                    </a>
                @endif
            @endforeach
        </div>
    </main>

@endsection
