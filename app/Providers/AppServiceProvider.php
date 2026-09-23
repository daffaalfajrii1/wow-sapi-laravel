<?php

namespace App\Providers;

use App\Models\Breed;
use App\Models\Cattle;
use App\Models\FarmerProfile;
use App\Models\Vaccine;
use App\Policies\BreedPolicy;
use App\Policies\CattlePolicy;
use App\Policies\FarmerProfilePolicy;
use App\Policies\VaccinePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    public function boot(): void
    {
        \Carbon\Carbon::setLocale('id');
        \Illuminate\Support\Carbon::setLocale('id');
        Gate::policy(Cattle::class, CattlePolicy::class);
        Gate::policy(Breed::class, BreedPolicy::class);
        Gate::policy(Vaccine::class, VaccinePolicy::class);
        Gate::policy(FarmerProfile::class, FarmerProfilePolicy::class);
    }
}
