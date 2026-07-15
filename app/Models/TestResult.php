<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResult extends Model
{
    protected $fillable = [
        'submission_id', 'project_test_id', 'test_name',
        'passed', 'error_message', 'output',
        'execution_time_ms', 'points_earned', 'points_possible',
    ];

    protected $casts = [
        'passed' => 'boolean',
        'execution_time_ms' => 'integer',
        'points_earned' => 'integer',
        'points_possible' => 'integer',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\Submission::class);
    }

    public function projectTest(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\ProjectTest::class, 'project_test_id');
    }
}
