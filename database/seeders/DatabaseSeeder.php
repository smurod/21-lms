<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->call([
            RolesAndPermissionsSeeder::class,
            CourseSeeder::class,
            ProjectSeeder::class,
            CalendarSeeder::class,
            Test2ReviewSeeder::class,
        ]);
    }
}
