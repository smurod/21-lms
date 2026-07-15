@extends('public.layouts.app')
@section('name', 'page')
@section('nav-calendar', 'active')
@section('title', 'Event — 21-LMS')
@section('content')

    @include('public.components.user-menu-overlay')
    @include('public.components.notifications-overlay')
    @include('public.components.activities-overlay')
    @include('public.components.projects-overlay')

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="pd-toast show" id="calendarFlash" role="status">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            <span>{{ session('success') }}</span>
            <button class="pd-toast-close" type="button" aria-label="Close notification">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="pd-toast show" id="calendarFlash" role="status" style="background:#7f1d1d;color:#fecaca;">
            <span>{{ session('error') }}</span>
            <button class="pd-toast-close" type="button" aria-label="Close notification">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <main class="calendar-page">
        <div class="calendar-header">
            <a href="{{ route('public.events.index') }}" class="calendar-back" aria-label="Back to events">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            </a>
            <h1>Event details</h1>
        </div>

        <div class="calendar-body">
            <div class="event-details-card">
                <div class="event-details-head">
                    <div class="event-details-when">
                        <span class="calendar-list-date">{{ $event->date->format('j F Y') }}</span>
                        <span class="calendar-list-time">{{ substr($event->start_time, 0, 5) }} – {{ substr($event->end_time, 0, 5) }}</span>
                    </div>
                    @if ($event->project)
                        <a class="calendar-list-project" href="{{ route('public.projects.show', $event->project_id) }}">{{ $event->project->title ?? $event->project->name }}</a>
                    @endif
                </div>

                <h2 class="event-details-title">{{ $event->title }}</h2>

                @if ($event->description)
                    <p class="event-details-desc">{{ $event->description }}</p>
                @endif

                <div class="event-details-meta">
                    <span class="calendar-list-author">Organized by {{ optional($event->user)->name }}</span>
                </div>

                {{-- ------------------------------------------------------------
                     School 21 semantics: participants REGISTER to attend events
                     created by other participants or by the school.
                     ------------------------------------------------------------ --}}
                <div class="event-details-actions">
                    @if ($event->user_id === auth()->id())
                        <span class="event-details-note">You are the organizer of this event.</span>
                    @elseif ($isRegistered)
                        <span class="event-details-joined">✓ You are registered for this event</span>
                        <form method="POST" action="{{ route('public.events.unregister', $event->id) }}" class="event-unregister-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="calendar-cancel-btn">Cancel registration</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('public.events.register', $event->id) }}">
                            @csrf
                            <button type="submit" class="save-btn">Register</button>
                        </form>
                    @endif

                    @if ($event->project && ! $onProject && $event->user_id !== auth()->id())
                        <p class="event-details-note event-details-project-hint">
                            This event is about the <strong>{{ $event->project->title ?? $event->project->name }}</strong> project —
                            you can also <a href="{{ route('public.projects.show', $event->project_id) }}">open the project page</a> and subscribe to it.
                        </p>
                    @endif
                </div>

                {{-- Attendees (who registered) --}}
                <div class="event-attendees">
                    <h3>Participants <span class="event-attendees-count">{{ $event->attendees->count() }}</span></h3>
                    @if ($event->attendees->isEmpty())
                        <p class="event-details-note">Nobody has registered yet — be the first!</p>
                    @else
                        <ul class="event-attendees-list">
                            @foreach ($event->attendees as $attendee)
                                <li class="event-attendee">
                                    <span class="event-attendee-avatar">{{ mb_strtoupper(mb_substr($attendee->name, 0, 1)) }}</span>
                                    <span class="event-attendee-name">{{ $attendee->name }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </main>

@endsection
