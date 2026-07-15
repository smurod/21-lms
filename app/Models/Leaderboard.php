<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leaderboard extends Model
{
    protected $fillable = [
        'user_id', 'period', 'period_start', 'period_end',
        'xp_earned', 'projects_completed', 'reviews_given',
        'average_score', 'rank',
    ];

    protected $casts = [
        'period' => 'string',
        'period_start' => 'date',
        'period_end' => 'date',
        'xp_earned' => 'integer',
        'projects_completed' => 'integer',
        'reviews_given' => 'integer',
        'average_score' => 'decimal:2',
        'rank' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
