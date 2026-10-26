<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Schema;

trait TracksDocumentFieldSources
{
    public function clearDocumentFieldsFor(string $input): array
    {
        $defaults = match ($this->getTable()) {
            'drivers' => [
                'license_no' => 'license_image', 'license_expiry_date' => 'license_image',
                'driver_name' => 'adher_card_iamge', 'adher_no' => 'adher_card_iamge',
                'current_address' => 'adher_card_back_image',
            ],
            'parents' => [
                'father_name' => 'father_adhaar_card_image', 'father_aadhaar_number' => 'father_adhaar_card_image',
                'mother_name' => 'mother_adhaar_card_image', 'mother_aadhaar_number' => 'mother_adhaar_card_image',
                'address_1' => 'father_adhaar_card_back_image', 'address_2' => 'father_adhaar_card_back_image',
                'state' => 'father_adhaar_card_back_image', 'city' => 'father_adhaar_card_back_image',
                'pincode' => 'father_adhaar_card_back_image',
            ],
            'children' => [
                'child_name' => 'child_adhaar_card_image', 'gender' => 'child_adhaar_card_image',
                'date_of_birth' => 'child_adhaar_card_image', 'child_aadhaar_number' => 'child_adhaar_card_image',
                'home_address' => 'child_adhaar_card_back_image',
            ],
            'vehicles' => [
                'vehicle_number' => 'rc_image', 'rc_number' => 'rc_image', 'rc_expiry_date' => 'rc_image',
                'insurance_number' => 'insurance_image', 'insurance_expiry_date' => 'insurance_image',
            ],
            default => [],
        };
        $sources = $this->document_field_sources ?? $defaults;
        $request = request();
        $liveSources = $request->input('document_field_source_model') === $this->getTable()
            ? json_decode((string) $request->input('document_field_sources'), true) : null;
        if (is_array($liveSources)) $sources = $liveSources;
        $cleared = [];
        foreach ($sources as $field => $owner) {
            if (! is_string($owner)) continue;
            foreach (['child_adhaar_card_image', 'child_adhaar_card_back_image'] as $canonical) {
                if (str_ends_with($owner, '_'.$canonical)) $owner = $canonical;
            }
            if ($owner !== $input || ! isset($defaults[$field])) continue;
            $this->setAttribute($field, null);
            unset($sources[$field]);
            $cleared[] = $field;
        }
        $this->document_field_sources = $sources;
        // The saving hook must receive the remaining map, not the pre-delete map.
        if (is_array($liveSources)) $request->merge(['document_field_sources' => json_encode($sources)]);
        return $cleared;
    }

    public function initializeTracksDocumentFieldSources(): void
    {
        $this->mergeCasts(['document_field_sources' => 'array']);
    }

    public static function bootTracksDocumentFieldSources(): void
    {
        static::saving(function ($model) {
            $request = request();
            if ($request->input('document_field_source_model') !== $model->getTable()
                || ! $request->has('document_field_sources')
                || ! Schema::hasColumn($model->getTable(), 'document_field_sources')) {
                return;
            }
            $sources = json_decode((string) $request->input('document_field_sources'), true);
            if (! is_array($sources)) {
                return;
            }
            foreach ($sources as &$input) {
                if (! is_string($input)) continue;
                foreach (['child_adhaar_card_image', 'child_adhaar_card_back_image'] as $canonical) {
                    if (str_ends_with($input, '_'.$canonical)) $input = $canonical;
                }
            }
            unset($input);
            $allowedInputs = [
                'license_image', 'license_back_image', 'adher_card_iamge', 'adher_card_back_image',
                'father_adhaar_card_image', 'father_adhaar_card_back_image',
                'mother_adhaar_card_image', 'mother_adhaar_card_back_image',
                'child_adhaar_card_image', 'child_adhaar_card_back_image',
                'rc_image', 'rc_back_image', 'insurance_image',
            ];
            $model->document_field_sources = array_filter($sources,
                fn ($input, $field) => in_array($field, $model->getFillable(), true)
                    && is_string($input) && in_array($input, $allowedInputs, true), ARRAY_FILTER_USE_BOTH);
        });
    }
}
