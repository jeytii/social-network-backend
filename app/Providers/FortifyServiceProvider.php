<?php

namespace App\Providers;

use App\Actions\Fortify\{
    CreateNewUser,
    ResetUserPassword,
    UpdateUserPassword,
    UpdateUserProfileInformation
};
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        // Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        // Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);

        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::resetPasswordView(fn($request) => view('auth.reset-password', compact('request')));

        // RateLimiter::for('login', function (Request $request) {
        //     return Limit::perMinute(5)->by($request->email.$request->ip());
        // });
    }
}
