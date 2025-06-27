<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $fillable = [
        'name',
        'code',
        'icon',
    ];

    public function accounts()
    {
        return $this->hasMany(SocialAccount::class);
    }
}
