<header class="site-header @yield('header-class')">
    <div class="header-bg" aria-hidden="true"></div>

    <div class="header-inner">
        <a class="brand" href="{{ route('public.home') }}">
      <span class="brand-mark">
        <span class="brand-mark-21">21</span>
        <span class="brand-mark-lms">LMS</span>
      </span>
            <span class="brand-text">
        <span class="brand-title">21-LMS</span>
        <span class="brand-sub">GROW FOCUS REPEAT</span>
      </span>
        </a>

        <nav class="main-nav">
            <a href="{{ route('public.home') }}" class="@yield('nav-dashboard')">Dashboard</a>
            <a href="{{ route('public.calendar') }}" class="@yield('nav-calendar')">Calendar</a>
            {{-- Progress / Profile link removed --}}
            {{-- <a href="{{ route('public.progress') }}">Progress</a> --}}
            <a href="{{ route('public.projects.index') }}" class="projects-nav-toggle @yield('nav-projects')">Projects</a>
            <a href="{{ route('public.activities') }}" class="activities-toggle @yield('nav-activities')">Activities</a>
            <a href="{{ route('public.more') }}">More</a>
        </nav>

        <div class="header-tools">
            <a class="search-btn" href="{{ route('public.projects.map') }}#search">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <span>Search</span>
            </a>

            <a class="icon-btn" href="{{ \Illuminate\Support\Facades\Route::has('chats.index') ? route('chats.index') : '#chats' }}" title="Chats">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </a>

            <a class="icon-btn project-map-toggle @yield('map-toggle-active')" href="{{ route('public.projects.map') }}" aria-label="Project map" title="Project map">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.08" y1="13.5" x2="15.92" y2="17.5"/><line x1="15.92" y1="6.5" x2="8.08" y2="10.5"/></svg>
            </a>

            @php
                $unreadCount = auth()->check() ? \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count() : 0;
            @endphp
            <button class="icon-btn notifications-toggle{{ $unreadCount > 0 ? ' has-badge' : '' }}" type="button" aria-label="Notifications" aria-haspopup="true" aria-expanded="false" aria-controls="notificationsOverlay">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                @if($unreadCount > 0)
                    <span class="badge">{{ $unreadCount }}</span>
                @endif
            </button>

            <button class="user-menu-toggle" type="button" aria-label="User menu" aria-haspopup="true" aria-expanded="false" aria-controls="userMenuOverlay">
                <span class="dots"><span></span><span></span><span></span></span>
                <span class="avatar">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
        </span>
            </button>
        </div>
    </div>
</header>
