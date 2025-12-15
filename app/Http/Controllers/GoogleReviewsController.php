<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GoogleReviewsController extends Controller
{
    /**
     * GET /api/frontend/google-reviews
     * Response: {
     *   success: bool,
     *   data: { rating: float|null, user_ratings_total: int|null, reviews: array }
     * }
     */
    public function index(Request $request)
    {
        $placeId = env('GOOGLE_PLACES_PLACE_ID');
        $apiKey = env('GOOGLE_PLACES_API_KEY');

        if (!$placeId || !$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Google Places is not configured',
                'data' => [
                    'rating' => null,
                    'user_ratings_total' => null,
                    'reviews' => [],
                ],
            ], 200);
        }

        $cacheKey = 'google_reviews_' . md5($placeId);
        $ttlSeconds = 60 * 60 * 6; // 6 hours

        $payload = Cache::remember($cacheKey, $ttlSeconds, function () use ($placeId, $apiKey) {
            $fields = 'rating,user_ratings_total,reviews';
            $url = 'https://maps.googleapis.com/maps/api/place/details/json';

            $response = Http::timeout(8)->get($url, [
                'place_id' => $placeId,
                'fields' => $fields,
                'key' => $apiKey,
            ]);

            if (!$response->ok()) {
                return [
                    'rating' => null,
                    'user_ratings_total' => null,
                    'reviews' => [],
                ];
            }

            $json = $response->json();
            $result = is_array($json) ? ($json['result'] ?? []) : [];

            $rating = isset($result['rating']) ? (float) $result['rating'] : null;
            $total = isset($result['user_ratings_total']) ? (int) $result['user_ratings_total'] : null;
            $reviews = isset($result['reviews']) && is_array($result['reviews']) ? $result['reviews'] : [];

            // Normalize minimal review fields
            $normalized = array_map(function ($r) {
                return [
                    'author_name' => $r['author_name'] ?? null,
                    'profile_photo_url' => $r['profile_photo_url'] ?? null,
                    'rating' => isset($r['rating']) ? (int) $r['rating'] : null,
                    'text' => $r['text'] ?? null,
                    'relative_time_description' => $r['relative_time_description'] ?? null,
                ];
            }, array_slice($reviews, 0, 5));

            return [
                'rating' => $rating,
                'user_ratings_total' => $total,
                'reviews' => $normalized,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }
}


