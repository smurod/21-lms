<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use SoftDeletes;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'gitlab_token',
        'level',
        'total_xp',
    ];

    protected $hidden = [
        'password',
        'gitlab_token',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /** @var array<string, string> */
    protected $appends = [
        'profile_photo_url',
    ];

    protected $with = ['roles'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'level' => 'integer',
            'total_xp' => 'integer',
        ];
    }

    // === XP / Leveling ===
    public function xpTransactions()
    {
        return $this->hasMany(XpTransaction::class);
    }

    public function getCurrentLevelAttribute(): int
    {
        $levels = config('xp.levels');
        ksort($levels);
        $current = 1;
        foreach ($levels as $level => $data) {
            if ($this->total_xp >= $data['xp_required']) {
                $current = $level;
            } else {
                break;
            }
        }
        return $current;
    }

    // === Gamification ===
    public function achievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function unlockedAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class)
            ->whereNotNull('unlocked_at');
    }

    public function stat(): HasOne
    {
        return $this->hasOne(UserStat::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }

    public function completedCourses(): \Illuminate\Database\Eloquent\Builder
    {
        return Course::whereHas('pendingProjects.submissions', function ($query) {
            $query->where('user_id', $this->id)
                ->where('status', 'passed');
        });
    }

    public function isCourseCompleted(int $courseId): bool
    {
        $course = Course::with('pendingProjects')->find($courseId);
        if (!$course) {
            return false;
        }

        $completed = \App\Models\Admin\Submission::where('user_id', $this->id)
            ->whereIn('project_id', $course->pendingProjects()->pluck('id'))
            ->where('status', 'passed')
            ->pluck('project_id')
            ->toArray();

        $required = $course->pendingProjects()->pluck('id')->toArray();

        return empty(array_diff($required, $completed));
    }

    // === Git / Submissions ===
    public function submissions()
    {
        return $this->hasMany(\App\Models\Admin\Submission::class);
    }

    // === Chats ===
    public function chatsAsReviewer(): HasMany
    {
        return $this->hasMany(Chat::class, 'reviewer_id');
    }

    public function chatsAsReviewee(): HasMany
    {
        return $this->hasMany(Chat::class, 'reviewee_id');
    }

    // === GitLab account connection ===
    public function hasConnectedGitlab(): bool
    {
        return !empty($this->gitlab_token);
    }

    public function connectedGitlabToken(): ?string
    {
        if (empty($this->gitlab_token)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->gitlab_token);
        } catch (\Throwable) {
            // Backward compatibility if an older local database stored a plain token.
            return $this->gitlab_token;
        }
    }

    public function connectGitlabToken(string $token): void
    {
        $this->forceFill([
            'gitlab_token' => Crypt::encryptString($token),
        ])->save();
    }

    public function disconnectGitlab(): void
    {
        $this->forceFill([
            'gitlab_token' => null,
        ])->save();
    }

    // === Helpers ===
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSubscribed(): bool
    {
        return $this->submissions()
            ->whereIn('status', ['passed', 'reviewed'])
            ->exists();
    }
}
