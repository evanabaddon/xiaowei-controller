<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    // Relasi ke akun jika nanti dibuat
    public function accounts()
    {
        return $this->hasMany(SocialAccount::class);
    }
}
