<?php

namespace App\Enum;

enum AgeRange: string
{
    case AGE_18_25 = '18-25';
    case AGE_26_35 = '26-35';
    case AGE_36_45 = '36-45';
    case AGE_45_UP = '45+';

    public function label(): string
    {
        return match($this) {
            self::AGE_18_25 => '18-25 Tahun',
            self::AGE_26_35 => '26-35 Tahun',
            self::AGE_36_45 => '36-45 Tahun',
            self::AGE_45_UP => '45+ Tahun'
        };
    }

    public static function asSelectArray(): array
    {
        return array_reduce(
            self::cases(),
            fn ($carry, $case) => $carry + [$case->value => $case->label()],
            []
        );
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
