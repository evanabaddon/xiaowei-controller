<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationTask extends Model
{
    protected $fillable = ['device_id', 'apply_to_all', 'mode', 'steps'];

    protected $casts = [
        'steps' => 'array',
        'apply_to_all' => 'boolean',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function getStepsAttribute($value)
    {
        $steps = json_decode($value, true);

        return collect($steps)->map(function ($step) {
            $action = $step['action'];

            return match ($action) {
                'toast', 'clickText', 'input' => [
                    'action' => $action,
                    'text' => $step['text'] ?? '',
                ],
                'launchApp' => [
                    'action' => $action,
                    'app' => $step['app'] ?? '',
                ],
                'sleep' => [
                    'action' => $action,
                    'ms' => (int) ($step['ms'] ?? 1000),
                ],
                'tap' => [
                    'action' => $action,
                    'x' => (int) ($step['x'] ?? 0),
                    'y' => (int) ($step['y'] ?? 0),
                ],
                'swipe' => [
                    'action' => $action,
                    'x1' => (int) ($step['x1'] ?? 0),
                    'y1' => (int) ($step['y1'] ?? 0),
                    'x2' => (int) ($step['x2'] ?? 0),
                    'y2' => (int) ($step['y2'] ?? 0),
                    'duration' => (int) ($step['duration'] ?? 500),
                ],
                default => $step,
            };
        })->values()->all();
    }

    public function setStepsAttribute($value)
    {
        $cleaned = collect($value)->map(function ($step) {
            $action = $step['action'];

            return match ($action) {
                'toast', 'clickText', 'input' => [
                    'action' => $action,
                    'text' => $step['text'] ?? '',
                ],
                'launchApp' => [
                    'action' => $action,
                    'app' => $step['app'] ?? '',
                ],
                'sleep' => [
                    'action' => $action,
                    'ms' => (int) ($step['ms'] ?? 1000),
                ],
                'tap' => [
                    'action' => $action,
                    'x' => (int) ($step['x'] ?? 0),
                    'y' => (int) ($step['y'] ?? 0),
                ],
                'swipe' => [
                    'action' => $action,
                    'x1' => (int) ($step['x1'] ?? 0),
                    'y1' => (int) ($step['y1'] ?? 0),
                    'x2' => (int) ($step['x2'] ?? 0),
                    'y2' => (int) ($step['y2'] ?? 0),
                    'duration' => (int) ($step['duration'] ?? 500),
                ],
                default => $step,
            };
        })->values()->all();

        $this->attributes['steps'] = json_encode($cleaned);
    }

}
