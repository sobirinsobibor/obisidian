<?php

namespace App\Models;

use App\Enums\SpaceRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_global_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_global_admin' => 'boolean',
        ];
    }

    public function isGlobalAdmin(): bool
    {
        return (bool) $this->is_global_admin;
    }

    public function spaces(): BelongsToMany
    {
        return $this->belongsToMany(Space::class, 'space_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'owner_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(NoteReview::class, 'reviewer_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(NoteComment::class);
    }

    public function roleInSpace(Space|int $space): ?SpaceRole
    {
        if ($this->isGlobalAdmin()) {
            return SpaceRole::ADMIN;
        }

        $spaceId = $space instanceof Space ? $space->id : $space;

        $membership = $this->spaces()->where('spaces.id', $spaceId)->first();

        if (! $membership || ! $membership->pivot || ! $membership->pivot->role) {
            return null;
        }

        return SpaceRole::tryFrom($membership->pivot->role);
    }

    public function hasRoleInSpace(Space|int $space, SpaceRole|array $roles): bool
    {
        if ($this->isGlobalAdmin()) {
            return true;
        }

        $userRole = $this->roleInSpace($space);
        if (! $userRole) {
            return false;
        }

        if ($roles instanceof SpaceRole) {
            return $userRole === $roles;
        }

        return in_array($userRole, $roles, true);
    }
}
