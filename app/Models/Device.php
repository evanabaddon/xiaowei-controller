<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use WebSocket\Client;

class Device extends Model
{
    protected $fillable = [
        'machine_id',
        'serial',
        'name',
        'model',
        'status',
        'last_seen_at',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function getInstalledApps(): array
    {
        \Log::info("📡 Connecting to {$this->machine->ws_url} for device {$this->serial}");

        try {
            $client = new Client($this->machine->ws_url, ['timeout' => 5]);

            $client->send(json_encode([
                'action' => 'apkList',
                'devices' => $this->serial,
            ]));

            $responseRaw = $client->receive();
            \Log::info("📦 Response: $responseRaw");

            $response = json_decode($responseRaw, true);
            $client->close();

            if (
                $response['code'] === 10000 &&
                isset($response['data'][$this->serial]) &&
                is_array($response['data'][$this->serial])
            ) {
                return $response['data'][$this->serial];
            }

            \Log::warning("⚠️ Unexpected response structure: " . json_encode($response));
            return [];
        } catch (\Exception $e) {
            \Log::warning("❌ Failed to get apps for {$this->serial}: {$e->getMessage()}");
            return [];
        }
    }

    public function getLastScreenshot(): ?string
    {
        try {
            $client = new Client($this->machine->ws_url, ['timeout' => 5]);

            $client->send(json_encode([
                'action' => 'screen',
                'devices' => $this->serial,
                'data' => [
                    'savePath' => "D:\\Pictures",
                ],
            ]));

            $response = json_decode($client->receive(), true);
            $client->close();

            if ($response['code'] !== 10000) {
                \Log::warning("❌ Screenshot request failed for device {$this->serial}");
                return null;
            }

            // Path to expected image
            $imagePath = "D:\\Pictures\\{$this->serial}.png";
            if (!file_exists($imagePath)) {
                \Log::warning("⚠️ Screenshot file not found at {$imagePath}");
                return null;
            }

            // Encode to base64 to display in browser
            $imageData = file_get_contents($imagePath);
            return 'data:image/png;base64,' . base64_encode($imageData);
        } catch (\Exception $e) {
            \Log::warning("⚠️ Failed to get screenshot for {$this->serial}: {$e->getMessage()}");
            return null;
        }
    }

    public function getConnectionStatus(): string
    {
        return cache()->remember("ping_status_{$this->serial}", now()->addSeconds(30), function () {
            try {
                \Log::info("🌐 Pinging internet from {$this->serial}");

                $client = new \WebSocket\Client($this->machine->ws_url, ['timeout' => 5]);
                $client->send(json_encode([
                    'action' => 'adb',
                    'devices' => $this->serial,
                    'data' => [
                        'command' => 'adb exec-out ping -c 1 8.8.8.8',
                    ]
                ]));

                $response = json_decode($client->receive(), true);
                $client->close();

                $result = $response['data'][$this->serial] ?? '';

                if (str_contains($result, 'bytes from')) {
                    return 'Connected';
                } elseif (str_contains($result, 'unknown host') || str_contains($result, '100% packet loss')) {
                    return 'No Internet';
                }

                return 'Disconnected';
            } catch (\Exception $e) {
                \Log::warning("❌ Ping failed for {$this->serial}: {$e->getMessage()}");
                return 'Error';
            }
        });
    }

    public function refreshConnectionStatus(bool $force = false): void
    {
        cache()->forget("ping_status_{$this->serial}");
        $this->getConnectionStatus(); // Akan cache ulang
    }

    public function updateNetworkInfo(): void
    {
        try {
            $client = new Client($this->machine->ws_url, ['timeout' => 5]);

            // 1. Get IP Address
            $client->send(json_encode([
                'action' => 'adb',
                'devices' => $this->serial,
                'data' => [
                    'command' => 'adb exec-out ip addr show wlan0',
                ],
            ]));

            $ipResponse = json_decode($client->receive(), true);
            $ipOutput = $ipResponse['data'][$this->serial] ?? '';
            preg_match('/inet\s+(\d+\.\d+\.\d+\.\d+)/', $ipOutput, $match);
            $ip = $match[1] ?? null;

            // 2. Get Wi-Fi SSID (current or stored)
            $client->send(json_encode([
                'action' => 'adb',
                'devices' => $this->serial,
                'data' => [
                    'command' => 'adb shell dumpsys wifi | grep SSID',
                ],
            ]));

            $wifiResponse = json_decode($client->receive(), true);
            $wifiOutput = $wifiResponse['data'][$this->serial] ?? '';

            // Cari SSID yang sedang dipakai (mWifiInfo SSID)
            // Ambil SSID dari mWifiInfo
            preg_match('/mWifiInfo:\s*\[SSID:\s*([^,\]]+)/', $wifiOutput, $ssidMatch);
            $ssid = $ssidMatch[1] ?? null;

            // Fallback kalau SSID unknown atau kosong
            if (!$ssid || str_contains($ssid, '<unknown') || $ssid === '') {
                preg_match('/ID:\s+\d+\s+SSID:\s+"([^"]+)"/', $wifiOutput, $fallbackMatch);
                $ssid = $fallbackMatch[1] ?? null;
            }

            $this->ip_address = $ip;
            $this->ssid = $ssid;
            $this->save();

            \Log::info("✅ Updated device {$this->serial}: IP = {$ip}, SSID = {$ssid}");

            $client->close();
        } catch (\Exception $e) {
            \Log::warning("❌ Failed to update network info for {$this->serial}: {$e->getMessage()}");
        }
    }



}
