<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $username = Str::of($this->faker->unique()->userName())
            ->replaceMatches('/[^A-Za-z0-9_.-]/', '-')
            ->replaceMatches('/^[^A-Za-z0-9_]+/', '')
            ->limit(50, '')
            ->toString();

        return [
            'name' => $this->faker->name(),
            'username' => $username ?: 'cadet' . $this->faker->unique()->numberBetween(1000, 9999),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('school21'),
            'level' => $this->faker->numberBetween(1, 7),
            'total_xp' => 0,
            'remember_token' => Str::random(10),
        ];
    }
}
