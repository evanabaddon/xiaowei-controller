<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContentTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_ids',
        'mode',
        'daily_quota',
        'active',
        'prompt',
        'response',
        'image_url',
        'status',
        'automation_task_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'social_account_ids' => 'array',
    ];

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function automationTask()
    {
        return $this->belongsTo(AutomationTask::class);
    }
}
