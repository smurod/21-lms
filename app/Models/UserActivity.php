<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserActivity extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'event_type', 'event_category',
        'subject_type', 'subject_id', 'metadata',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'event_type' => 'string',
        'event_category' => 'string',
        'subject_type' => 'string',
        'subject_id' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
