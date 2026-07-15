<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\CalendarSlot;
use App\Models\SubmissionReviewQueue;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Calendar seeder — covers EVERY calendar scenario:
 *
 *   User A (main test user, see $mainEmail below):
 *     1. free own slot, > 24h away          -> green "mine", CAN cancel
 *     2. free own slot, < 24h away          -> green "mine", cancel LOCKED (🔒)
 *     3. own slot BOOKED by the system      -> yellow, shows who it was booked for
 *     4. own slot booked AND < 24h away     -> yellow + locked
 *     5. own published event                -> blue cell + "My events" tab with Cancel
 *     6. cancelled slot                     -> must NOT appear anywhere
 *
 *   User B:
 *     7. own slots/events of ANOTHER user   -> must NOT be visible to user A (privacy)
 *
 *   Queue (system booking algorithm):
 *     8. user C assigned to slot #3 (status=assigned)
 *     9. users D and E waiting in queue (positions 1 and 2)
 *
 *   Events tab:
 *    10. published events from several users -> all visible in "Events"
 */
class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        /* ------------------------------------------------------------
           Users. Adjust $mainEmail to the account you log in with —
           scenarios 1-6 are seeded for that user.
           ------------------------------------------------------------ */
        $mainEmail = 'test@example.com'; // <-- ваш логин-аккаунт

        $userA = User::where('email', $mainEmail)->first()
            ?? User::factory()->create(['name' => 'test.user', 'email' => $mainEmail]);

        $userB = User::firstOrCreate(
            ['email' => 'peer.b@example.com'],
            User::factory()->raw(['name' => 'r.developer'])
        );
        $userC = User::firstOrCreate(
            ['email' => 'peer.c@example.com'],
            User::factory()->raw(['name' => 'a.coder'])
        );
        $userD = User::firstOrCreate(
            ['email' => 'peer.d@example.com'],
            User::factory()->raw(['name' => 'tangleto'])
        );
        $userE = User::firstOrCreate(
            ['email' => 'peer.e@example.com'],
            User::factory()->raw(['name' => 'whentalb'])
        );

        /* ------------------------------------------------------------
           1. Free own slot, > 24h away — cancellable.
           ------------------------------------------------------------ */
        CalendarSlot::create([
            'user_id'    => $userA->id,
            'date'       => Carbon::today()->addDays(3)->toDateString(),
            'start_time' => '10:00',
            'end_time'   => '10:15',
            'status'     => 'available',
        ]);

        /* ------------------------------------------------------------
           2. Free own slot, < 24h away — cancel locked (🔒).
              Today +1 hour (safe against midnight edge: use tomorrow
              minus 23h -> always < 24h from now).
           ------------------------------------------------------------ */
        $soon = Carbon::now()->addHours(5);
        CalendarSlot::create([
            'user_id'    => $userA->id,
            'date'       => $soon->toDateString(),
            'start_time' => $soon->format('H:00'),
            'end_time'   => $soon->copy()->addMinutes(15)->format('H:15'),
            'status'     => 'available',
        ]);

        /* ------------------------------------------------------------
           3. Own slot BOOKED by the system for user C (> 24h away).
              Owner must see "booked by a.coder"; cancel allowed,
              cancellation must push C back to the FRONT of the queue.
           ------------------------------------------------------------ */
        $bookedSlot = CalendarSlot::create([
            'user_id'           => $userA->id,
            'date'              => Carbon::today()->addDays(4)->toDateString(),
            'start_time'        => '15:00',
            'end_time'          => '15:15',
            'status'            => 'booked',
            'booked_by_user_id' => $userC->id,
        ]);

        SubmissionReviewQueue::create([
            'submission_id' => null,
            'user_id'       => $userC->id,
            'slot_id'       => $bookedSlot->id,
            'status'        => 'assigned',
            'position'      => 0,
        ]);

        /* ------------------------------------------------------------
           4. Own slot booked AND < 24h away — locked + booked info.
           ------------------------------------------------------------ */
        $soonBooked = Carbon::now()->addHours(8);
        CalendarSlot::create([
            'user_id'           => $userA->id,
            'date'              => $soonBooked->toDateString(),
            'start_time'        => $soonBooked->format('H:30'),
            'end_time'          => $soonBooked->copy()->addMinutes(15)->format('H:45'),
            'status'            => 'booked',
            'booked_by_user_id' => $userB->id,
        ]);

        /* ------------------------------------------------------------
           5. Own published event — visible in grid + "My events" tab.
           ------------------------------------------------------------ */
        CalendarEvent::create([
            'user_id'     => $userA->id,
            'date'        => Carbon::today()->addDays(2)->toDateString(),
            'start_time'  => '18:00',
            'end_time'    => '19:00',
            'title'       => 'Tribe meetup',
            'description' => 'Weekly sync of the Computers tribe.',
            'status'      => 'published',
        ]);

        /* ------------------------------------------------------------
           6. Cancelled slot — must NOT be rendered anywhere.
           ------------------------------------------------------------ */
        CalendarSlot::create([
            'user_id'    => $userA->id,
            'date'       => Carbon::today()->addDays(5)->toDateString(),
            'start_time' => '11:00',
            'end_time'   => '11:15',
            'status'     => 'cancelled',
        ]);

        /* ------------------------------------------------------------
           7. PRIVACY: slots of user B — user A must NOT see them.
           ------------------------------------------------------------ */
        CalendarSlot::create([
            'user_id'    => $userB->id,
            'date'       => Carbon::today()->addDays(3)->toDateString(),
            'start_time' => '12:00',
            'end_time'   => '12:15',
            'status'     => 'available',
        ]);
        CalendarSlot::create([
            'user_id'    => $userB->id,
            'date'       => Carbon::today()->addDays(6)->toDateString(),
            'start_time' => '09:00',
            'end_time'   => '09:15',
            'status'     => 'available',
        ]);

        /* ------------------------------------------------------------
           8-9. QUEUE: D and E are waiting (positions 1 and 2).
              Next created slot must go to D (FIFO), then E.
           ------------------------------------------------------------ */
        SubmissionReviewQueue::create([
            'submission_id' => null,
            'user_id'       => $userD->id,
            'slot_id'       => null,
            'status'        => 'waiting',
            'position'      => 1,
        ]);
        SubmissionReviewQueue::create([
            'submission_id' => null,
            'user_id'       => $userE->id,
            'slot_id'       => null,
            'status'        => 'waiting',
            'position'      => 2,
        ]);

        /* ------------------------------------------------------------
           10. Events of other users — visible to everyone in "Events".
           ------------------------------------------------------------ */
        CalendarEvent::create([
            'user_id'     => $userB->id,
            'date'        => Carbon::today()->addDays(1)->toDateString(),
            'start_time'  => '14:00',
            'end_time'    => '15:30',
            'title'       => 'C workshop',
            'description' => 'Pointers and memory management deep dive.',
            'status'      => 'published',
        ]);
        CalendarEvent::create([
            'user_id'     => $userC->id,
            'date'        => Carbon::today()->addDays(4)->toDateString(),
            'start_time'  => '17:00',
            'end_time'    => '18:00',
            'title'       => 'Peer Review basics',
            'description' => 'How to run a good review session.',
            'status'      => 'published',
        ]);
        // Cancelled event — must NOT appear in "Events"
        CalendarEvent::create([
            'user_id'     => $userB->id,
            'date'        => Carbon::today()->addDays(2)->toDateString(),
            'start_time'  => '20:00',
            'end_time'    => '21:00',
            'title'       => 'Cancelled meetup',
            'status'      => 'cancelled',
        ]);
    }
}
