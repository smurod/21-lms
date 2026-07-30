{{-- Projects overlay — shared component.
     Usage: @include('public.components.projects-overlay') --}}
<div class="activities-overlay" id="projectsOverlay">
    <div class="activities-panel">
        <button class="activities-close" aria-label="Close projects">×</button>
        <div class="activities-list">
            <a href="{{ route('public.projects.index') }}" class="activity-btn" data-activity="projects">Projects</a>
            <a href="{{ route('reviews.index') }}" class="activity-btn" data-activity="reviews">Peer reviews</a>
            <a href="{{ route('public.projects.map') }}" class="activity-btn" data-activity="project-map">Project map</a>
            <a href="#video" class="activity-btn" data-activity="video">Video</a>
            <a href="{{ route('public.gitlab') }}" class="activity-btn" data-activity="gitlab">My Projects in Gitlab</a>
        </div>
        <div class="activities-info">
            <h2>Projects</h2>
            <p>The section contains the main tools for training at School 21. Get basic knowledge from videos, carry out projects and monitor your progress on the project map.</p>
        </div>
    </div>
</div>
