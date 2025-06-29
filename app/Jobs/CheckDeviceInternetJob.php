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

    public int $tries = 2;
    public int $timeout = 10;

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
    // app/Jobs/CheckDeviceInternetJob.php
    public function handle(): void
    {
        $serial = $this->device->serial;
        
        try {
            // Gunakan timeout lebih pendek
            $client = new Client($this->device->machine->ws_url, [
                'timeout' => 3, // Turunkan dari 5 ke 3 detik
                'headers' => [
                    'X-Quick-Check' => '1',
                    'Connection' => 'close'
                ]
            ]);

            // Gabungkan semua command dalam satu request
            $client->send(json_encode([
                'action' => 'multi_command',
                'devices' => $serial,
                'data' => [
                    'commands' => [
                        'ip' => 'adb exec-out ip addr show wlan0',
                        'ping' => 'adb exec-out ping -c 1 -W 1 8.8.8.8'
                    ]
                ]
            ]));

            $response = json_decode($client->receive(), true);
            $client->close();

            // Proses hasil
            $ip = $this->extractIp($response['data'][$serial]['ip'] ?? '');
            $isConnected = str_contains($response['data'][$serial]['ping'] ?? '', 'bytes from');

            $this->device->update([
                'internet_ip' => $ip,
                'internet_status' => $isConnected ? 'connected' : 'disconnected',
                'last_checked_at' => now()
            ]);

        } catch (\Exception $e) {
            \Log::warning("Device check failed: {$serial} - {$e->getMessage()}");
            $this->handleFailure();
        }
    }

    private function extractIp(string $output): ?string
    {
        preg_match('/inet (\d+\.\d+\.\d+\.\d+)/', $output, $matches);
        return $matches[1] ?? null;
    }

    private function handleFailure(): void
    {
        $this->device->update([
            'internet_status' => 'error',
            'last_error' => now()
        ]);
    }
}
