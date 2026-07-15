<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    protected $fillable = [
        'module_id', 'title', 'slug', 'content',
        'content_type', 'video_url', 'estimated_minutes',
        'order_position', 'is_published', 'is_free',
    ];

    protected $casts = [
        'content_type' => 'string',
        'estimated_minutes' => 'integer',
        'order_position' => 'integer',
        'is_published' => 'boolean',
        'is_free' => 'boolean',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
