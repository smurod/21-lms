@extends('public.layouts.app')
@section('name', 'page')
@section('nav-calendar', 'active')
@section('title', 'Events — 21-LMS')
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
            <a href="{{ route('public.calendar') }}" class="calendar-back" aria-label="Back to calendar">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            </a>
            <h1>Events</h1>
        </div>

        <div class="calendar-body">
            @include('public.events._list', ['events' => $events])
        </div>
    </main>

@endsection
