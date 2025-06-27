<?php

namespace App\Jobs;

use App\Models\Device;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use WebSocket\Client;

class CheckDeviceInternetJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, Dispatchable, SerializesModels;

    protected Device $device;

    /**
     * Create a new job instance.
     */
    public function __construct(Device $device)
    {
        $this->device = $device;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $serial = $this->device->serial;
        $machine = $this->device->machine;
        $url = $machine->ws_url;

        try {
            Log::info("📡 Checking internet status for {$serial}");

            $client = new Client($url, ['timeout' => 5]);
            $client->send(json_encode([
                'action' => 'adb',
                'devices' => $serial,
                'data' => [
                    'command' => 'adb exec-out ip addr show wlan0'
                ]
            ]));

            $response = json_decode($client->receive(), true);
            $client->close();

            if ($response['code'] === 10000 && isset($response['data'][$serial])) {
                $raw = $response['data'][$serial];

                $ip = null;
                if (preg_match('/inet (\d+\.\d+\.\d+\.\d+)/', $raw, $matches)) {
                    $ip = $matches[1];
                }

                $this->device->update([
                    'internet_ip' => $ip,
                    'internet_status' => $ip ? 'connected' : 'disconnected',
                ]);
            } else {
                $this->device->update([
                    'internet_ip' => null,
                    'internet_status' => 'disconnected',
                ]);
            }

        } catch (\Exception $e) {
            Log::warning("❌ Failed internet check for {$serial}: {$e->getMessage()}");

            $this->device->update([
                'internet_ip' => null,
                'internet_status' => 'disconnected',
            ]);
        }
    
    }
}
