<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'description', 'learning_objectives',
        'cover_image', 'difficulty', 'estimated_hours',
        'order_position', 'is_published', 'is_featured', 'created_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'estimated_hours' => 'integer',
        'order_position' => 'integer',
    ];

    protected $with = ['modules'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('order_position');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(\App\Models\Admin\Project::class);
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(CourseDependency::class, 'depends_on_course_id');
    }

    public function requiredBy(): HasMany
    {
        return $this->hasMany(CourseDependency::class, 'course_id');
    }

    public function pendingProjects(): HasMany
    {
        return $this->hasMany(\App\Models\Admin\Project::class)
            ->where('is_published', true);
    }

    /**
     * Get the required course for a dependency (through CourseDependency).
     */
    public function requiredCourse()
    {
        // Called on the dependency record's depends_on_course_id
        return self::find($this->depends_on_course_id);
    }
}
