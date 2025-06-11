<?php

namespace App\Providers;

use App\Models\Estimate;
use App\Models\Project;
use App\Models\User;
use App\Policies\EstimatePolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        Project::class => ProjectPolicy::class,
        Estimate::class => EstimatePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Определение ролей
        Gate::define('admin', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('partner', function (User $user) {
            return $user->isPartner() || $user->isAdmin();
        });

        Gate::define('client', function (User $user) {
            return $user->isClient() || $user->isAdmin();
        });
    }
}
