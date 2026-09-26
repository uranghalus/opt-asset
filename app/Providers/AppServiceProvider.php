<?php

namespace App\Providers;

use App\Tenancy\TenantContext;
use App\Tenancy\UlidIdentifierGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Saml2\Saml2ExtendSocialite;
use Stancl\Tenancy\Contracts\UniqueIdentifierGenerator;

/**
 * Fail-closed tenancy architecture (ticket T01 / GitHub #9, decision
 * 2026-09-25): ULID tenant identifiers instead of the package default UUIDs
 * — sortable, URL-safe, and stable in composite unique constraints.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Tenant ids are ULIDs: sortable, URL-safe, and stable inside the
        // composite unique constraints every domain table carries.
        $this->app->bind(UniqueIdentifierGenerator::class, UlidIdentifierGenerator::class);

        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Event::listen(
            SocialiteWasCalled::class,
            [Saml2ExtendSocialite::class, 'handle']
        );
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
