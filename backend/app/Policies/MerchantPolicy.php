<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Merchant;
use Illuminate\Auth\Access\HandlesAuthorization;

class MerchantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_merchant');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Merchant $merchant): bool
    {
        return $user->can('view_merchant');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_merchant');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Merchant $merchant): bool
    {
        return $user->can('update_merchant');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Merchant $merchant): bool
    {
        return $user->can('delete_merchant');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_merchant');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Merchant $merchant): bool
    {
        return $user->can('force_delete_merchant');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_merchant');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Merchant $merchant): bool
    {
        return $user->can('restore_merchant');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_merchant');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Merchant $merchant): bool
    {
        return $user->can('replicate_merchant');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_merchant');
    }
}
