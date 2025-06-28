<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
}
