<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStat extends Model
{
    protected $fillable = [
        'user_id', 'projects_started', 'projects_completed',
        'projects_failed', 'completion_rate', 'reviews_given',
        'reviews_received', 'average_review_score',
        'total_learning_hours', 'current_streak_days',
        'longest_streak_days', 'discussions_created',
        'helpful_replies',
    ];

    protected $casts = [
        'projects_started' => 'integer',
        'projects_completed' => 'integer',
        'projects_failed' => 'integer',
        'completion_rate' => 'decimal:2',
        'reviews_given' => 'integer',
        'reviews_received' => 'integer',
        'average_review_score' => 'decimal:2',
        'total_learning_hours' => 'integer',
        'current_streak_days' => 'integer',
        'longest_streak_days' => 'integer',
        'discussions_created' => 'integer',
        'helpful_replies' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
