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

        /* Calendar visibility:
           - available slots are visible to everyone, but duplicated slots with
             the same interval are collapsed to one random Free slot;
           - booked slots are visible only to the slot owner and the assigned
             student; booked-by names are not exposed in the grid. */
        $availableSlots = CalendarSlot::where('status', 'available')
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->with(['user', 'bookedBy'])
            ->get()
            ->groupBy(fn (CalendarSlot $slot) => implode('|', [
                $slot->date->toDateString(),
                substr($slot->start_time, 0, 5),
                substr($slot->end_time, 0, 5),
                $slot->project_id ?? 'any',
            ]))
            ->map(fn ($group) => $group->random())
            ->values();

        $bookedSlots = CalendarSlot::where('status', 'booked')
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhere('booked_by_user_id', auth()->id());
            })
            ->with(['user', 'bookedBy'])
            ->get();

        $slots = $availableSlots->merge($bookedSlots)->values();

        $events = CalendarEvent::whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->where('status', 'published')
            ->with('user')
            ->get();

        return view('public.calendar', [
            'activeTab'     => 'schedule',
            'weekOffset'    => $weekOffset,
            'weekStart'     => $weekStart,
            'weekEnd'       => $weekEnd,
            'slotStep'      => 60,
            'formStep'      => 15,
            'slots'         => $slots,
            'events'        => $events,
            'allEvents'     => collect(),
            'myEvents'      => collect(),
            'mySlots'       => collect(),
            'projects'      => collect(),
            'authId'        => auth()->id(),
            'queuePosition' => 0,
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
            'slotStep'      => 60,
            'formStep'      => 15,
            'slots'         => collect(),
            'events'        => collect(),
            'allEvents'     => collect(),
            'myEvents'      => $myEvents,
            'mySlots'       => $mySlots,
            'projects'      => collect(),
            'authId'        => $authId,
            'queuePosition' => 0,
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

        $startsAt = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);

        if ($startsAt->lessThanOrEqualTo(now())) {
            return redirect()->back()
                ->withErrors(['start_time' => 'Нельзя создать слот в прошлом.'])
                ->withInput();
        }

        $endsAt = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        if ($endsAt->lessThanOrEqualTo($startsAt) || $startsAt->diffInMinutes($endsAt) < 30) {
            return redirect()->back()
                ->withErrors(['end_time' => 'Минимальная длительность слота — 30 минут.'])
                ->withInput();
        }

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
        ]);

        CalendarEvent::create([
            'user_id'     => auth()->id(),
            'project_id'  => null,
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

    public function bookSlot(int $slotId): RedirectResponse
    {
        $slot = CalendarSlot::where('status', 'available')->findOrFail($slotId);
        $user = auth()->user();

        if ($slot->user_id === $user->id) {
            return redirect()->back()->with('error', 'You cannot book your own review slot.');
        }

        $startsAt = Carbon::parse($slot->date->toDateString() . ' ' . $slot->start_time);
        if ($startsAt->lessThanOrEqualTo(now())) {
            return redirect()->back()->with('error', 'Cannot book a slot in the past.');
        }

        $submission = \App\Models\Admin\Submission::where('user_id', $user->id)
            ->whereIn('status', ['tested', 'in_review', 'reviewed'])
            ->latest('id')
            ->first();

        if (! $submission) {
            return redirect()->back()->with('error', 'No project is waiting for review. Submit a project first.');
        }

        $project = $submission->project;
        $completedReviews = $submission->reviews()->where('status', 'completed')->count();
        $activeReviews = $submission->reviews()->whereIn('status', ['pending', 'in_progress'])->count();
        $requiredReviews = $project->required_reviews_count ?? 2;

        if (($completedReviews + $activeReviews) >= $requiredReviews) {
            return redirect()->back()->with('error', 'This project already has enough assigned reviews.');
        }

        $alreadyReviewedBySlotOwner = $submission->reviews()
            ->where('reviewer_id', $slot->user_id)
            ->exists();

        if ($alreadyReviewedBySlotOwner) {
            return redirect()->back()->with('error', 'This reviewer has already been assigned to this project. Choose another slot.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($slot, $user, $submission) {
            $lockedSlot = CalendarSlot::where('id', $slot->id)
                ->where('status', 'available')
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSlot->book($user);

            \App\Models\Admin\Review::firstOrCreate(
                [
                    'submission_id' => $submission->id,
                    'reviewer_id' => $lockedSlot->user_id,
                ],
                [
                    'status' => 'pending',
                    'is_auto_assigned' => true,
                    // scheduled review start; reviewer cannot open the review before this time
                    'started_at' => Carbon::parse($lockedSlot->date->toDateString() . ' ' . $lockedSlot->start_time),
                    // review deadline: 24 hours after scheduled start
                    'completed_at' => Carbon::parse($lockedSlot->date->toDateString() . ' ' . $lockedSlot->start_time)->addHours(24),
                ]
            );
        });

        return redirect()->back()->with('success', 'Review slot booked. The reviewer can now open it in Peer reviews.');
    }

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
                ->latest('id')
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

        // Tests passed. The project now waits for the student to book suitable
        // peer-review slots manually from the public calendar.
        $submission->update([
            'status' => 'in_review',
            'submitted_at' => now(),
        ]);

        return redirect()->back()->with(
            'success',
            "Tests passed {$submission->tests_passed}/{$submission->tests_total} ({$submission->test_score}%). ".
            'Choose a free peer-review slot in the calendar.'
        );
    }
}
