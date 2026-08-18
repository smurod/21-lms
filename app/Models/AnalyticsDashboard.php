<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsDashboard extends Model
{
    protected $fillable = [
        'user_id',
        'datalens_dashboard_id',
        'workbook_id',
        'connection_id',
        'title',
        'prompt',
        'dashboard_url',
        'embed_url',
        'charts',
        'status',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'charts' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AnalyticsDashboardMessage::class)->oldest();
    }
}
