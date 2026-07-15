<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseDependency extends Model
{
    protected $fillable = [
        'course_id', 'depends_on_course_id', 'order_position',
    ];

    protected $casts = [
        'order_position' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function requiredCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'depends_on_course_id');
    }
}
