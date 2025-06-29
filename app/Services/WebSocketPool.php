<?php

namespace App\Services;

use WebSocket\Client;
use Illuminate\Support\Facades\Log;

class WebSocketPool
{
    protected static array $clients = [];

    public static function getClient(string $url): ?Client
    {
        if (!isset(self::$clients[$url])) {
            try {
                self::$clients[$url] = new Client($url, ['timeout' => 5]);
            } catch (\Exception $e) {
                Log::warning("⚠️ Failed to connect WebSocket to $url: " . $e->getMessage());
                return null;
            }
        }

        return self::$clients[$url];
    }

    public static function closeAll(): void
    {
        foreach (self::$clients as $client) {
            try {
                $client->close();
            } catch (\Exception $e) {
                Log::warning("⚠️ Error closing WebSocket: " . $e->getMessage());
            }
        }

        self::$clients = [];
    }
}
