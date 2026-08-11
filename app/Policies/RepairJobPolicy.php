<?php

namespace App\Policies;

use App\Models\JobOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RepairJobPolicy
{
    use HandlesAuthorization;

    /**
     * Any authenticated user who has at least one repair-related permission may list job orders.
     * Finer scoping (own vs all) is applied inside the controller query.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAny([
            'repairs.view.own',
            'repairs.view.status',
            'repairs.manage',
            'jobs.manage.full',
            'jobs.create',
        ]);
    }

    /**
     * Determine if a user can view a specific job order.
     */
    public function view(User $user, JobOrder $job): bool
    {
        if ($user->can('jobs.manage.full') || $user->can('repairs.view.status') || $user->hasAnyRole(['admin', 'shop_manager', 'cashier'])) {
            return true;
        }

        if ($user->can('repairs.view.own') || $user->can('repairs.manage') || $user->hasRole('technician')) {
            return $job->technician_id === $user->technician?->id;
        }

        return false;
    }

    /**
     * admin / shop_manager always pass via Gate::before; this handles technicians.
     * A technician may only act on jobs where their technician_id matches the job.
     */
    public function manage(User $user, JobOrder $job): bool
    {
        if ($user->can('jobs.manage.full') || $user->hasAnyRole(['admin', 'shop_manager'])) {
            return true;
        }

        if ($user->can('repairs.manage') || $user->can('diagnosis.manage') || $user->can('parts.usage.create')) {
            return $job->technician?->user_id === $user->id;
        }

        return false;
    }
}
