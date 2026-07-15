<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'rarity',
        'xp_reward', 'condition_type', 'condition_value',
        'condition_params', 'is_hidden', 'is_active',
    ];

    protected $casts = [
        'condition_value' => 'integer',
        'condition_params' => 'array',
        'is_hidden' => 'boolean',
        'is_active' => 'boolean',
        'xp_reward' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }
}
