{{-- Notifications overlay — shared component.
     Usage: @include('public.components.notifications-overlay') --}}
<div class="notifications-overlay" id="notificationsOverlay" aria-hidden="true">
    <div class="notifications-panel" role="dialog" aria-modal="true" aria-labelledby="notificationsTitle">
        <div class="notifications-head">
            <h2 id="notificationsTitle">Notifications</h2>
            <button class="notifications-close" aria-label="Close notifications">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="notifications-filters">
            <button class="notifications-filter active" data-filter="all">All</button>
            <button class="notifications-filter" data-filter="profile">Profile</button>
            <button class="notifications-filter" data-filter="projects">Projects</button>
            <button class="notifications-filter" data-filter="awards">Awards</button>
        </div>

        <div class="notifications-list" data-bind="notificationsList">
            <!-- Rendered by app.js -->
        </div>
    </div>
</div>
