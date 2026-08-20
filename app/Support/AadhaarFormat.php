<?php

namespace App\Support;

final class AadhaarFormat
{
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        return substr($digits, 0, 12);
    }

    public static function format(?string $value, string $fallback = ''): string
    {
        $digits = static::normalize($value);
        if (! $digits) {
            return $fallback;
        }

        return trim(implode(' ', str_split($digits, 4)));
    }

    public static function hasValidLength(?string $value): bool
    {
        $digits = static::normalize($value);

        return $digits !== null && strlen($digits) === 12;
    }
}
