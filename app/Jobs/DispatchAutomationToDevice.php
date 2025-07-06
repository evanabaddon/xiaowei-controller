<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\AutomationTask;
use App\Models\Device;

class DispatchAutomationToDevice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $task;
    public $device;

    public function __construct(AutomationTask $task, Device $device)
    {
        $this->task = $task;
        $this->device = $device;
    }

    // public function handle(): void
    // {
    //     $androidId = $this->device->android_id;

    //     // Cukup beri tanda bahwa device ini harus fetch automation task
    //     Cache::put("trigger_for_{$androidId}", $this->task->id, now()->addMinutes(5));

    //     Log::info("📤 Menandai task untuk device {$androidId}", [
    //         'task_id' => $this->task->id,
    //         'device_id' => $this->device->id,
    //     ]);
    // }
    public function handle(): void
    {
        $androidId = $this->device->android_id;
        Cache::put("trigger_for_{$androidId}", $this->task->id, now()->addMinutes(5));

        // Inisialisasi queue akun sosial hanya saat task baru dikirim
        $socialAccounts = \App\Models\SocialAccount::where('device_id', $this->device->id)->pluck('id')->toArray();
        Cache::put("queue_for_{$androidId}", $socialAccounts, now()->addMinutes(10));

        Log::info("📤 Menandai task untuk device {$androidId}", [
            'task_id' => $this->task->id,
            'device_id' => $this->device->id,
        ]);
    }

}
