<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\Machine;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use WebSocket\Client;

class PingMachineStatusJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

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
        $machines = Machine::all();

        Log::info("🔁 Pinging: {$machines->count()} machines");

        foreach ($machines as $machine) {
            Log::info("🔁 Pinging: {$machine->ws_url}");
            try {
                $client = new Client($machine->ws_url, ['timeout' => 5]);

                $client->send(json_encode(['action' => 'list']));
                $response = json_decode($client->receive(), true);

                Log::info("📶 Response from {$machine->name}: " . json_encode($response));

                if ($response['code'] === 10000) {
                    $machine->update([
                        'status' => 'online',
                        'last_checked_at' => now(),
                    ]);
                    // Get devices from the machine and update or create in the database table devices
                    $devices = $response['data']; 
                    foreach ($devices as $dev) {
                        Device::updateOrCreate(
                            ['serial' => $dev['serial']],
                            [
                                'machine_id' => $machine->id,
                                'name' => $dev['name'] ?? $dev['model'],
                                'model' => $dev['model'] ?? null,
                                'status' => 'connected',
                                'last_seen_at' => now(),
                            ]
                        );
                    }
                    // Optional: tandai device lain yg tidak terlihat sebagai disconnected
                    Device::where('machine_id', $machine->id)
                    ->whereNotIn('serial', array_column($devices, 'serial'))
                    ->update(['status' => 'disconnected']);

                } else {
                    $machine->update([
                        'status' => 'offline',
                    ]);
                }

                $client->close();
            } catch (Exception $e) {
                Log::warning("⚠️ Machine {$machine->name} unreachable: {$e->getMessage()}");
                $machine->update([
                    'status' => 'offline',
                ]);
            }
        }
    }
}
