@extends('public.layouts.app')
@section('name', 'page')
@section('nav-calendar', 'active')
@section('content')

    <!-- Notifications overlay -->
    @include('public.components.notifications-overlay')

    @include('public.components.user-menu-overlay')

    <!-- Activities overlay -->
    @include('public.components.activities-overlay')

    <!-- Projects overlay -->
    @include('public.components.projects-overlay')

    @php
        /* Everything below is SERVER-rendered. All data comes from CalendarController.
           Safe fallbacks keep the page alive if a variable is missing. */
        $activeTab  = $activeTab  ?? 'schedule';
        $weekOffset = $weekOffset ?? max(0, (int) request('week', 0));
        $weekStart  = $weekStart  ?? \Carbon\Carbon::today()->addWeeks($weekOffset);
        $weekEnd    = $weekEnd    ?? $weekStart->copy()->addDays(6);
        $slotStep   = $slotStep   ?? 15;

        $slots     = collect($slots ?? []);
        $events    = collect($events ?? []);
        $allEvents = collect($allEvents ?? []);
        $myEvents  = collect($myEvents ?? []);
        $mySlots   = collect($mySlots ?? []);
        $projects  = collect($projects ?? []);
        $authId    = $authId ?? auth()->id();

        /* Fast lookup maps: "Y-m-d|H:i" => ['m' => model, 'start' => bool].
           A slot/event covers EVERY 15-min cell from start_time (inclusive)
           to end_time (exclusive) — so a 01:00-02:00 slot paints 4 cells.
           The badge is rendered only on the first (start) cell. */
        $toMin = fn ($t) => ((int) substr($t, 0, 2)) * 60 + ((int) substr($t, 3, 2));

        $slotMap = [];
        foreach ($slots as $slot) {
          $day  = $slot->date->toDateString();
          $from = $toMin($slot->start_time);
          $till = max($toMin($slot->end_time), $from + $slotStep);
          for ($t = $from; $t < $till; $t += $slotStep) {
            $slotMap[$day . '|' . sprintf('%02d:%02d', intdiv($t, 60), $t % 60)] =
              ['m' => $slot, 'start' => $t === $from];
          }
        }

        $eventMap = [];
        foreach ($events as $event) {
          $day  = $event->date->toDateString();
          $from = $toMin($event->start_time);
          $till = max($toMin($event->end_time), $from + $slotStep);
          for ($t = $from; $t < $till; $t += $slotStep) {
            $eventMap[$day . '|' . sprintf('%02d:%02d', intdiv($t, 60), $t % 60)] =
              ['m' => $event, 'start' => $t === $from];
          }
        }

        /* Validation errors: which form failed (used to reopen the modal server-side) */
        $failedForm = old('form_type'); // 'slot' | 'event' | null
    @endphp

    {{-- Flash messages (server-side, reuses the toast styles) --}}
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
            <a href="{{ route('public.home') }}" class="calendar-back" aria-label="Back">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            </a>
            <h1>Calendar</h1>
        </div>

        {{-- Tabs are REAL LINKS to server routes — no JS panel switching --}}
        <div class="calendar-tabs">
            <a class="calendar-tab{{ $activeTab === 'schedule' ? ' active' : '' }}" href="{{ route('public.calendar') }}">Schedule</a>
            <a class="calendar-tab{{ $activeTab === 'events' ? ' active' : '' }}" href="{{ route('public.events.index') }}">Events</a>
            <a class="calendar-tab{{ $activeTab === 'my-events' ? ' active' : '' }}" href="{{ route('public.calendar.my-events') }}">My events</a>
        </div>

        @if ($activeTab === 'schedule')
            <!-- ===== Schedule: the interactive week grid ===== -->
            <div class="calendar-body">
                <div class="calendar-toolbar">
                    @if ($weekOffset === 0)
                        <span class="week-nav prev is-disabled" aria-disabled="true" aria-label="Previous week">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        </span>
                    @else
                        <a class="week-nav prev" href="{{ route('public.calendar', ['week' => $weekOffset - 1]) }}" aria-label="Previous week">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                        </a>
                    @endif

                    <div class="calendar-range" id="calendarRange">
                        {{ $weekStart->format('j F') }} &ndash; {{ $weekEnd->format('j F Y') }}
                    </div>

                    <a class="week-nav next" href="{{ route('public.calendar', ['week' => $weekOffset + 1]) }}" aria-label="Next week">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                </div>

                <div class="calendar-grid-wrapper">
                    {{-- Dates and cells are ONE grid: the date headers are the first
                         (sticky) row, so each column of cells sits strictly under its date. --}}
                    <div class="calendar-grid" id="calendarGrid">
                        <div class="grid-corner"></div>

                        @for ($d = 0; $d < 7; $d++)
                            @php $date = $weekStart->copy()->addDays($d); @endphp
                            <div class="grid-day-header{{ $date->isToday() ? ' today' : '' }}">
                                <span class="grid-day-label">{{ $date->format('j M, D') }}</span>
                            </div>
                        @endfor

                        @for ($m = 0; $m < 1440; $m += $slotStep)
                            <div class="grid-time-label">{{ $m % 60 === 0 ? sprintf('%02d:00', intdiv($m, 60)) : '' }}</div>
                            @for ($d = 0; $d < 7; $d++)
                                @php
                                    $cellDate = $weekStart->copy()->addDays($d)->toDateString();
                                    $cellTime = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
                                    $key  = $cellDate . '|' . $cellTime;
                                    $slotHit = $slotMap[$key] ?? null;
                                    $evtHit  = $eventMap[$key] ?? null;
                                    $slot = $slotHit['m'] ?? null;
                                    $evt  = $evtHit['m'] ?? null;
                                    $isStart = ($evtHit['start'] ?? false) || ($slotHit['start'] ?? false);

                                    $cls = '';
                                    $badge = '';
                                    $extra = '';
                                    if ($evt) {
                                      $cls = ' cell-event' . ($isStart ? '' : ' cell-cont');
                                      $badge = $isStart ? ($evt->title ?? optional($evt->user)->name) : '';
                                      $extra = ' data-event-id="' . $evt->id . '"'
                                             . ' data-event-title="' . e($evt->title) . '"'
                                             . ' data-event-author="' . e(optional($evt->user)->name) . '"'
                                             . ' data-event-desc="' . e($evt->description ?? '') . '"'
                                             . ' data-event-start="' . e(substr($evt->start_time, 0, 5)) . '"'
                                             . ' data-event-end="' . e(substr($evt->end_time, 0, 5)) . '"'
                                             . ' data-event-url="' . route('public.events.show', $evt->id) . '"';
                                    } elseif ($slot) {
                                      /* Grid contains ONLY the current user's slots (controller filters).
                                         Booking is performed by the system algorithm — the owner just
                                         sees WHO was assigned when the slot is booked. */
                                      $cls = ($slot->isBooked() ? ' cell-booked mine' : ' cell-slot mine')
                                           . ($isStart ? '' : ' cell-cont');
                                      $badge = ! $isStart ? '' : ($slot->isBooked()
                                        ? (optional($slot->bookedBy)->name ?? 'Booked')
                                        : 'My slot');

                                      /* 24h rule (server-enforced too): cancel action only when allowed */
                                      $slotStartsAt = \Carbon\Carbon::parse($slot->date->toDateString() . ' ' . $slot->start_time);
                                      $canCancel = now()->diffInHours($slotStartsAt, false) >= 24;
                                      $extra = $canCancel
                                        ? ' data-cancel-action="' . route('calendar.slots.destroy', $slot->id) . '"'
                                        : ' data-cancel-locked="1"';
                                      if ($slot->isBooked()) {
                                        $extra .= ' data-booked-by="' . e(optional($slot->bookedBy)->name ?? '') . '"';
                                      }
                                    }
                                @endphp
                                <button class="grid-cell{{ $cls }}" type="button"
                                        data-date="{{ $cellDate }}"
                                        data-time="{{ $cellTime }}"{!! $extra !!}>
                                    @if ($badge)<span class="cell-badge">{{ $badge }}</span>@endif
                                </button>
                            @endfor
                        @endfor
                    </div>
                </div>
            </div>
        @endif

        @if ($activeTab === 'my-events')
            <!-- ===== My events: events and slots created by the current user ===== -->
            <div class="calendar-body">
                <div class="calendar-list">
                    @forelse ($myEvents as $event)
                        <article class="calendar-list-item">
                            <div class="calendar-list-when">
                                <span class="calendar-list-date">{{ $event->date->format('j M') }}</span>
                                <span class="calendar-list-time">{{ substr($event->start_time, 0, 5) }} – {{ substr($event->end_time, 0, 5) }}</span>
                            </div>
                            <div class="calendar-list-bar event"></div>
                            <div class="calendar-list-info">
                                <h3>{{ $event->title }}</h3>
                                @if ($event->description)<p>{{ $event->description }}</p>@endif
                                <div class="calendar-list-meta">
                                    @if ($event->project)<span class="calendar-list-project">{{ $event->project->title ?? $event->project->name }}</span>@endif
                                </div>
                            </div>
                            <form method="POST" action="{{ route('calendar.events.destroy', $event->id) }}" class="calendar-list-actions">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="calendar-cancel-btn">Cancel</button>
                            </form>
                        </article>
                    @empty
                        <div class="calendar-empty">
                            <div class="empty-title">No events of yours</div>
                            <p class="empty-text">Create an event from the Schedule tab — click any free cell</p>
                        </div>
                    @endforelse

                    @foreach ($mySlots as $slot)
                        <article class="calendar-list-item">
                            <div class="calendar-list-when">
                                <span class="calendar-list-date">{{ $slot->date->format('j M') }}</span>
                                <span class="calendar-list-time">{{ substr($slot->start_time, 0, 5) }} – {{ substr($slot->end_time, 0, 5) }}</span>
                            </div>
                            <div class="calendar-list-bar {{ $slot->isBooked() ? 'booked' : 'slot' }}"></div>
                            <div class="calendar-list-info">
                                <h3>Peer Review slot</h3>
                                <div class="calendar-list-meta">
                                    @if ($slot->isBooked())
                                        <span class="calendar-list-author">Booked by {{ optional($slot->bookedBy)->name }}</span>
                                    @else
                                        <span class="calendar-list-author">Available</span>
                                    @endif
                                </div>
                            </div>
                            @php
                                $slotStartsAt = \Carbon\Carbon::parse($slot->date->toDateString() . ' ' . $slot->start_time);
                                $canCancel = now()->diffInHours($slotStartsAt, false) >= 24;
                            @endphp
                            @if ($canCancel)
                                <form method="POST" action="{{ route('calendar.slots.destroy', $slot->id) }}" class="calendar-list-actions">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="calendar-cancel-btn">Cancel</button>
                                </form>
                            @else
                                <div class="calendar-list-actions">
                                    <span class="calendar-cancel-locked" title="Cannot cancel less than 24 hours before the slot starts">🔒 Locked</span>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </main>

    @if ($activeTab === 'schedule')
        {{-- ==================== Create slot / event modal ====================
             Two NATIVE forms posting to the server. If validation fails, Laravel
             redirects back and the modal reopens server-side via $errors. --}}
        <div class="calendar-modal-backdrop" id="calendarModalBackdrop" @if(!$errors->any()) hidden @endif>
            <div class="calendar-modal" role="dialog" aria-modal="true" aria-labelledby="calendarModalTitle">
                <div class="calendar-modal-head">
                    <div class="calendar-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <button class="calendar-modal-close" id="calendarModalClose" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="calendar-modal-body">
                    <h2 id="calendarModalTitle">{{ $failedForm === 'event' ? 'New event' : 'Peer Review slot' }}</h2>
                    <p class="calendar-modal-time" id="calendarModalTime">
                        {{ old('date') ? \Carbon\Carbon::parse(old('date'))->format('j F') . ', ' . old('start_time') : '' }}
                    </p>

                    @if ($errors->any())
                        <p class="calendar-modal-error">{{ $errors->first() }}</p>
                    @endif

                    <div class="calendar-modal-toggle">
                        <button class="toggle-btn{{ $failedForm !== 'event' ? ' active' : '' }}" type="button" data-type="slot">Create slot</button>
                        <button class="toggle-btn{{ $failedForm === 'event' ? ' active' : '' }}" type="button" data-type="event">Create event</button>
                    </div>

                    {{-- ---------- SLOT form: only the time range ---------- --}}
                    <form method="POST" action="{{ route('calendar.slots.create') }}" id="slotForm" @if($failedForm === 'event') hidden @endif>
                        @csrf
                        <input type="hidden" name="form_type" value="slot">
                        <input type="hidden" name="date" id="slotDate" value="{{ old('date') }}">

                        <div class="calendar-modal-fields" id="timeFields">
                            <div class="time-field">
                                <label for="slotFrom">From</label>
                                <select id="slotFrom" name="start_time">
                                    @for ($m = 0; $m < 1440; $m += $slotStep)
                                        @php $t = sprintf('%02d:%02d', intdiv($m, 60), $m % 60); @endphp
                                        <option value="{{ $t }}" @selected(old('start_time') === $t)>{{ $t }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="time-field">
                                <label for="slotTo">to</label>
                                <select id="slotTo" name="end_time">
                                    @for ($m = $slotStep; $m <= 1440; $m += $slotStep)
                                        @php $t = sprintf('%02d:%02d', intdiv($m, 60) % 24, $m % 60); @endphp
                                        <option value="{{ $t }}" @selected(old('end_time') === $t)>{{ $t }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="calendar-modal-foot">
                            <button class="save-btn" type="submit">Save</button>
                        </div>
                    </form>

                    {{-- ---------- EVENT form: title / description / project ---------- --}}
                    <form method="POST" action="{{ route('calendar.events.create') }}" id="eventForm" @if($failedForm !== 'event') hidden @endif>
                        @csrf
                        <input type="hidden" name="form_type" value="event">
                        <input type="hidden" name="date" id="eventDate" value="{{ old('date') }}">
                        <input type="hidden" name="start_time" id="eventStart" value="{{ old('start_time') }}">
                        <input type="hidden" name="end_time" id="eventEnd" value="{{ old('end_time') }}">

                        <div class="calendar-modal-extra" id="extraFields">
                            <div class="event-field">
                                <label for="eventTitle">Title</label>
                                <input type="text" id="eventTitle" name="title" maxlength="255" placeholder="Event title" value="{{ old('title') }}">
                            </div>
                            <div class="event-field">
                                <label for="eventDesc">Description</label>
                                <textarea id="eventDesc" name="description" rows="3" placeholder="What is this event about? (optional)">{{ old('description') }}</textarea>
                            </div>
                            <div class="event-field">
                                <label for="eventProject">Project</label>
                                <select id="eventProject" name="project_id">
                                    <option value="">— No project —</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" @selected((string) old('project_id') === (string) $project->id)>{{ $project->title ?? $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="calendar-modal-foot">
                            <button class="save-btn" type="submit">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ==================== Own slot info (booked / cancel-locked) ==================== --}}
        <div class="calendar-modal-backdrop" id="calendarSlotInfoBackdrop" hidden>
            <div class="calendar-modal" role="dialog" aria-modal="true" aria-labelledby="calendarSlotInfoTitle">
                <div class="calendar-modal-head">
                    <div class="calendar-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <button class="calendar-modal-close" id="calendarSlotInfoClose" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="calendar-modal-body">
                    <h2 id="calendarSlotInfoTitle">Peer Review slot</h2>
                    <p class="calendar-modal-time" id="calendarSlotInfoTime"></p>
                    <p class="calendar-modal-note" id="calendarSlotInfoNote"></p>
                </div>
            </div>
        </div>

        {{-- ==================== Cancel own slot confirmation ==================== --}}
        <div class="calendar-modal-backdrop" id="calendarCancelBackdrop" hidden>
            <div class="calendar-modal" role="dialog" aria-modal="true" aria-labelledby="calendarCancelTitle">
                <div class="calendar-modal-head">
                    <div class="calendar-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <button class="calendar-modal-close" id="calendarCancelClose" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="calendar-modal-body">
                    <h2 id="calendarCancelTitle">Cancel Peer Review slot?</h2>
                    <p class="calendar-modal-time" id="calendarCancelTime"></p>
                    <p class="calendar-modal-note">The slot will be removed from the schedule. If someone has already booked it, the booking will be cancelled too.</p>
                </div>

                <div class="calendar-modal-foot">
                    <form method="POST" action="" id="calendarCancelForm">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="keep-btn" id="calendarCancelKeep">Keep slot</button>
                        <button type="submit" class="cancel-confirm-btn">Cancel slot</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ==================== Event details (read-only) ==================== --}}
        <div class="calendar-modal-backdrop" id="calendarEventBackdrop" hidden>
            <div class="calendar-modal" role="dialog" aria-modal="true" aria-labelledby="calendarEventTitle">
                <div class="calendar-modal-head">
                    <div class="calendar-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <button class="calendar-modal-close" id="calendarEventClose" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="calendar-modal-body">
                    <h2 id="calendarEventTitle">Event</h2>
                    <p class="calendar-modal-time" id="calendarEventTime"></p>
                    <p class="calendar-modal-note" id="calendarEventDesc"></p>
                </div>
            </div>
        </div>
    @endif

@endsection
