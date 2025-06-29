<?php

namespace App\Enum;

enum PoliticalLeaning: string
{
    case PRO_GOVERNMENT = 'pro_government';
    case OPPOSITION = 'opposition';
    case NEUTRAL = 'neutral';
    case APOLITICAL = 'apolitical';

    // Tambahkan method label()
    public function label(): string
    {
        return match($this) {
            self::PRO_GOVERNMENT => 'Pro Government',
            self::OPPOSITION => 'Opposition',
            self::NEUTRAL => 'Neutral',
            self::APOLITICAL => 'Apolitical'
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

    public static function asSelectArray(): array
    {
        return array_reduce(
            self::cases(),
            fn ($carry, $case) => $carry + [$case->value => $case->label()],
            []
        );
    }
}
