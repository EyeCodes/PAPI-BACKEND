<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The companies that belong to the user.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Company::class, 'company_user')
            ->withTimestamps(); // Add timestamps if your pivot table has them
    }

    /**
     * Check if user is a superadmin
     * This method provides a fallback if Spatie Permission isn't working
     */
    public function isSuperAdmin(): bool
    {
        try {
            // First try using Spatie's hasRole method
            return $this->hasRole('superadmin');
        } catch (\Exception $e) {
            // Fallback: check roles relationship directly
            return $this->roles()->where('name', 'superadmin')->exists();
        }
    }

    /**
     * Check if user is a handler
     */
    public function isHandler(): bool
    {
        try {
            return $this->hasRole('handler');
        } catch (\Exception $e) {
            return $this->roles()->where('name', 'handler')->exists();
        }
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole($roles): bool
    {
        try {
            // Use parent hasAnyRole method from Spatie
            return parent::hasAnyRole($roles);
        } catch (\Exception $e) {
            // Fallback implementation
            $roles = is_array($roles) ? $roles : [$roles];
            return $this->roles()->whereIn('name', $roles)->exists();
        }
    }

    /**
     * Override hasRole to provide fallback
     */
    public function hasRole($role, $guardName = null): bool
    {
        try {
            // Use parent hasRole method from Spatie
            return parent::hasRole($role, $guardName);
        } catch (\Exception $e) {
            // Fallback implementation
            return $this->roles()->where('name', $role)->exists();
        }
    }

    /**
     * Check if user can access a specific company
     */
    public function canAccessCompany($companyId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isHandler()) {
            return $this->companies()->where('companies.id', $companyId)->exists();
        }

        return false;
    }

    /**
     * Get user's company IDs (for easier querying)
     */
    public function getCompanyIds(): array
    {
        return $this->companies()->pluck('companies.id')->toArray();
    }

    /**
     * Check if user belongs to a specific company
     */
    public function belongsToCompany($companyId): bool
    {
        return $this->companies()->where('companies.id', $companyId)->exists();
    }

    /**
     * Get user's primary company (first company they belong to)
     */
    public function getPrimaryCompany()
    {
        return $this->companies()->first();
    }

    /**
     * Scope to filter users by company access for the current user
     */
    public function scopeAccessibleToUser($query, User $user)
    {
        if ($user->isSuperAdmin()) {
            return $query; // Superadmin can see all users
        }

        if ($user->isHandler()) {
            $companyIds = $user->getCompanyIds();
            return $query->where(function ($subQuery) use ($user, $companyIds) {
                $subQuery->where('id', $user->id) // Always include themselves
                    ->orWhereHas('companies', function ($companyQuery) use ($companyIds) {
                        $companyQuery->whereIn('companies.id', $companyIds);
                    });
            });
        }

        return $query->where('id', $user->id); // Default: only themselves
    }
}