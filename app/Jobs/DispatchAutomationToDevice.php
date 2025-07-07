<?php

namespace App\Jobs;

use App\Models\Device;
use Illuminate\Bus\Queueable;
use App\Models\AutomationTask;
use App\Models\GeneratedContent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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

    public function handle(): void
    {
        $androidId = $this->device->android_id;

        if (empty($androidId)) {
            Log::warning("❌ Device ID {$this->device->id} tidak punya android_id.");
            return;
        }

        Cache::put("trigger_for_{$androidId}", $this->task->id, now()->addMinutes(5));

        $socialAccounts = \App\Models\SocialAccount::where('device_id', $this->device->id)->pluck('id')->toArray();

        if (empty($socialAccounts)) {
            Log::warning("❌ Tidak ada social account untuk device {$androidId}.");
            return;
        }

        Cache::put("queue_for_{$androidId}", $socialAccounts, now()->addMinutes(10));

        // Jangan update ke published dulu
        Log::info("📤 Menandai task untuk device {$androidId}", [
            'task_id' => $this->task->id,
            'device_id' => $this->device->id,
        ]);
    }


}
