<?php

use App\Models\ContentTask;
use App\Jobs\PingMachineStatusJob;
use Illuminate\Support\Facades\Log;
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
            Log::info("✅ Scheduler jalan di " . now());
        
            $tasks = \App\Models\ContentTask::where('active', 1)
                ->where('mode', 'scheduled')
                ->get();
        
            Log::info("🧩 Jumlah task ditemukan: " . $tasks->count());
        
            $tasks->each(function ($task) {
                if (!$task->socialAccount) {
                    Log::warning("⚠️ Task ID {$task->id} tidak punya relasi socialAccount");
                    return;
                }
        
                Log::info("🎯 Memproses task ID {$task->id} untuk akun: " . $task->socialAccount->username);
        
                for ($i = 0; $i < $task->daily_quota; $i++) {
                    if ($task->socialAccount && $task->socialAccount->persona) {
                        GenerateContentForAccountJob::dispatch($task->socialAccount->persona);
                    } else {
                        Log::warning("[Scheduler] SocialAccount atau Persona tidak ditemukan untuk task ID {$task->id}");
                    }
                    
                    Log::info("🚀 Dispatch job ke queue untuk: " . $task->socialAccount->username);
                }
            });
        })
        // ->everyMinute(); // Ubah ke dailyAt('00:15') kalau sudah yakin
        ->dailyAt('00:15');
        
        
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
