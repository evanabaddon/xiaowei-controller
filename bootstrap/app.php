<?php

use App\Models\ContentTask;
use App\Jobs\PingMachineStatusJob;
use Illuminate\Foundation\Application;
use App\Jobs\CheckAllDeviceInternetJob;
use App\Jobs\GenerateContentForAccountJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->job(PingMachineStatusJob::class)->everyMinute();
        $schedule->job(CheckAllDeviceInternetJob::class)->everyMinute();
        $schedule->call(function () {
            ContentTask::where('is_active', true)
                ->where('mode', 'automatic')
                ->each(function ($task) {
                    for ($i = 0; $i < $task->daily_limit; $i++) {
                        GenerateContentForAccountJob::dispatch($task->socialAccount);
                    }
                });
        })->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
