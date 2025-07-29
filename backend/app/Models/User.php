<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'firebase_uid',
        'salary'
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
            'salary' => 'decimal:2',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function memories(): HasMany
    {
        return $this->hasMany(UserMemory::class);
    }

    /**
     * Get user's points for all merchants
     */
    public function merchantPoints(): HasMany
    {
        return $this->hasMany(UserMerchantPoints::class);
    }

    /**
     * Get user's assets
     */
    public function assets(): HasMany
    {
        return $this->purchases()->where('asset_type', 'asset');
    }

    /**
     * Get user's liabilities
     */
    public function liabilities(): HasMany
    {
        return $this->purchases()->where('asset_type', 'liability');
    }

    /**
     * The merchants (tenants) this user belongs to.
     */
    public function merchants(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Merchant::class, 'merchant_user');
    }

    /**
     * Return the tenants this user can access (for Filament tenancy).
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->merchants;
    }

    /**
     * Determine if the user can access the given tenant.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->merchants()->whereKey($tenant->getKey())->exists();
    }

    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // You can customize this logic as needed
        return true;
    }
}
