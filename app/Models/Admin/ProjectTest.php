<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTest extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'test_code',
        'points',
        'order_position',
        'is_hidden',
        'test_type',
        'timeout_seconds',
    ];

    protected $casts = [
        'points' => 'integer',
        'order_position' => 'integer',
        'is_hidden' => 'boolean',
        'timeout_seconds' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(\App\Models\TestResult::class, 'project_test_id');
    }
}
