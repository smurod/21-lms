<!-- User menu overlay — Profile link removed -->
<div class="user-menu-overlay" id="userMenuOverlay" aria-hidden="true">
    <div class="user-menu-panel" role="dialog" aria-modal="true" aria-labelledby="userMenuTitle">
        <div class="user-menu-head">
            <h2 class="user-name" id="userMenuTitle" data-bind="userName">{{ auth()->check() ? (auth()->user()->username ?? auth()->user()->name) : 'Guest' }}</h2>
            <span class="user-level" data-bind="userLevel">lvl {{ auth()->check() ? (auth()->user()->level ?? 0) : 0 }}</span>
        </div>
        <div class="user-menu-list">
            {{-- Profile link removed per request --}}
            <button class="user-menu-item notifications-toggle">Notifications</button>
            <a class="user-menu-item" href="#password">Change password</a>
            <a class="user-menu-item" href="#wallet">Wallet</a>
            <form method="POST" action="{{ route('logout') }}" class="user-menu-logout">
                @csrf
                <button type="submit" class="user-menu-item user-menu-item--logout">Log out</button>
            </form>
        </div>
    </div>
</div>
