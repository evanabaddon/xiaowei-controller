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
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
            'register-device',
            'device/*',
            'api/screenshot',
        ]);
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
                $accountIds = $task->social_account_ids ?? [];
            
                Log::info("🔎 Task ID {$task->id} memiliki account_ids: " . json_encode($accountIds));
            
                $accounts = \App\Models\SocialAccount::whereIn('id', $accountIds)->get();
            
                foreach ($accounts as $account) {
                    if (!$account || !$account->persona) {
                        Log::warning("⚠️ Task ID {$task->id}: akun atau persona tidak valid.");
                        continue;
                    }
            
                    Log::info("🎯 Dispatching job for persona {$account->persona->id} and account {$account->username}");
            
                    for ($i = 0; $i < $task->daily_quota; $i++) {
                        \App\Jobs\GenerateContentForAccountJob::dispatch(
                            $account->persona,
                            [$account->id],
                            $task->automationTask ?? null
                        );
                    }
                }
            });
            
            
            
        })
        ->everyMinute(); 
        // ->dailyAt('00:15');
        $schedule->call(function () {
            $tasks = \App\Models\AutomationTask::where('mode', 'otomatis')->get();
        
            foreach ($tasks as $task) {
                if ($task->last_dispatched_at && $task->last_dispatched_at->isToday()) {
                    Log::info("⏭ AutomationTask ID {$task->id} sudah dijalankan hari ini. Skip.");
                    continue;
                }
        
                $devices = $task->apply_to_all
                    ? \App\Models\Device::all()
                    : collect([$task->device]);
        
                foreach ($devices as $device) {
                    if (empty($device->android_id)) {
                        Log::warning("⚠️ Device ID {$device->id} tidak memiliki android_id. Lewatkan dispatch.");
                        continue;
                    }
                
                    \App\Jobs\DispatchAutomationToDevice::dispatch($task, $device);
                    Log::info("✅ Dispatch automation ke device {$device->android_id} dari task {$task->id}");
                }
        
                // Tandai sudah dikirim hari ini
                $task->update(['last_dispatched_at' => now()]);
            }
        })->everyMinute();
        // ->dailyAt('00:30');
        
        
        
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
