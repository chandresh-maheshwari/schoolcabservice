<?php

namespace App\Helpers;

class IdEncoder
{

    public static function encode($id)
    {
        $salt = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        $data = $id . '-' . $salt;
        $encoded = base64_encode($data);

        $encoded = rtrim(strtr($encoded, '+/', '-_'), '=');

        return str_pad(substr($encoded, 0, 12), 12, 'x');
    }

    public static function decode($encoded)
    {
        $encoded = rtrim($encoded, 'x');
        $decoded = base64_decode(strtr($encoded, '-_', '+/'));
        if (!$decoded) return null;
        $parts = explode('-', $decoded);
        return $parts[0] ?? null;
    }
}
