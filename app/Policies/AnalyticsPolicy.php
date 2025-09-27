<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnalyticsPolicy
{
    use HandlesAuthorization;

    public function view(User $user)
    {
        // Allow all authenticated users to view analytics
        return true;
    }

    public function viewDetailed(User $user)
    {
        // Only admin users can view detailed analytics
        return $user->isAdmin();
    }
}
