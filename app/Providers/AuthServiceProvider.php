<?php

namespace App\Providers;

use App\Models\Location;
use App\Models\ManPowerReport;
use App\Models\ReportPermission;
use App\Models\User;
use App\Policies\BankReportPolicy;
use App\Policies\CriteriaPolicy;
use App\Policies\CriteriaReportPolicy;
use App\Policies\LocationPolicy;
use App\Policies\ManagementRankPolicy;
use App\Policies\ManPowerReportPolicy;
use App\Policies\ReportPermissionPolicy;
use App\Policies\SubCriteriaPolicy;
use App\Policies\TurnOverReportPolicy;
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
        ReportPermission::class => ManPowerReportPolicy::class,
        User::class => BankReportPolicy::class,
        User::class => CriteriaReportPolicy::class,
        User::class => ManagementRankPolicy::class,
        User::class => SubCriteriaPolicy::class,
        User::class => CriteriaPolicy::class,
        User::class => ReportPermissionPolicy::class,
        User::class => TurnOverReportPolicy::class,
        User::class => LocationPolicy::class
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
