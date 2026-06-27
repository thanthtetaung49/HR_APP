<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class AdminHRManagerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $roles = $user->roles;

        $isAdmin = $roles->contains(function ($role) {
            return $role->name === 'admin';
        });

        $isHRmanager = $roles->contains(function ($role) {
            return $role->name === 'hr-manager';
        });

        $permission = false;

        if ($isAdmin || $isHRmanager) {
            $permission = true;
        }

        return $permission;
    }

    public function bankReportPermission (User $user): bool
    {
        $roles = $user->roles;

        $isAdmin = $roles->contains(function ($role) {
            return $role->name === 'admin';
        });

        $permission = false;

        if ($isAdmin) {
            $permission = true;
        }

        return $permission;
    }


    public function generatePayroll(User $user): bool
    {
        $roles = $user->roles;

        $isAdmin = $roles->contains(function ($role) {
            return $role->name === 'admin';
        });

        $isHRmanager = $roles->contains(function ($role) {
            return $role->name === 'hr-manager';
        });

        $permission = false;

        if ($isAdmin || $isHRmanager) {
             $permission = true;
        }

        return $permission;
    }


    public function viewPayroll(User $user): bool
    {
        $roles = $user->roles;

        $isAdmin = $roles->contains(function ($role) {
            return $role->name === 'admin';
        });

        $isHRmanager = $roles->contains(function ($role) {
            return $role->name === 'hr-manager';
        });

        $isHROfficer = $roles->contains(function ($role) {
            return $role->name === 'hr-officer';
        });

        $isEmployee = $roles->contains(function ($role) {
            return $role->name === 'employee';
        });

        $permission = false;

        if ($isAdmin || $isHRmanager || $isHROfficer || $isEmployee) {
             $permission = true;
        }

        return $permission;
    }





    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        //
    }
}
