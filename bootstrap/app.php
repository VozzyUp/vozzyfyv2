<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Redirect de convidados para /login (evita RouteNotFoundException quando route('login') não está disponível, ex.: cache de rotas)
        $middleware->redirectGuestsTo(fn () => url('/login'));

        // Webhooks recebem POST de gateways externos sem CSRF token
        $middleware->validateCsrfTokens(except: [
            'webhooks/gateways/*',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\RunScheduleFallback::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'guest' => \App\Http\Middleware\EnsureGuest::class,
            'api.application' => \App\Http\Middleware\AuthenticateApiApplication::class,
            'member.area.resolve' => \App\Http\Middleware\ResolveMemberAreaProduct::class,
            'member.area.resolve.by.host' => \App\Http\Middleware\ResolveMemberAreaByHost::class,
            'member.area.access' => \App\Http\Middleware\EnsureMemberAreaAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->job(new \App\Jobs\SendSubscriptionRemindersJob)->dailyAt('09:00');
        $schedule->command('checkout:fire-abandoned-cart-webhooks --hours=2')->hourly();
        $schedule->command('email-campaign:process')->everyMinute();
        $schedule->command('schedule:heartbeat')->everyMinute();
        $schedule->job(new \App\Jobs\QueueHeartbeatJob)->everyMinute();
    })
    ->create();
