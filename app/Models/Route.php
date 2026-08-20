<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $table = 'routes';

    protected $fillable = [
        'user_id',
        'school_id',
        'name',
        'state',
        'city',
        'bus_id',
        'driver_id',
        'route_json',
        'status',
        'deleted',
        'created_at',
    ];

    protected $casts = [
        'route_json' => 'array',
    ];

    public $timestamps = false;

    public function getRouteJsonAttribute($value): array
    {
        $decoded = is_array($value) ? $value : json_decode((string) $value, true);
        if (is_array($decoded)) {
            return $this->normalizeRouteJson($decoded);
        }

        $legacyGeoJson = array_key_exists('geojson', $this->attributes)
            ? json_decode((string) $this->attributes['geojson'], true)
            : null;
        $legacyStops = array_key_exists('stops', $this->attributes)
            ? json_decode((string) $this->attributes['stops'], true)
            : [];

        return $this->normalizeRouteJson([
            'geojson' => $legacyGeoJson,
            'stops' => $legacyStops,
        ]);
    }

    public function setRouteJsonAttribute($value): void
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        $this->attributes['route_json'] = json_encode($this->normalizeRouteJson($decoded));
    }

    public function getGeojsonAttribute()
    {
        return $this->route_json['geojson'] ?? null;
    }

    public function getStopsAttribute(): array
    {
        $routeJson = $this->route_json;
        $pickupPoints = $routeJson['pickup_points'] ?? $routeJson['stops'] ?? [];

        return is_array($pickupPoints) ? $pickupPoints : [];
    }

    public function getStartPointAttribute(): ?array
    {
        $startPoint = $this->route_json['start_point'] ?? null;
        return is_array($startPoint) ? $startPoint : null;
    }

    public function getEndPointAttribute(): ?array
    {
        $endPoint = $this->route_json['end_point'] ?? null;
        return is_array($endPoint) ? $endPoint : null;
    }

    public function getPickupPointsAttribute(): array
    {
        return $this->getStopsAttribute();
    }

    private function normalizeRouteJson($payload): array
    {
        $payload = is_array($payload) ? $payload : [];

        $startPoint = $this->normalizeLocationPoint($payload['start_point'] ?? null, 'start', 1);
        $endPoint = $this->normalizeLocationPoint($payload['end_point'] ?? null, 'end');

        $pickupPoints = $this->normalizePointList($payload['pickup_points'] ?? [], 'pickup');
        $legacyStops = $this->normalizePointList($payload['stops'] ?? [], 'pickup');

        if (! $startPoint && ! $endPoint && empty($pickupPoints) && ! empty($legacyStops)) {
            $legacyOrderedPoints = array_values($legacyStops);
            $startPoint = $this->normalizeLocationPoint(array_shift($legacyOrderedPoints), 'start', 1);

            if (! empty($legacyOrderedPoints)) {
                $endPoint = $this->normalizeLocationPoint(
                    array_pop($legacyOrderedPoints),
                    'end',
                    count($legacyOrderedPoints) + 2
                );
                $pickupPoints = $this->normalizePointList($legacyOrderedPoints, 'pickup');
            }
        }

        $pickupPoints = array_values(array_map(function ($point, $index) {
            $normalizedPoint = $this->normalizeLocationPoint($point, 'pickup', $index + 2);
            return $normalizedPoint ?? $point;
        }, $pickupPoints, array_keys($pickupPoints)));

        if ($endPoint) {
            $endPoint['sequence'] = count($pickupPoints) + 2;
        }

        $orderedPoints = [];
        if ($startPoint) {
            $orderedPoints[] = $startPoint;
        }
        foreach ($pickupPoints as $pickupPoint) {
            if (is_array($pickupPoint)) {
                $orderedPoints[] = $pickupPoint;
            }
        }
        if ($endPoint) {
            $orderedPoints[] = $endPoint;
        }

        $geojson = $payload['geojson'] ?? null;
        if (! is_array($geojson) && isset($payload['type'], $payload['coordinates'])) {
            $geojson = [
                'type' => $payload['type'],
                'coordinates' => $payload['coordinates'],
            ];
        }

        $normalizedGeojson = $this->normalizeGeojson($geojson, $orderedPoints);

        return [
            'start_point' => $startPoint,
            'pickup_points' => array_values(array_filter($pickupPoints, 'is_array')),
            'end_point' => $endPoint,
            'geojson' => $normalizedGeojson,
            'route_summary' => $this->normalizeRouteSummary($payload['route_summary'] ?? null),
            'route_alternatives' => $this->normalizeRouteAlternatives($payload['route_alternatives'] ?? []),
            'route_legs' => $this->normalizeRouteLegs($payload['route_legs'] ?? []),
            'stops' => array_values(array_filter($orderedPoints, 'is_array')),
        ];
    }

    private function normalizeRouteSummary($summary): ?array
    {
        if (! is_array($summary)) {
            return null;
        }

        return [
            'distance_meters' => is_numeric($summary['distance_meters'] ?? null) ? (float) $summary['distance_meters'] : null,
            'distance_text' => isset($summary['distance_text']) ? trim((string) $summary['distance_text']) : null,
            'duration_seconds' => is_numeric($summary['duration_seconds'] ?? null) ? (float) $summary['duration_seconds'] : null,
            'duration_text' => isset($summary['duration_text']) ? trim((string) $summary['duration_text']) : null,
            'summary' => isset($summary['summary']) ? trim((string) $summary['summary']) : null,
            'selected_route_index' => is_numeric($summary['selected_route_index'] ?? null)
                ? (int) $summary['selected_route_index']
                : null,
        ];
    }

    private function normalizeRouteAlternatives($alternatives): array
    {
        if (! is_array($alternatives)) {
            return [];
        }

        $normalized = [];
        foreach (array_values($alternatives) as $alternative) {
            if (! is_array($alternative)) {
                continue;
            }

            $normalized[] = [
                'index' => is_numeric($alternative['index'] ?? null) ? (int) $alternative['index'] : null,
                'distance_meters' => is_numeric($alternative['distance_meters'] ?? null) ? (float) $alternative['distance_meters'] : null,
                'distance_text' => isset($alternative['distance_text']) ? trim((string) $alternative['distance_text']) : null,
                'duration_seconds' => is_numeric($alternative['duration_seconds'] ?? null) ? (float) $alternative['duration_seconds'] : null,
                'duration_text' => isset($alternative['duration_text']) ? trim((string) $alternative['duration_text']) : null,
                'summary' => isset($alternative['summary']) ? trim((string) $alternative['summary']) : null,
            ];
        }

        return $normalized;
    }

    private function normalizeRouteLegs($legs): array
    {
        if (! is_array($legs)) {
            return [];
        }

        $normalized = [];
        foreach (array_values($legs) as $leg) {
            if (! is_array($leg)) {
                continue;
            }

            $normalized[] = [
                'index' => is_numeric($leg['index'] ?? null) ? (int) $leg['index'] : null,
                'from_sequence' => is_numeric($leg['from_sequence'] ?? null) ? (int) $leg['from_sequence'] : null,
                'to_sequence' => is_numeric($leg['to_sequence'] ?? null) ? (int) $leg['to_sequence'] : null,
                'distance_meters' => is_numeric($leg['distance_meters'] ?? null) ? (float) $leg['distance_meters'] : null,
                'distance_text' => isset($leg['distance_text']) ? trim((string) $leg['distance_text']) : null,
                'duration_seconds' => is_numeric($leg['duration_seconds'] ?? null) ? (float) $leg['duration_seconds'] : null,
                'duration_text' => isset($leg['duration_text']) ? trim((string) $leg['duration_text']) : null,
                'summary' => isset($leg['summary']) ? trim((string) $leg['summary']) : null,
            ];
        }

        return $normalized;
    }

    private function normalizePointList($points, string $defaultType): array
    {
        if (! is_array($points)) {
            return [];
        }

        $normalized = [];
        foreach (array_values($points) as $index => $point) {
            $normalizedPoint = $this->normalizeLocationPoint($point, $defaultType, $index + 1);
            if ($normalizedPoint) {
                $normalized[] = $normalizedPoint;
            }
        }

        return $normalized;
    }

    private function normalizeLocationPoint($point, string $defaultType, ?int $sequence = null): ?array
    {
        if (! is_array($point)) {
            return null;
        }

        $lat = $point['lat'] ?? $point['latitude'] ?? null;
        $lng = $point['lng'] ?? $point['lon'] ?? $point['longitude'] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $defaultName = ucfirst($defaultType) . ' Point';
        $name = trim((string) ($point['name'] ?? $point['title'] ?? $point['address'] ?? $point['display_name'] ?? $defaultName));
        $address = trim((string) ($point['address'] ?? $point['display_name'] ?? $name));
        $resolvedSequence = is_numeric($point['sequence'] ?? null)
            ? (int) $point['sequence']
            : $sequence;

        return [
            'name' => $name !== '' ? $name : $defaultName,
            'address' => $address !== '' ? $address : ($name !== '' ? $name : $defaultName),
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'type' => trim((string) ($point['type'] ?? $defaultType)) ?: $defaultType,
            'sequence' => $resolvedSequence,
        ];
    }

    private function normalizeGeojson($geojson, array $orderedPoints): ?array
    {
        if (is_array($geojson) && ($geojson['type'] ?? null) === 'LineString' && is_array($geojson['coordinates'] ?? null)) {
            $coordinates = [];
            foreach ($geojson['coordinates'] as $coordinate) {
                if (
                    is_array($coordinate)
                    && count($coordinate) >= 2
                    && is_numeric($coordinate[0] ?? null)
                    && is_numeric($coordinate[1] ?? null)
                ) {
                    $coordinates[] = [(float) $coordinate[0], (float) $coordinate[1]];
                }
            }

            if (count($coordinates) >= 2) {
                return [
                    'type' => 'LineString',
                    'coordinates' => $coordinates,
                ];
            }
        }

        $coordinates = [];
        foreach ($orderedPoints as $point) {
            if (is_array($point) && is_numeric($point['lng'] ?? null) && is_numeric($point['lat'] ?? null)) {
                $coordinates[] = [(float) $point['lng'], (float) $point['lat']];
            }
        }

        if (count($coordinates) < 2) {
            return null;
        }

        return [
            'type' => 'LineString',
            'coordinates' => $coordinates,
        ];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'bus_id', 'id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id', 'id');
    }
}
