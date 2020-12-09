<?php

namespace App\Providers;

use App\Models\AuthToken;
use Illuminate\Auth\RequestGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::viaRequest("apikey", function (Request $request) {
            // DB token values aren't nullable so this check is unneeded
            // However, if they became nullable in the future all empty tokens would match the first null token
            // so the check exists for extra safety
            if ($token = $request->bearerToken()) {
                $model = AuthToken::firstWhere("token", $token);
                if ($model) {
                    $model->forceFill(['last_used_at' => now()])->save();
                }
                return $model;
            }
            return null;
        });

    }
}
