<?php

namespace App\Helpers;

use App\Models\School;
use Illuminate\Support\Facades\Auth;

class SchoolBranding
{
    public const DEFAULT_PRIMARY = '#2D336B';
    public const DEFAULT_SECONDARY = '#7886c7';
    public const DEFAULT_LOGO = 'images/for-schools.png';
    public const DEFAULT_LOGO_MINI = 'assets/images/cherrypik_logo.png';
    public const DEFAULT_FAVICON = 'assets/images/fav-icon/Tahukar Magazine logo vv [Recovered].png';

    public static function current(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $school = null;

        try {
            $user = Auth::user();
            if ($user && method_exists($user, 'isSchool') && $user->isSchool()) {
                $school = School::where('deleted', 0)->where('user_id', $user->id)->first();
            } else {
                $schoolSlug = request()->route('schoolSlug');
                if (is_string($schoolSlug) && trim($schoolSlug) !== '') {
                    $school = School::where('deleted', 0)->where('slug', trim($schoolSlug))->first();
                }
            }
        } catch (\Throwable $e) {
            $school = null;
        }

        return $cached = self::forSchool($school);
    }

    public static function forSchool(?School $school): array
    {
        $primary = self::sanitizeColor($school?->primary_color) ?? self::DEFAULT_PRIMARY;
        $secondary = self::sanitizeColor($school?->secondary_color) ?? self::DEFAULT_SECONDARY;

        $logoUrl = asset(self::DEFAULT_LOGO);
        $logoMiniUrl = asset(self::DEFAULT_LOGO_MINI);
        $faviconUrl = asset(self::DEFAULT_FAVICON);

        $logoPath = self::normalizeStoragePath($school?->logo_path);
        if ($logoPath) {
            $logoUrl = asset($logoPath);
        }

        $logoMiniPath = self::normalizeStoragePath($school?->logo_mini_path);
        if ($logoMiniPath) {
            $logoMiniUrl = asset($logoMiniPath);
        }

        $faviconPath = self::normalizeStoragePath($school?->favicon_path);
        if ($faviconPath) {
            $faviconUrl = asset($faviconPath);
        }

        $headerTitle = trim((string) ($school?->header_title ?? ''));

        return [
            'school_id' => $school?->id,
            'school_name' => $school?->school_name,
            'header_title' => $headerTitle !== '' ? $headerTitle : ($school?->school_name ?? null),
            'primary_color' => $primary,
            'secondary_color' => $secondary,
            'primary_rgb' => self::hexToRgb($primary),
            'secondary_rgb' => self::hexToRgb($secondary),
            'logo_url' => $logoUrl,
            'logo_mini_url' => $logoMiniUrl,
            'favicon_url' => $faviconUrl,
        ];
    }

    private static function sanitizeColor(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (! preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return null;
        }

        $hex = strtolower($value);
        if (strlen($hex) === 4) {
            $hex = '#' . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
        }

        return strtoupper($hex);
    }

    private static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '0,0,0';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return $r . ',' . $g . ',' . $b;
    }

    private static function normalizeStoragePath(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            return $path;
        }

        // Public disk serves files via /storage symlink.
        return 'storage/' . $path;
    }
}

