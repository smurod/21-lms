<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    protected $table = 'projects';
    protected $fillable = [
        'title', 'slug', 'description', 'instructions', 'hints',
        'course_id', 'module_id',
        'difficulty', 'estimated_hours', 'order_position',
        'language', 'language_version',
        'submission_type', 'allowed_file_extensions', 'max_file_size_mb', 'has_automated_tests',
        'test_file_path', 'test_timeout_seconds', 'docker_config',
        'xp_reward', 'passing_score',
        'requires_peer_review', 'required_reviews_count',
        'is_published', 'is_mandatory',
        'tags', 'learning_outcomes',
        'created_by',
        'gitlab_project_id', 'repository_url', 'default_branch',
        'runtime', 'min_level',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $casts = [
        'requires_peer_review' => 'boolean',
        'is_published' => 'boolean',
        'is_mandatory' => 'boolean',
        'runtime' => 'array',
        'tags' => 'array',
        'allowed_file_extensions' => 'array',
        'learning_outcomes' => 'array',
        'hints' => 'array',
        'docker_config' => 'array',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(\App\Models\Admin\Submission::class, 'project_id', 'id');
    }

    public function activeSubmissions(): HasOne
    {
        return $this->hasOne(\App\Models\Admin\Submission::class, 'project_id', 'id')
            ->whereIn('status', ['pending', 'queued', 'testing', 'in_review', 'reviewed', 'in_progress', 'resubmitted']);
    }
}
