<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Collection;

trait HasRoleHelpers
{
    /**
     * Check if user is a superadmin
     */
    protected function isSuperAdmin($user = null): bool
    {
        $user = $user ?: Auth::user();
        if (!$user) return false;
        
        return $user->isSuperAdmin();
    }

    /**
     * Check if user is a handler
     */
    protected function isHandler($user = null): bool
    {
        $user = $user ?: Auth::user();
        if (!$user) return false;
        
        return $user->isHandler();
    }

    /**
     * Check if user has any of the specified roles
     */
    protected function hasAnyRole($roles, $user = null): bool
    {
        $user = $user ?: Auth::user();
        if (!$user) return false;

        return $user->hasAnyRole($roles);
    }

    /**
     * Check if user has a specific role
     */
    protected function hasRole($role, $user = null): bool
    {
        $user = $user ?: Auth::user();
        if (!$user) return false;
        
        return $user->hasRole($role);
    }

    /**
     * Check if user can access a specific company
     */
    protected function canAccessCompany($companyId, $user = null): bool
    {
        $user = $user ?: Auth::user();
        
        if (!$user) return false;
        
        return $user->canAccessCompany($companyId);
    }

    /**
     * Get companies available to the current user
     */
    protected function getAvailableCompanies($user = null): Collection
    {
        $user = $user ?: Auth::user();
        
        if (!$user) return collect();
        
        if ($user->isSuperAdmin()) {
            return Company::where('is_active', true)->get();
        }
        
        if ($user->isHandler()) {
            return $user->companies()->where('is_active', true)->get();
        }
        
        return collect();
    }

    /**
     * Get company options for select fields
     */
    protected function getCompanyOptions($user = null): array
    {
        return $this->getAvailableCompanies($user)->pluck('company_name', 'id')->toArray();
    }

    /**
     * Check if current user can access the resource
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        return $user->hasAnyRole(['superadmin', 'handler']);
    }

    /**
     * Static version of isSuperAdmin for use in static contexts
     */
    public static function userIsSuperAdmin($user = null): bool
    {
        $user = $user ?: Auth::user();
        if (!$user) return false;
        
        return $user->isSuperAdmin();
    }

    /**
     * Static version of isHandler for use in static contexts
     */
    public static function userIsHandler($user = null): bool
    {
        $user = $user ?: Auth::user();
        if (!$user) return false;
        
        return $user->isHandler();
    }

    /**
     * Get user's accessible company IDs
     */
    protected function getAccessibleCompanyIds($user = null): array
    {
        $user = $user ?: Auth::user();
        
        if (!$user) return [];
        
        if ($user->isSuperAdmin()) {
            return Company::where('is_active', true)->pluck('id')->toArray();
        }
        
        if ($user->isHandler()) {
            return $user->getCompanyIds();
        }
        
        return [];
    }

    /**
     * Apply company filtering to a query based on user permissions
     */
    protected function applyCompanyFiltering($query, $companyIdField = 'company_id', $user = null)
    {
        $user = $user ?: Auth::user();
        
        if (!$user) {
            return $query->whereRaw('1 = 0'); // Return no results
        }
        
        if ($user->isSuperAdmin()) {
            return $query; // No filtering for superadmin
        }
        
        if ($user->isHandler()) {
            $companyIds = $user->getCompanyIds();
            return $query->whereIn($companyIdField, $companyIds);
        }
        
        return $query->whereRaw('1 = 0'); // Return no results for unrecognized roles
    }

    /**
     * Get default company ID for current user
     */
    protected function getDefaultCompanyId($user = null): ?int
    {
        $user = $user ?: Auth::user();
        
        if (!$user) return null;
        
        if ($user->isSuperAdmin()) {
            return Company::where('is_active', true)->first()?->id;
        }
        
        if ($user->isHandler()) {
            return $user->getPrimaryCompany()?->id;
        }
        
        return null;
    }

    /**
     * Check if user should have company selection disabled (handlers with single company)
     */
    protected function shouldDisableCompanySelection($user = null): bool
    {
        $user = $user ?: Auth::user();
        
        if (!$user) return true;
        
        if ($user->isSuperAdmin()) {
            return false; // Superadmin can always select
        }
        
        if ($user->isHandler()) {
            return $user->companies()->count() <= 1; // Disable if single company
        }
        
        return true; // Disable for other roles
    }
}
