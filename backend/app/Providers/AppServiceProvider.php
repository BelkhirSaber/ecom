<?php

namespace App\Providers;

use App\Models\Order;
use App\Policies\OrderPolicy;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Eloquent\EloquentCartRepository;
use App\Services\Payment\PaymentProviderResolver;
use App\Services\Payment\Providers\PaymentProviderInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CartRepositoryInterface::class, EloquentCartRepository::class);
        $this->app->bind(PaymentProviderInterface::class, function ($app) {
            $resolver = $app->make(PaymentProviderResolver::class);
            return $resolver->resolve();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email', '');
            $email = strtolower(trim($email));

            return Limit::perMinute(10)->by($request->ip().'|'.$email);
        });

        Gate::policy(Order::class, OrderPolicy::class);
    }
}
