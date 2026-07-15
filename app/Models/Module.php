<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Module extends Model
{
    protected $fillable = [
        'course_id', 'title', 'slug', 'description',
        'order_position', 'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'order_position' => 'integer',
    ];

    protected $with = ['lessons'];

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order_position');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(\App\Models\Admin\Project::class);
    }
}
