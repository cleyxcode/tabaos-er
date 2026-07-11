<?php

namespace App\Providers;

use App\Models\Ambulans;
use App\Models\Faskes;
use App\Models\LaporanBencana;
use App\Models\PedomanBhd;
use App\Models\Penugasan;
use App\Models\Relawan;
use App\Models\User;
use App\Models\ZonaRawanBencana;
use App\Observers\LaporanBencanaObserver;
use App\Policies\AmbulansPolicy;
use App\Policies\FaskesPolicy;
use App\Policies\LaporanBencanaPolicy;
use App\Policies\PedomanBhdPolicy;
use App\Policies\PenugasanPolicy;
use App\Policies\RelawanPolicy;
use App\Policies\UserPolicy;
use App\Policies\ZonaRawanBencanaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Observer
        LaporanBencana::observe(LaporanBencanaObserver::class);

        // Policies
        Gate::policy(LaporanBencana::class, LaporanBencanaPolicy::class);
        Gate::policy(Faskes::class, FaskesPolicy::class);
        Gate::policy(Ambulans::class, AmbulansPolicy::class);
        Gate::policy(Relawan::class, RelawanPolicy::class);
        Gate::policy(Penugasan::class, PenugasanPolicy::class);
        Gate::policy(ZonaRawanBencana::class, ZonaRawanBencanaPolicy::class);
        Gate::policy(PedomanBhd::class, PedomanBhdPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
