<?php

namespace App\Models;

use App\Enum\ContentTone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountPersona extends Model
{
    protected $fillable = [
        'age_range',
        'political_leaning',
        'interests',
        'content_tone',
        'persona_description'
    ];

    protected $casts = [
        'interests' => 'array'
    ];

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function getInterestsAttribute($value)
    {
        if (empty($value)) return [];
        
        // Handle format salah ""kopi,teknologi""
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = trim($value, '"');
        }
        
        return is_array($value) ? $value : explode(',', $value);
    }
}
