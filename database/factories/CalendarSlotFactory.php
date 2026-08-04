<?php

namespace Database\Factories;

use App\Models\Admin\Project;
use App\Models\CalendarSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CalendarSlot> */
class CalendarSlotFactory extends Factory
{
    protected $model = CalendarSlot::class;

    public function definition(): array
    {
        $start = now()->addDays($this->faker->numberBetween(1, 10))->hour($this->faker->numberBetween(9, 18))->minute(0)->second(0);

        return [
            'user_id' => User::factory(),
            'project_id' => Project::query()->inRandomOrder()->value('id'),
            'date' => $start->toDateString(),
            'start_time' => $start->format('H:i'),
            'end_time' => $start->copy()->addHour()->format('H:i'),
            'status' => 'available',
            'booked_by_user_id' => null,
            'notes' => 'Factory generated calendar slot.',
        ];
    }

    public function booked(User $student): static
    {
        return $this->state(fn () => [
            'status' => 'booked',
            'booked_by_user_id' => $student->id,
        ]);
    }
}
