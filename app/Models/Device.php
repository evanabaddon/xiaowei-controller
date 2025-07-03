<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Services\WebSocketPool;

class Device extends Model
{
    protected $fillable = [
        'machine_id',
        'serial',
        'name',
        'model',
        'status',
        'last_seen_at',
        'ip_address',
        'ssid',
        'android_id', 
        'adb_serial',   
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function automationTasks()
    {
        return $this->hasMany(\App\Models\AutomationTask::class);
    }

    public function latestAutomationTask()
    {
        return $this->hasOne(\App\Models\AutomationTask::class)->latestOfMany();
    }


    public function getInstalledApps(): array
    {
        Log::info("📡 Requesting installed apps for {$this->serial}");

        $client = WebSocketPool::getClient($this->machine->ws_url);
        if (!$client) return [];

        try {
            $client->send(json_encode([
                'action' => 'apkList',
                'devices' => $this->serial,
            ]));

            $response = json_decode($client->receive(), true);

            return $response['code'] === 10000
                && isset($response['data'][$this->serial])
                ? $response['data'][$this->serial]
                : [];
        } catch (\Exception $e) {
            Log::warning("❌ Failed to get apps for {$this->serial}: {$e->getMessage()}");
            return [];
        }
    }

    public function getLastScreenshot(): ?string
    {
        $client = WebSocketPool::getClient($this->machine->ws_url);
        if (!$client) return null;

        try {
            $client->send(json_encode([
                'action' => 'screen',
                'devices' => $this->serial,
                'data' => ['savePath' => "D:\\Pictures"],
            ]));

            $response = json_decode($client->receive(), true);
            if ($response['code'] !== 10000) return null;

            $imagePath = "D:\\Pictures\\{$this->serial}.png";
            if (!file_exists($imagePath)) return null;

            return 'data:image/png;base64,' . base64_encode(file_get_contents($imagePath));
        } catch (\Exception $e) {
            Log::warning("⚠️ Screenshot error for {$this->serial}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Status koneksi internet perangkat.
     * Tidak akan melakukan koneksi WebSocket kecuali jika ada cache.
     */
    public function getConnectionStatus(): string
    {
        $cacheKey = "ping_status_{$this->serial}";

        if (cache()->has($cacheKey)) {
            return cache()->get($cacheKey);
        }

        return $this->ip_address ? 'Connected' : 'Disconnected';
    }

    /**
     * Paksa update status koneksi dan cache-nya via ping.
     */
    public function refreshConnectionStatus(bool $force = false): void
    {
        $cacheKey = "ping_status_{$this->serial}";
        cache()->forget($cacheKey);

        if ($force) {
            $status = $this->doPingCheck();
            cache()->put($cacheKey, $status, now()->addMinutes(2));
        }
    }

    /**
     * Ping Google DNS untuk cek koneksi internet.
     */
    protected function doPingCheck(): string
    {
        $client = WebSocketPool::getClient($this->machine->ws_url);
        if (!$client) return 'Error';

        try {
            $client->send(json_encode([
                'action' => 'adb',
                'devices' => $this->serial,
                'data' => ['command' => 'adb exec-out ping -c 1 8.8.8.8'],
            ]));

            $response = json_decode($client->receive(), true);
            $output = $response['data'][$this->serial] ?? '';

            return match (true) {
                str_contains($output, 'bytes from') => 'Connected',
                str_contains($output, 'unknown host'), str_contains($output, '100% packet loss') => 'No Internet',
                default => 'Disconnected',
            };
        } catch (\Exception $e) {
            Log::warning("❌ Ping error for {$this->serial}: {$e->getMessage()}");
            return 'Error';
        }
    }

    /**
     * Update IP address dan SSID dari device.
     */
    public function updateNetworkInfo(): void
    {
        $client = WebSocketPool::getClient($this->machine->ws_url);
        if (!$client) return;

        try {
            // Dapatkan IP
            $client->send(json_encode([
                'action' => 'adb',
                'devices' => $this->serial,
                'data' => ['command' => 'adb exec-out ip addr show wlan0'],
            ]));
            $ipRaw = json_decode($client->receive(), true)['data'][$this->serial] ?? '';
            preg_match('/inet\s+(\d+\.\d+\.\d+\.\d+)/', $ipRaw, $match);
            $ip = $match[1] ?? null;

            // Dapatkan SSID
            $client->send(json_encode([
                'action' => 'adb',
                'devices' => $this->serial,
                'data' => ['command' => 'adb shell dumpsys wifi | grep SSID'],
            ]));
            $ssidRaw = json_decode($client->receive(), true)['data'][$this->serial] ?? '';
            preg_match('/mWifiInfo:\s*\[SSID:\s*([^,\]]+)/', $ssidRaw, $ssidMatch);
            $ssid = $ssidMatch[1] ?? null;

            if (!$ssid || str_contains($ssid, '<unknown')) {
                preg_match('/ID:\s+\d+\s+SSID:\s+"([^"]+)"/', $ssidRaw, $fallback);
                $ssid = $fallback[1] ?? null;
            }

            $this->ip_address = $ip;
            $this->ssid = $ssid;
            $this->save();

            Log::info("✅ Updated {$this->serial} → IP: {$ip}, SSID: {$ssid}");
        } catch (\Exception $e) {
            Log::warning("❌ Failed to update network info for {$this->serial}: {$e->getMessage()}");
        }
    }
}
