<?php

namespace App\Providers;

use App\Models\ManPowerReport;
use App\Models\ReportPermission;
use App\Policies\ManPowerReportPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{

    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        /* 'App\Models\Model' => 'App\Policies\ModelPolicy', */
        ReportPermission::class => ManPowerReportPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // dd('AuthServiceProvider loaded', $this->policies);
    }

}
