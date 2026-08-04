<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $table = 'reviews';
    protected $fillable = [
        'submission_id', 'reviewer_id',
        'score', 'feedback', 'private_notes',
        'checklist_data',
        'time_spent_minutes', 'confidence_score',
        'is_mentor_review', 'is_auto_assigned', 'is_appeal_review',
        'status',
        'is_calibration', 'accuracy_score',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'checklist_data' => 'array',
        'confidence_score' => 'decimal:2',
        'is_mentor_review' => 'boolean',
        'is_auto_assigned' => 'boolean',
        'is_appeal_review' => 'boolean',
        'status' => 'string',
        'is_calibration' => 'boolean',
        'accuracy_score' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id', 'id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id', 'id');
    }
}
