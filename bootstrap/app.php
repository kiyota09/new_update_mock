<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Register custom middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'position' => \App\Http\Middleware\CheckPosition::class,
            'can.access.eco' => \App\Http\Middleware\CheckEcoAccess::class,
            'can.access.ord' => \App\Http\Middleware\CanAccessOrd::class,
            'can.access.warehouse' => \App\Http\Middleware\CanAccessWarehouse::class,
            'can.access.inventory' => \App\Http\Middleware\CanAccessInventory::class,
            'can.access.scm' => \App\Http\Middleware\CanAccessScm::class,
            'man.role' => \App\Http\Middleware\EnsureManufacturingRole::class,
            'can.access.procurement' => \App\Http\Middleware\CanAccessProcurement::class,
            'can.access.logistics' => \App\Http\Middleware\CheckLogisticsAccess::class,
            'module.access' => \App\Http\Middleware\CheckModuleAccess::class,
            'can.access.man.manager' => \App\Http\Middleware\CheckManufacturingManagerAccess::class, // NEW
        ]);

        /**
         * ✅ Dynamic Redirection Logic
         * This solves the redirect loop. It checks which user is logged in
         * and sends them to the correct dashboard.
         */
        $middleware->redirectUsersTo(function () {
            if (Auth::guard('client')->check()) {
                return route('client.dashboard');
            }
            return route('dashboard');
        });

        /**
         * ✅ Guest Redirection Logic
         * If a guest tries to access a protected page, redirect them to the
         * correct login page based on the URL prefix.
         */
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('partner/*') || $request->is('client/*')) {
                return route('client.login');
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();