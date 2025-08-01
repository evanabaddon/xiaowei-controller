<?php

namespace App\Jobs;

use App\Models\Device;
use App\Jobs\CheckDeviceInternetJob;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckAllDeviceInternetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Device::chunk(50, function ($devices) {
            foreach ($devices as $device) {
                CheckDeviceInternetJob::dispatch($device)->onQueue('internet-check');
            }
        });
    }
}
