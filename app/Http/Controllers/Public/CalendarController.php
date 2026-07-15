<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\CalendarSlot;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  Pages (server-rendered tabs)                                       */
    /* ------------------------------------------------------------------ */

    public function __invoke(): View
    {
        return $this->schedulePage();
    }

    public function schedulePage(): View
    {
        $weekOffset = max(0, (int) request('week', 0));
        $weekStart  = Carbon::today()->addWeeks($weekOffset);
        $weekEnd    = $weekStart->copy()->addDays(6);

        /* PRIVACY: users see ONLY their own slots. Booking is done by the
           system algorithm, so foreign slots are never shown or bookable. */
        $slots = CalendarSlot::where('user_id', auth()->id())
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->whereIn('status', ['available', 'booked'])
            ->with(['user', 'bookedBy'])
            ->get();

        $events = CalendarEvent::whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->where('status', 'published')
            ->with('user')
            ->get();

        /* School 21: events are team-up announcements. A user may create an
           event ONLY for a project he is registered on. Registration lives in
           `submissions` (User->hasMany(Admin\Submission)) — any status counts:
           registered / in progress / passed. */
        $registeredProjectIds = auth()->user()
            ->submissions()
            ->pluck('project_id')
            ->unique();

        $projects = \App\Models\Admin\Project::whereIn('id', $registeredProjectIds)
            ->orderBy('title')
            ->get();

        return view('public.calendar', [
            'activeTab'     => 'schedule',
            'weekOffset'    => $weekOffset,
            'weekStart'     => $weekStart,
            'weekEnd'       => $weekEnd,
            'slotStep'      => 15,
            'slots'         => $slots,
            'events'        => $events,
            'allEvents'     => collect(),
            'myEvents'      => collect(),
            'mySlots'       => collect(),
            'projects'      => $projects,
            'authId'        => auth()->id(),
            'queuePosition' => app(BookingService::class)->getQueuePosition(auth()->user()),
        ]);
    }

    public function eventsPage(): View
    {
        $events = CalendarEvent::where('status', 'published')
            ->with(['user', 'project'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        return view('public.events.index', [
            'events' => $events,
        ]);
    }

    public function showEvent(int $eventId): View
    {
        $event = CalendarEvent::where('status', 'published')
            ->with(['user', 'project', 'attendees'])
            ->findOrFail($eventId);

        /* School 21 semantics: users REGISTER to attend the event itself. */
        $isRegistered = $event->isRegistered(auth()->user());

        /* Extra: if the event is linked to a project, also tell whether the
           viewer is already subscribed to that project (shows a hint). */
        $onProject = false;
        if ($event->project_id && auth()->check()) {
            $onProject = auth()->user()
                ->submissions()
                ->where('project_id', $event->project_id)
                ->exists();
        }

        return view('public.events.show', [
            'event'        => $event,
            'isRegistered' => $isRegistered,
            'onProject'    => $onProject,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  POST — register / unregister for an event (School 21 style)        */
    /* ------------------------------------------------------------------ */

    public function registerForEvent(int $eventId): RedirectResponse
    {
        $event = CalendarEvent::where('status', 'published')->findOrFail($eventId);

        if ($event->user_id === auth()->id()) {
            return redirect()->back()->with('error', 'You are the organizer of this event.');
        }

        $event->attendees()->syncWithoutDetaching([auth()->id()]);

        return redirect()->route('public.events.show', $event->id)
            ->with('success', 'You are registered for "' . $event->title . '".');
    }

    public function unregisterFromEvent(int $eventId): RedirectResponse
    {
        $event = CalendarEvent::findOrFail($eventId);
        $event->attendees()->detach(auth()->id());

        return redirect()->route('public.events.show', $event->id)
            ->with('success', 'Registration cancelled.');
    }

    public function myEventsPage(): View
    {
        $authId = auth()->id();

        $myEvents = CalendarEvent::where('user_id', $authId)
            ->where('status', 'published')
            ->with('project')
            ->orderBy('date', 'desc')
            ->get();

        $mySlots = CalendarSlot::where('user_id', $authId)
            ->whereIn('status', ['available', 'booked'])
            ->with('bookedBy')
            ->orderBy('date', 'desc')
            ->get();

        return view('public.calendar', [
            'activeTab'     => 'my-events',
            'weekOffset'    => 0,
            'weekStart'     => Carbon::today(),
            'weekEnd'       => Carbon::today()->addDays(6),
            'slotStep'      => 15,
            'slots'         => collect(),
            'events'        => collect(),
            'allEvents'     => collect(),
            'myEvents'      => $myEvents,
            'mySlots'       => $mySlots,
            'projects'      => collect(),
            'authId'        => $authId,
            'queuePosition' => app(BookingService::class)->getQueuePosition(auth()->user()),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  POST — create slot (native form)                                   */
    /* ------------------------------------------------------------------ */

    public function createSlot(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date'       => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
            'project_id' => 'nullable|exists:projects,id',
            'notes'      => 'nullable|string|max:1000', // FIX: was max=1000
        ]);

        /* Friendly guard for the DB unique index
           (user_id, project_id, date, start_time): without it a duplicate
           submit would crash with a QueryException instead of a message. */
        $duplicate = CalendarSlot::where('user_id', auth()->id())
            ->where('project_id', $validated['project_id'] ?? null)
            ->whereDate('date', $validated['date'])
            ->whereTime('start_time', $validated['start_time'])
            ->exists();

        if ($duplicate) {
            return redirect()->back()
                ->withErrors(['start_time' => 'You already have a slot at this date and time.'])
                ->withInput();
        }

        $slot = CalendarSlot::create([
            'user_id'    => auth()->id(),
            'project_id' => $validated['project_id'] ?? null,
            'date'       => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time'   => $validated['end_time'],
            'notes'      => $validated['notes'] ?? null,
            'status'     => 'available',
        ]);

        app(BookingService::class)->processQueue($slot->id);

        return redirect()->route('public.calendar')->with('success', 'Slot created successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  POST — create event (native form)                                  */
    /* ------------------------------------------------------------------ */

    public function createEvent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date'        => 'required|date|after_or_equal:today',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'project_id'  => 'required|exists:projects,id', // event = team-up for a project
        ]);

        /* Server-side guard: the user must be registered on that project
           (has a submission for it — created by SubscriptionController). */
        $registered = auth()->user()
            ->submissions()
            ->where('project_id', $validated['project_id'])
            ->exists();

        if (! $registered) {
            return redirect()->back()
                ->withErrors(['project_id' => 'You can create events only for projects you are registered on.'])
                ->withInput();
        }

        CalendarEvent::create([
            'user_id'     => auth()->id(),
            'project_id'  => $validated['project_id'] ?? null,
            'date'        => $validated['date'],
            'start_time'  => $validated['start_time'],
            'end_time'    => $validated['end_time'],
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status'      => 'published',
        ]);

        return redirect()->route('public.calendar')->with('success', 'Event created successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  DELETE — cancel own slot (native form)                             */
    /* ------------------------------------------------------------------ */

    public function destroySlot(int $slotId): RedirectResponse
    {
        $slot = CalendarSlot::findOrFail($slotId);

        if ($slot->user_id !== auth()->id()) {
            return redirect()->back()->with('error', "You cannot cancel another user's slot.");
        }

        $result = app(BookingService::class)->cancelSlot($slot, auth()->user());

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', 'Slot cancelled.');
    }

    /* ------------------------------------------------------------------ */
    /*  DELETE — cancel own event (native form)                            */
    /* ------------------------------------------------------------------ */

    public function destroyEvent(int $eventId): RedirectResponse
    {
        $event = CalendarEvent::findOrFail($eventId);

        if ($event->user_id !== auth()->id()) {
            return redirect()->back()->with('error', "You cannot cancel another user's event.");
        }

        $event->cancel();

        return redirect()->back()->with('success', 'Event cancelled.');
    }

    /* ------------------------------------------------------------------ */
    /*  POST — submit project for review -> queue (kept, now redirect)     */
    /* ------------------------------------------------------------------ */

    public function submitProjectForReview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'submission_id' => 'nullable|integer',
            'project_id'    => 'nullable|integer',
        ]);

        $user = auth()->user();

        // Find active submission
        $submission = null;
        if (!empty($validated['submission_id'])) {
            $submission = \App\Models\Admin\Submission::where('id', $validated['submission_id'])
                ->where('user_id', $user->id)
                ->first();
        }
        if (! $submission && !empty($validated['project_id'])) {
            $submission = \App\Models\Admin\Submission::where('project_id', $validated['project_id'])
                ->where('user_id', $user->id)
                ->whereIn('status', ['in_progress', 'pending', 'failed', 'resubmitted'])
                ->latest()
                ->first();
        }

        if (! $submission) {
            return redirect()->back()->with('error', 'Active submission not found. Subscribe to the project first.');
        }

        // 1) Run automated tests
        $testRunner = app(\App\Services\TestRunnerService::class);
        $submission = $testRunner->run($submission);

        if (! $testRunner->passes($submission)) {
            return redirect()->back()->with('error',
                "Autotests failed: {$submission->tests_passed}/{$submission->tests_total} ({$submission->test_score}%). ".
                "Passing score: ".($submission->project->passing_score ?? 70)."%. Fix and resubmit."
            );
        }

        // Mark as tested → in_review queue will pick it up
        $submission->update(['status' => 'tested']);

        // 2) Assign peer-review slot via BookingService
        $result = app(BookingService::class)->assignSlot(
            $user,
            $submission->project_id,
            $submission->id
        );

        // Update submission to in_review
        $submission->update(['status' => 'in_review', 'submitted_at' => now()]);

        if ($result->isAssigned()) {
            return redirect()->back()->with(
                'success',
                "Tests passed {$submission->tests_passed}/{$submission->tests_total} ({$submission->test_score}%). ".
                'You have been assigned a review slot: '
                . $result->slot->date->toDateString() . ', '
                . substr($result->slot->start_time, 0, 5) . '.'
            );
        }

        return redirect()->back()->with(
            'success',
            "Tests passed {$submission->tests_passed}/{$submission->tests_total} ({$submission->test_score}%). ".
            'You are in the queue (position ' . $result->position . '). A slot will be assigned when available.'
        );
    }
}
