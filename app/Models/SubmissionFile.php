<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionFile extends Model
{
    protected $fillable = [
        'submission_id', 'original_filename', 'stored_filename',
        'file_path', 'mime_type', 'file_size_bytes', 'file_hash',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Admin\Submission::class);
    }
}
