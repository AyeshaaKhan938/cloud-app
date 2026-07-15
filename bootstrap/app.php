<?php

use App\Http\Middleware\EnsureLotteryManagementToken;
use App\Http\Middleware\SyncPublicDiskUrlForLocalRequests;
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
        $middleware->web(prepend: [
            SyncPublicDiskUrlForLocalRequests::class,
        ]);

        $middleware->alias([
            'lottery.management' => EnsureLotteryManagementToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('operator-alerts:email')->everyFifteenMinutes();
        $schedule->command('age-verification:delete-expired-documents')->hourly();
        $schedule->command('analytics:daily-email')->hourly();
    })
    ->create();
