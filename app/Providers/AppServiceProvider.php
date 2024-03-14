<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
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
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Sanctum::authenticateAccessTokensUsing(
            static function (PersonalAccessToken $accessToken, bool $is_valid) {
                $expiration = $accessToken->name == 'access' ? config('sanctum.expiration') : config('sanctum.rt_expiration');

                return $accessToken->created_at ? $accessToken->created_at->gt(now()->subMinutes($expiration)) : false;
            }
        );
    }
}
