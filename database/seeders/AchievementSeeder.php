<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            // First Steps
            [
                'name' => 'First Steps',
                'slug' => 'first_steps',
                'description' => 'Complete your first course module',
                'icon' => '🎓',
                'rarity' => 'common',
                'xp_reward' => 50,
                'condition_type' => 'first_project',
                'condition_value' => 1,
                'condition_params' => json_encode(['event' => 'module_completed']),
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'First Project',
                'slug' => 'first_project',
                'description' => 'Complete your first pet-project',
                'icon' => '🚀',
                'rarity' => 'common',
                'xp_reward' => 100,
                'condition_type' => 'first_project',
                'condition_value' => 1,
                'condition_params' => json_encode(['event' => 'first_project']),
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Five Projects',
                'slug' => 'five_projects',
                'description' => 'Complete 5 pet-projects',
                'icon' => '🏆',
                'rarity' => 'epic',
                'xp_reward' => 250,
                'condition_type' => 'projects_count',
                'condition_value' => 5,
                'condition_params' => json_encode([]),
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Ten Projects',
                'slug' => 'ten_projects',
                'description' => 'Complete 10 pet-projects',
                'icon' => '🏅',
                'rarity' => 'rare',
                'xp_reward' => 500,
                'condition_type' => 'projects_count',
                'condition_value' => 10,
                'condition_params' => json_encode([]),
                'is_hidden' => false,
                'is_active' => true,
            ],

            // XP Milestones
            [
                'name' => 'XP: 500',
                'slug' => 'xp_500',
                'description' => 'Earn 500 XP total',
                'icon' => '⭐',
                'rarity' => 'common',
                'xp_reward' => 0,
                'condition_type' => 'xp_earned',
                'condition_value' => 500,
                'condition_params' => json_encode([]),
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'XP: 1000',
                'slug' => 'xp_1000',
                'description' => 'Earn 1000 XP total',
                'icon' => '🌟',
                'rarity' => 'epic',
                'xp_reward' => 100,
                'condition_type' => 'xp_earned',
                'condition_value' => 1000,
                'condition_params' => json_encode([]),
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'XP: 5000',
                'slug' => 'xp_5000',
                'description' => 'Earn 5000 XP total',
                'icon' => '✨',
                'rarity' => 'rare',
                'xp_reward' => 500,
                'condition_type' => 'xp_earned',
                'condition_value' => 5000,
                'condition_params' => json_encode([]),
                'is_hidden' => false,
                'is_active' => true,
            ],

            // Learning Streaks
            [
                'name' => '3-Day Streak',
                'slug' => 'streak_3',
                'description' => 'Study for 3 consecutive days',
                'icon' => '🔥',
                'rarity' => 'common',
                'xp_reward' => 50,
                'condition_type' => 'streak_days',
                'condition_value' => 3,
                'condition_params' => json_encode([]),
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => '7-Day Streak',
                'slug' => 'streak_7',
                'description' => 'Study for 7 consecutive days',
                'icon' => '🔥🔥',
                'rarity' => 'epic',
                'xp_reward' => 150,
                'condition_type' => 'streak_days',
                'condition_value' => 7,
                'condition_params' => json_encode([]),
                'is_hidden' => false,
                'is_active' => true,
            ],

            // Review Milestones
            [
                'name' => 'First Review',
                'slug' => 'first_review',
                'description' => 'Complete your first peer review',
                'icon' => '📝',
                'rarity' => 'common',
                'xp_reward' => 30,
                'condition_type' => 'reviews_count',
                'condition_value' => 1,
                'condition_params' => json_encode([]),
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Review Master',
                'slug' => 'review_master',
                'description' => 'Complete 10 peer reviews',
                'icon' => '📋',
                'rarity' => 'rare',
                'xp_reward' => 200,
                'condition_type' => 'reviews_count',
                'condition_value' => 10,
                'condition_params' => json_encode([]),
                'is_hidden' => false,
                'is_active' => true,
            ],

            // Perseverance
            [
                'name' => 'Never Give Up',
                'slug' => 'never_give_up',
                'description' => 'Resubmit after failing — don\'t quit!',
                'icon' => '💪',
                'rarity' => 'epic',
                'xp_reward' => 75,
                'condition_type' => 'custom',
                'condition_value' => 3,
                'condition_params' => json_encode(['event' => 'project_failed_3_times']),
                'is_hidden' => true,
                'is_active' => true,
            ],

            // Hidden achievements
            [
                'name' => 'Git Explorer',
                'slug' => 'git_explorer',
                'description' => '???',
                'icon' => '🔒',
                'rarity' => 'rare',
                'xp_reward' => 100,
                'condition_type' => 'custom',
                'condition_value' => 1,
                'condition_params' => json_encode(['event' => 'git_commit']),
                'is_hidden' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Top of the Board',
                'slug' => 'top_of_the_board',
                'description' => '???',
                'icon' => '🔒',
                'rarity' => 'legendary',
                'xp_reward' => 300,
                'condition_type' => 'custom',
                'condition_value' => 1,
                'condition_params' => json_encode(['event' => 'leaderboard_top_1']),
                'is_hidden' => true,
                'is_active' => true,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(
                ['slug' => $achievement['slug']],
                $achievement
            );
        }
    }
}
