<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\InitializeTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [__DIR__.'/../routes/web.php', __DIR__.'/../routes/platform.php', __DIR__.'/../routes/classification.php'],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'saml/acs',
        ]);

        $middleware->redirectGuestsTo(fn () => route('saml.redirect'));

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Fail-closed tenancy: app routes under an authenticated user
        // always run inside a tenant context. SAML endpoints (redirect, ACS,
        // SLS, metadata) and logout stay outside it — the acting tenant can
        // only be resolved after authentication completes.
        $middleware->alias([
            'tenant' => InitializeTenantContext::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Route model binding must resolve AFTER the tenant context exists:
        // the binding query goes through the tenant-scoped models, so an
        // uninitialized context would fail every gated route closed (1=0)
        // and a stale in-process context would resolve another tenant's
        // rows. The default priority already runs auth before bindings; the
        // tenant middleware joins it there.
        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            InitializeTenantContext::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
