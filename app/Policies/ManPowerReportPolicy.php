<?php

namespace App\Policies;

use App\Models\ManPowerReport;
use App\Models\ReportPermission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ManPowerReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $reportPermission = ReportPermission::where('user_id', $user->id)->first();
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
        } else {
            $permission = $reportPermission?->permission == 'yes'  ? true : false;
        }


        return $permission;
    }

    public function canApprove(User $user): bool
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
        } else {
            $permission = false;
        }

        return $permission;
    }

    public function canRemarkByEmployee(User $user): bool
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
            $permission = false;
        } else {
            $permission = true;
        }

        return $permission;
    }
}
