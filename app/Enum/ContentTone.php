<?php

namespace App\Enum;

enum ContentTone: string
{
    case CASUAL = 'casual';
    case SARCASTIC = 'sarcastic';
    case FORMAL = 'formal';
    case HUMOROUS = 'humorous';
    case PROVOCATIVE = 'provocative';
    case NEUTRAL = 'neutral';

    public function label(): string
    {
        return match($this) {
            self::CASUAL => 'Santai',
            self::SARCASTIC => 'Sarkastik',
            self::FORMAL => 'Formal',
            self::HUMOROUS => 'Humor',
            self::PROVOCATIVE => 'Provokatif',
            self::NEUTRAL => 'Netral'
        };
    }

    // Method yang benar untuk Filament
    public static function getOptions(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
