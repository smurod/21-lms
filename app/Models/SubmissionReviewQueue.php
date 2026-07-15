<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Admin\Submission;
use App\Models\CalendarSlot;

class SubmissionReviewQueue extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'submission_id',
        'user_id',
        'slot_id',
        'status',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(CalendarSlot::class);
    }

    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    public function isAssigned(): bool
    {
        return $this->status === 'assigned';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting')->orderBy('position', 'asc');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSlot($query, $slotId)
    {
        return $query->where('slot_id', $slotId);
    }
}
