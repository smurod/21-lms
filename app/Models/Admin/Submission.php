<?php

namespace App\Models\Admin;

use App\Models\TestResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    protected $table = 'submissions';
    protected $fillable = [
        'user_id', 'project_id',
        'submission_type', 'git_url', 'git_commit_hash', 'code_content',
        'status',
        'tests_passed', 'tests_total', 'test_score', 'test_output', 'test_details',
        'review_score', 'reviews_received',
        'final_score', 'is_plagiarized',
        'submitted_at', 'tested_at', 'reviewed_at', 'completed_at',
        'attempt_number',
        'execution_time_ms', 'memory_used_mb',
    ];

    protected $casts = [
        'status' => 'string',
        'test_details' => 'array',
        'tests_passed' => 'integer',
        'tests_total' => 'integer',
        'test_score' => 'decimal:2',
        'review_score' => 'decimal:2',
        'reviews_received' => 'integer',
        'final_score' => 'decimal:2',
        'is_plagiarized' => 'boolean',
        'attempt_number' => 'integer',
        'execution_time_ms' => 'integer',
        'memory_used_mb' => 'integer',
        'submitted_at' => 'datetime',
        'tested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'submission_id', 'id');
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(TestResult::class, 'submission_id', 'id');
    }
}
