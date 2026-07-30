<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewChecklist extends Model
{
    protected $table = 'review_checklists';

    protected $fillable = [
        'project_id',
        'item_key',
        'item_label',
        'description',
        'weight',
        'is_required',
        'order_position',
    ];

    protected $casts = [
        'weight' => 'integer',
        'is_required' => 'boolean',
        'order_position' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }
}
