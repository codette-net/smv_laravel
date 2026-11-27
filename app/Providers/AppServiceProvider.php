<?php

namespace App\Providers;

use App\Contracts\VacancyPackagePriorityProvider;
use App\Payments\Stripe\StripeCheckoutClient;
use App\Payments\Stripe\StripeOrderOperationsClient;
use App\Payments\Stripe\StripeSdkCheckoutClient;
use App\Payments\Stripe\StripeSdkOrderOperationsClient;
use App\Support\Commerce\EntitledVacancyPackagePriority;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeCheckoutClient::class, StripeSdkCheckoutClient::class);
        $this->app->singleton(StripeOrderOperationsClient::class, StripeSdkOrderOperationsClient::class);

        $this->app->singleton(
            VacancyPackagePriorityProvider::class,
            EntitledVacancyPackagePriority::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('contact', fn (Request $request): Limit => Limit::perMinute(
            max(1, (int) config('contact.rate_limit_per_minute')),
        )->by('contact:'.$request->ip())->response(
            fn (Request $request, array $headers) => to_route('contact')
                ->with('contact_error', 'U heeft te veel berichten kort na elkaar verstuurd. Wacht even en probeer het daarna opnieuw.')
                ->withHeaders($headers),
        ));

        RateLimiter::for('public-login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by('public-login:'.Str::lower((string) $request->input('email')).'|'.$request->ip()));

        RateLimiter::for('public-registration', fn (Request $request): Limit => Limit::perMinute(3)
            ->by('public-registration:'.$request->ip()));
    }
}
