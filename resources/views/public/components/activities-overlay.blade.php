{{-- Activities overlay — shared component.
     Usage: @include('public.components.activities-overlay') --}}
<div class="activities-overlay" id="activitiesOverlay">
    <div class="activities-panel">
        <button class="activities-close" aria-label="Close activities">×</button>
        <div class="activities-list">
            <a href="{{ route('public.tribes') }}" class="activity-btn" data-activity="tribes">Tribes</a>
            <a href="{{ route('public.events.index') }}" class="activity-btn" data-activity="events">Events</a>
        </div>
        <div class="activities-info">
            <h2>Activities</h2>
            <p>In this section you will find various activities which will make training more exciting</p>
        </div>
    </div>
</div>
