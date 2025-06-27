<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'ws_mode',
        'custom_ws_url',
        'status',
        'last_checked_at',
        'notes',
    ];

    protected $appends = ['ws_url'];

    public function getWsUrlAttribute(): string
    {
        return $this->ws_mode === 'custom'
            ? $this->custom_ws_url
            : "ws://{$this->ip_address}:{$this->port}/";
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }
}
