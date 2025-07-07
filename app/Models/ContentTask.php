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
        'last_generated_at',
    ];

    protected $casts = [
        'last_generated_at' => 'datetime',
        'active' => 'boolean',
        'social_account_ids' => 'array',
    ];

    public function socialAccounts()
    {
        return SocialAccount::whereIn('id', $this->social_account_ids ?? [])->get();
    }

    public function automationTask()
    {
        return $this->belongsTo(AutomationTask::class);
    }

    public function generatedContents()
    {
        return $this->hasMany(GeneratedContent::class);
    }
}
