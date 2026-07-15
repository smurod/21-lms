{{-- ============================================================
     Public events list (School 21 style): shared partial.
     Used by:
       - public/events/index.blade.php  (standalone Events page)
       - public/calendar.blade.php      (Events tab via @include)
     Expects: $events — collection of published CalendarEvent
              (with user, project relations loaded).
     ============================================================ --}}
<div class="calendar-list">
    @forelse ($events as $event)
        <a class="calendar-list-item" href="{{ route('public.events.show', $event->id) }}">
            <div class="calendar-list-when">
                <span class="calendar-list-date">{{ $event->date->format('j M') }}</span>
                <span class="calendar-list-time">{{ substr($event->start_time, 0, 5) }} – {{ substr($event->end_time, 0, 5) }}</span>
            </div>
            <div class="calendar-list-bar event"></div>
            <div class="calendar-list-info">
                <h3>{{ $event->title }}</h3>
                @if ($event->description)<p>{{ \Illuminate\Support\Str::limit($event->description, 140) }}</p>@endif
                <div class="calendar-list-meta">
                    <span class="calendar-list-author">{{ optional($event->user)->name }}</span>
                    @if ($event->project)<span class="calendar-list-project">{{ $event->project->title ?? $event->project->name }}</span>@endif
                </div>
            </div>
        </a>
    @empty
        <div class="calendar-empty">
            <div class="empty-title">No events yet</div>
            <p class="empty-text">Nobody has created an event for now — come back later</p>
        </div>
    @endforelse
</div>
