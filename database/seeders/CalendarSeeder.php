<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\CalendarSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        $testUser = $this->ensureUser('test@gmail.com', 'Test User', 'test');
        $test1User = $this->ensureUser('test1@gmail.com', 'Test Reviewer One', 'test1');
        $test2User = $this->ensureUser('test2@gmail.com', 'Test Reviewer Two', 'test2');

        // Two nearest reviewer slots. Both start at the same nearest time;
        // BookingService tie-breaks by username, so review #1 goes to test1
        // and review #2 goes to test2.
        $slotStart = $this->nearestHour(now()->addMinutes(30));

        $this->createReviewerSlot($test1User, $slotStart, 'Seeded first P2P review slot for test@gmail.com flow.');
        $this->createReviewerSlot($test2User, $slotStart, 'Seeded second P2P review slot for test@gmail.com flow.');

        CalendarEvent::firstOrCreate(
            [
                'user_id' => $testUser->id,
                'date' => Carbon::today()->addDay()->toDateString(),
                'start_time' => '18:00',
                'title' => 'School21 sync',
            ],
            [
                'end_time' => '19:00',
                'description' => 'General event without project binding. Use it to verify title and description only.',
                'status' => 'published',
            ]
        );
    }

    private function ensureUser(string $email, string $name, string $username): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'username' => $username,
            'email_verified_at' => now(),
            'password' => Hash::make('school21'),
        ])->save();
        $user->assignRole('user');

        return $user;
    }

    private function createReviewerSlot(User $reviewer, Carbon $start, string $notes): void
    {
        CalendarSlot::firstOrCreate(
            [
                'user_id' => $reviewer->id,
                'date' => $start->toDateString(),
                'start_time' => $start->format('H:i'),
            ],
            [
                'end_time' => $start->copy()->addHour()->format('H:i'),
                'status' => 'available',
                'project_id' => null,
                'notes' => $notes,
            ]
        );
    }

    private function nearestHour(Carbon $time): Carbon
    {
        return $time->copy()->second(0)->minute(0)->addHour();
    }
}
