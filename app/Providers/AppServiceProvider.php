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
    }
}
