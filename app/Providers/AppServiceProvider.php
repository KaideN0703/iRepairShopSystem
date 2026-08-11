<?php

namespace App\Providers;

use App\Models\JobOrder;
use App\Policies\RepairJobPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admin bypasses every permission check — covers all current and future permissions.
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        // Centralized technician job-scope policy consumed by all job-related controllers.
        Gate::policy(JobOrder::class, RepairJobPolicy::class);

        // Scoped view gates
        Gate::define('customers.view.scoped', function ($user, $customer = null) {
            if ($user->hasAnyRole(['admin', 'shop_manager', 'cashier'])) {
                return true;
            }
            if ($customer) {
                return $customer->devices()
                    ->whereHas('jobOrders', fn ($q) => $q->where('technician_id', $user->technician?->id))
                    ->exists();
            }
            return $user->hasRole('technician');
        });

        Gate::define('warranty.view.scoped', function ($user, $warranty = null) {
            if ($user->hasAnyRole(['admin', 'shop_manager', 'cashier'])) {
                return true;
            }
            if ($warranty) {
                return $warranty->jobOrder?->technician_id === $user->technician?->id;
            }
            return $user->hasRole('technician');
        });

        Gate::define('inventory.view', fn ($user) => $user->hasAnyRole(['admin', 'shop_manager', 'technician', 'inventory_staff']));

        Gate::define('reports.view.own', fn ($user) => $user->hasAnyRole(['admin', 'shop_manager', 'technician']));

        Gate::define('technicians.view.assignments', fn ($user) =>
            $user->hasAnyRole(['admin', 'shop_manager', 'cashier', 'technician'])
        );
    }
}
