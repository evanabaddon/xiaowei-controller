<?php

namespace App\Models;

use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Model;

class GeneratedContent extends Model
{
    protected $fillable = [
        'social_account_id',
        'prompt',
        'response',
        'image_url',
        'status',
    ];

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }
}
