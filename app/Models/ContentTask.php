<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContentTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_id',
        'mode',
        'daily_quota',
        'active',
        'prompt',
        'response',
        'image_url',
        'status',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }
}
