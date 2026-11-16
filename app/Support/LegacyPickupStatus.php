<?php

namespace App\Support;

use App\Models\StopPickup;

class LegacyPickupStatus
{
    public static function isActive($routeId, $pickupPoints): bool
    {
        $names = collect($pickupPoints)->pluck('name')->filter()->unique()->values();
        if ($names->count() < 2) {
            return false;
        }

        $normalize = static function ($name): string {
            $parts = array_map(static fn ($part) => mb_strtolower(
                preg_replace('/\s+/u', ' ', trim($part)) ?? ''
            ), explode(',', (string) $name));
            sort($parts, SORT_STRING);
            return implode(',', $parts);
        };
        $aggregate = $normalize($names->implode(', '));

        return StopPickup::where('route_id', $routeId)
            ->where('status', 1)
            ->where(fn ($query) => $query->where('deleted', 0)->orWhereNull('deleted'))
            ->get(['pickup_name'])
            ->contains(fn ($point) => !$names->contains($point->pickup_name)
                && $normalize($point->pickup_name) === $aggregate);
    }
}
