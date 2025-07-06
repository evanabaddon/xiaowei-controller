<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationTask extends Model
{
    protected $fillable = ['name','device_id', 'apply_to_all', 'mode', 'steps',  'platform_id', ];

    protected $casts = [
        'steps' => 'array',
        'apply_to_all' => 'boolean',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }


    public function getStepsAttribute($value)
    {
        $steps = json_decode($value, true);

        return collect($steps)->map(function ($step) {
            if (!is_array($step) || !array_key_exists('action', $step)) {
                return $step;
            }

            $action = $step['action'];

            return match (true) {
                in_array($action, ['toast', 'clickText', 'input']) => [
                    'action' => $action,
                    'text' => $step['text'] ?? '',
                ],
                in_array($action, ['scrollUp', 'scrollDown']) => [
                    'action' => $action,
                    'i' => isset($step['i']) ? (int)$step['i'] : null,
                ],
                $action === 'launchApp' => [
                    'action' => $action,
                    'app' => $step['app'] ?? '',
                ],
                $action === 'sleep' => [
                    'action' => $action,
                    'ms' => $step['ms'] ?? 1000,
                ],
                $action === 'click' => [
                    'action' => 'tap',
                    'x' => $step['x'] ?? 0,
                    'y' => $step['y'] ?? 0,
                ],
                $action === 'swipe' => [
                    'action' => $action,
                    'x1' => $step['x1'] ?? 0,
                    'y1' => $step['y1'] ?? 0,
                    'x2' => $step['x2'] ?? 0,
                    'y2' => $step['y2'] ?? 0,
                    'duration' => $step['duration'] ?? 500,
                ],
                // Fungsi tombol AutoX (tanpa parameter)
                preg_match('/^(\w+)\(\)$/', $action, $m) => [
                    'action' => 'key',
                    'key_command' => $m[1],
                ],
                default => $step,
            };
        })->values()->all();
    }


    public function setStepsAttribute($value)
    {
        $cleaned = collect($value)
            ->filter(fn ($step) => is_array($step) && !empty($step) && isset($step['action']))
            ->map(function ($step) {
                $action = $step['action'];

                // Handle fungsi tombol AutoX (key)
                if ($action === 'key') {
                    $keyCommand = $step['key_command'] ?? null;
                    $param = trim($step['key_param'] ?? '');

                    if (!$keyCommand) {
                        return null;
                    }

                    // Fungsi tanpa parameter
                    if ($keyCommand !== 'Text') {
                        return ['action' => $keyCommand];
                    }

                    return null;
                }

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
                        'action' => 'click',
                        'x' => (int) ($step['x'] ?? 0),
                        'y' => (int) ($step['y'] ?? 0),
                    ],
                    'swipe' => [
                        'action' => 'swipe',
                        'x1' => (int) ($step['x1'] ?? 0),
                        'y1' => (int) ($step['y1'] ?? 0),
                        'x2' => (int) ($step['x2'] ?? 0),
                        'y2' => (int) ($step['y2'] ?? 0),
                        'duration' => (int) ($step['duration'] ?? 500),
                    ],
                    'scrollUp', 'scrollDown' => [
                        'action' => $action,
                        ...(isset($step['i']) && $step['i'] !== null && $step['i'] !== '' ? ['i' => (int)$step['i']] : []),
                    ],
                    default => $step,
                };
            })
            ->filter()
            ->values()
            ->all();

        $this->attributes['steps'] = json_encode($cleaned);
    }

}
