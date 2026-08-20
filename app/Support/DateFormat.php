<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;

class DateFormat
{
    public const DISPLAY_DATE = 'd/m/Y';
    public const DISPLAY_DATETIME = 'd/m/Y h:i A';
    public const STORAGE_DATE = 'Y-m-d';
    public const STORAGE_DATETIME = 'Y-m-d H:i:s';

    public static function formatDate($value, string $fallback = '-'): string
    {
        $carbon = static::parseValue($value);

        return $carbon ? $carbon->format(static::DISPLAY_DATE) : $fallback;
    }

    public static function formatDateTime($value, string $fallback = '-'): string
    {
        $carbon = static::parseValue($value);

        return $carbon ? $carbon->format(static::DISPLAY_DATETIME) : $fallback;
    }

    public static function toStorageDate(?string $value): ?string
    {
        $carbon = static::parseUserDate($value);

        return $carbon ? $carbon->format(static::STORAGE_DATE) : null;
    }

    public static function toStorageDateTime(?string $value): ?string
    {
        $carbon = static::parseUserDateTime($value);

        return $carbon ? $carbon->format(static::STORAGE_DATETIME) : null;
    }

    public static function parseUserDate(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d/m/y', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable $e) {
            }
        }

        return null;
    }

    public static function parseUserDateTime(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d/m/y h:i A', 'd/m/Y h:i A', 'd/m/y h:i a', 'd/m/Y h:i a', 'd/m/y H:i', 'd/m/Y H:i', 'Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable $e) {
            }
        }

        return null;
    }

    public static function parseValue($value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if ($value instanceof Collection) {
            return null;
        }

        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return null;
        }

        foreach ([
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd/m/y h:i A',
            'd/m/Y h:i A',
            'd/m/y h:i a',
            'd/m/Y h:i a',
            'd/m/y H:i',
            'd/m/Y H:i',
            'd/m/y',
            'd/m/Y',
        ] as $format) {
            try {
                return Carbon::createFromFormat($format, $stringValue);
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($stringValue);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
