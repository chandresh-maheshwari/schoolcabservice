@php
    $defaultInputId = $documentType === 'license' ? 'license_image' : 'adher_card_iamge';
    $defaultFields = $documentType === 'license'
        ? ['driver_name' => 'Driver Name', 'license_no' => 'Licence Number', 'license_expiry_date' => 'Expiry Date']
        : ['driver_name' => 'Driver Name', 'adher_no' => 'Aadhaar Number', 'current_address' => 'Current Address'];
    $inputId = $inputId ?? $defaultInputId;
    $extraInputIds = $extraInputIds ?? [];
    $fieldMap = $fieldMap ?? $defaultFields;
    $helpText = $helpText ?? ('Select a clear image or PDF to read ' . implode(', ', array_values($fieldMap)) . '. Check the details before saving.');
@endphp
<div class="driver-document-ocr mb-3" data-document-type="{{ $documentType }}"
    data-input-id="{{ $inputId }}"
    data-extra-input-ids="{{ implode(',', (array) $extraInputIds) }}"
    data-field-map='@json($fieldMap)'
    data-vendor-base="{{ asset('vendor/license-ocr') }}/">
    <p class="small text-muted mb-1">{{ $helpText }}</p>
    <div class="small" role="status" aria-live="polite" data-ocr-status></div>
    <div class="mt-2" data-ocr-results></div>
    <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-ocr-retry hidden>Read file again</button>
    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" data-ocr-cancel hidden>Cancel reading</button>
</div>
