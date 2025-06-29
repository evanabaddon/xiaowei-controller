<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'password',
        'cookie',
        'platform_id',
        'account_category_id',
        'device_id',
        'status',
        'notes',
    ];

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function accountCategory()
    {
        return $this->belongsTo(AccountCategory::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
    
    public function persona(): HasOne
    {
        return $this->hasOne(AccountPersona::class);
    }

    public function contentTask()
    {
        return $this->hasOne(ContentTask::class);
    }
    
    public function generatedContents()
    {
        return $this->hasMany(GeneratedContent::class);
    }

    
}
