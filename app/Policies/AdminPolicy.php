<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class AdminPolicy
{
    /**
     * Only admins can access admin functions.
     * This middleware/policy is used to gate admin routes.
     */

    /**
     * Determine if the user can view the admin dashboard.
     */
    public function viewDashboard(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can view all users.
     */
    public function viewUsers(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can view user details.
     */
    public function viewUser(User $user, User $targetUser): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can update a user.
     */
    public function updateUser(User $user, User $targetUser): bool
    {
        // Admins can update any user except themselves (edit own profile via separate endpoint)
        return $user->hasRole('admin') && $user->id !== $targetUser->id;
    }

    /**
     * Determine if the user can delete a user.
     */
    public function deleteUser(User $user, User $targetUser): bool
    {
        // Admins can delete any user except themselves
        return $user->hasRole('admin') && $user->id !== $targetUser->id;
    }

    /**
     * Determine if the user can assign roles to a user.
     */
    public function assignRole(User $user, User $targetUser): bool
    {
        // Admins can assign roles to other users
        return $user->hasRole('admin') && $user->id !== $targetUser->id;
    }

    /**
     * Determine if the user can view content management.
     */
    public function viewContent(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can delete subjects.
     */
    public function deleteContent(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can view activity logs.
     */
    public function viewActivityLogs(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can manage permissions.
     */
    public function managePermissions(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
