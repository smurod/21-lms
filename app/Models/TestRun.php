<?php

namespace App\Models;

use App\Models\Admin\Submission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestRun extends Model
{
    protected $fillable = [
        'submission_id', 'status', 'runner', 'image', 'command',
        'commit_hash', 'started_at', 'finished_at', 'duration_ms',
        'exit_code', 'score', 'logs', 'artifacts_path', 'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
        'exit_code' => 'integer',
        'score' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class);
    }
}
