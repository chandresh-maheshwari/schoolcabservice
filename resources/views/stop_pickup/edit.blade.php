@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

    @php
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $schoolSlug = request()->route('schoolSlug');
        $isSchoolPanel = filled($schoolSlug) && is_string($routeName) && str_starts_with($routeName, 'school.');
        $routePointsUrlTemplate = $isSchoolPanel
            ? route('school.stopPickup.route-points', ['schoolSlug' => $schoolSlug, 'routeId' => '__ROUTE_ID__'])
            : route('stopPickup.route-points', ['routeId' => '__ROUTE_ID__']);
        $routePointOptions = $routeData
            ->mapWithKeys(function ($route) {
                $routeJson = is_array($route->route_json ?? null) ? $route->route_json : [];
                $points = collect();

                $appendPoint = function ($point, string $fallbackType) use ($points) {
                    if (!is_array($point)) {
                        return;
                    }

                    $name = trim((string) ($point['name'] ?? $point['address'] ?? ''));
                    $latitude = $point['lat'] ?? $point['latitude'] ?? null;
                    $longitude = $point['lng'] ?? $point['lon'] ?? $point['longitude'] ?? null;

                    if ($name === '' || !is_numeric($latitude) || !is_numeric($longitude)) {
                        return;
                    }

                    $type = strtolower(trim((string) $fallbackType)) ?: 'pickup';
                    $points->push([
                        'name' => $name,
                        'type' => $type,
                        'label' => ucfirst($type) . ' - ' . $name,
                        'latitude' => (float) $latitude,
                        'longitude' => (float) $longitude,
                        'sequence' => is_numeric($point['sequence'] ?? null) ? (int) $point['sequence'] : null,
                    ]);
                };

                $appendPoint($routeJson['start_point'] ?? null, 'start');

                foreach ((array) ($routeJson['pickup_points'] ?? []) as $point) {
                    $appendPoint($point, 'pickup');
                }

                $appendPoint($routeJson['end_point'] ?? null, 'end');

                return [
                    (int) $route->id => $points->values()->all(),
                ];
            })
            ->all();
    @endphp

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item">
                            <a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Edit Stop Or Pickup Point
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Stop Or Pickup Point</h4>
            </div>

            <div class="card-body">
                <form id="stopPickupForm">
                    @csrf
                    @method('PUT')

                    {{-- Route --}}
                    <div class="form-group">
                        <label>Route Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="route_id" id="route_id">
                            <option value="">Select Route</option>

                            @foreach ($routeData as $route)
                               <option value="{{ $route->id }}"
    {{ $stopPickup->route_id == $route->id ? 'selected' : '' }}>
    {{ $route->name }}
</option>
                            @endforeach
                        </select>
                    </div>


                    {{-- Pickup Name --}}
                    <div class="form-group">
                        <label>Start Point</label>
                        <input type="text" class="form-control" id="start_point_display" readonly
                            placeholder="Select Route first">
                        <input type="hidden" id="start_point_name" name="start_point_name">
                    </div>
                    <div class="form-group">
                        <label>Pickup Name</label>
                        <textarea class="form-control" id="pickup_name_display" rows="3" readonly
                            placeholder="Select Route first" style="resize:none;"></textarea>
                        <input type="hidden" id="pickup_name" name="pickup_name"
                            value="{{ $stopPickup->pickup_name }}">
                    </div>

                    {{-- Stop Name --}}
                    <div class="form-group">
                        <label>Stop Name <span style="color:red;">*</span></label>
                        <select class="form-control" id="stop_name" name="stop_name" disabled>
                            <option value="">Select Stop Name</option>
                        </select>
                    </div>

                    {{-- Latitude --}}
                    <div class="form-group">
                        <label>Latitude <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="latitude" name="latitude"
                            value="{{ $stopPickup->latitude }}" step="any" min="-90" max="90" required
                            autocomplete="off">
                    </div>

                    {{-- Longitude --}}
                    <div class="form-group">
                        <label>Longitude <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="longitude" name="longitude"
                            value="{{ $stopPickup->longitude }}" step="any" min="-180" max="180" required
                            autocomplete="off">
                    </div>

                    {{-- Sequence Order --}}
                    <div class="form-group">
                        <label>Sequence Order <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="sequence_order" name="sequence_order"
                            value="{{ $stopPickup->sequence_order }}" required autocomplete="off"
                            oninput="this.value = this.value < 1 ? '' : this.value">
                    </div>

                    <button type="button" class="btn btn-primary" id="submitBtn">Update</button>
                    <a href="{{ route('stopPickup.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <div id="routePointSources" style="display:none;">
        @foreach ($routeData as $route)
            <select data-route-id="{{ $route->id }}">
                @foreach (($routePointOptions[$route->id] ?? []) as $point)
                    <option value="{{ $point['name'] }}"
                        data-lat="{{ $point['latitude'] }}"
                        data-lng="{{ $point['longitude'] }}"
                        data-sequence="{{ $point['sequence'] }}"
                        data-type="{{ $point['type'] }}">
                        {{ $point['label'] }}
                    </option>
                @endforeach
            </select>
        @endforeach
    </div>

    {{-- JS --}}
    <script>
        (function() {
            var currentPickupName = @json((string) ($stopPickup->pickup_name ?? ''));
            var currentStopName = @json((string) ($stopPickup->stop_name ?? ''));
            var routeSelect = document.getElementById('route_id');
            var startPointDisplay = document.getElementById('start_point_display');
            var startPointNameInput = document.getElementById('start_point_name');
            var pickupNameDisplay = document.getElementById('pickup_name_display');
            var pickupNameInput = document.getElementById('pickup_name');
            var stopSelect = document.getElementById('stop_name');
            var latitudeInput = document.getElementById('latitude');
            var longitudeInput = document.getElementById('longitude');
            var sequenceInput = document.getElementById('sequence_order');
            var routePointSources = document.getElementById('routePointSources');

            function escapeHtml(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function syncNiceSelect(selectElement) {
                if (!window.jQuery || !selectElement || !window.jQuery.fn || typeof window.jQuery.fn.niceSelect !== 'function') {
                    return;
                }

                var $select = window.jQuery(selectElement);

                if ($select.next('.nice-select').length) {
                    $select.niceSelect('destroy');
                }

                $select.css('display', '');
                $select.niceSelect();
            }

            function appendFallbackOption(selectElement, value) {
                if (!value) {
                    return;
                }

                for (var index = 0; index < selectElement.options.length; index++) {
                    if (selectElement.options[index].value === value) {
                        return;
                    }
                }

                selectElement.insertAdjacentHTML(
                    'beforeend',
                    '<option value="' + escapeHtml(value) + '" selected>' + escapeHtml(value) + ' (Current)</option>'
                );
            }

            function fillRouteMetaFrom(selectElement, syncTarget) {
                var selectedOption = selectElement.options[selectElement.selectedIndex];
                var hasPoint = selectedOption && selectedOption.value !== '';

                if (!hasPoint) {
                    return;
                }

                if (selectedOption.getAttribute('data-lat')) {
                    latitudeInput.value = selectedOption.getAttribute('data-lat');
                }
                if (selectedOption.getAttribute('data-lng')) {
                    longitudeInput.value = selectedOption.getAttribute('data-lng');
                }
                if (selectedOption.getAttribute('data-sequence')) {
                    sequenceInput.value = selectedOption.getAttribute('data-sequence');
                }

                if (syncTarget && syncTarget.value === '') {
                    syncTarget.value = selectedOption.value;
                }
            }

            function getRouteSource(routeId) {
                if (!routePointSources || !routeId) {
                    return null;
                }

                return routePointSources.querySelector('select[data-route-id="' + routeId + '"]');
            }

            function fillStartPoint(sourceSelect) {
                startPointDisplay.value = '';
                startPointNameInput.value = '';

                if (!sourceSelect || !sourceSelect.options.length) {
                    startPointDisplay.placeholder = 'Select Route first';
                    return;
                }

                for (var index = 0; index < sourceSelect.options.length; index++) {
                    var sourceOption = sourceSelect.options[index];
                    var optionType = String(sourceOption.getAttribute('data-type') || '').toLowerCase();

                    if (optionType !== 'start') {
                        continue;
                    }

                    startPointDisplay.value = sourceOption.value;
                    startPointNameInput.value = sourceOption.value;
                    return;
                }

                startPointDisplay.placeholder = 'No start point available';
            }

            function fillPickupNames(sourceSelect, fallbackValue) {
                pickupNameDisplay.value = '';
                pickupNameInput.value = '';
                fallbackValue = String(fallbackValue || '');

                if (!sourceSelect || !sourceSelect.options.length) {
                    if (fallbackValue !== '') {
                        pickupNameDisplay.value = fallbackValue;
                        pickupNameInput.value = fallbackValue;
                        return;
                    }

                    pickupNameDisplay.placeholder = 'Select Route first';
                    return;
                }

                var pickupNames = [];
                var pickupDisplayNames = [];
                var pickupCounter = 0;

                for (var index = 0; index < sourceSelect.options.length; index++) {
                    var sourceOption = sourceSelect.options[index];
                    var optionType = String(sourceOption.getAttribute('data-type') || '').toLowerCase();

                    if (optionType !== 'pickup') {
                        continue;
                    }

                    pickupCounter++;
                    pickupNames.push(sourceOption.value);
                    pickupDisplayNames.push('Pickup ' + pickupCounter + ': ' + sourceOption.value);
                }

                if (pickupNames.length === 0) {
                    if (fallbackValue !== '') {
                        pickupNameDisplay.value = fallbackValue;
                        pickupNameInput.value = fallbackValue;
                        return;
                    }

                    pickupNameDisplay.placeholder = 'No pickup point available';
                    return;
                }

                pickupNameDisplay.value = pickupDisplayNames.join('\n');
                pickupNameInput.value = pickupNames.join(', ');
            }

            function copySourceOptions(sourceSelect, targetSelect, selectedValue) {
                selectedValue = selectedValue || '';
                targetSelect.innerHTML = '<option value="">Select Stop Name</option>';

                if (!sourceSelect || !sourceSelect.options.length) {
                    targetSelect.disabled = true;
                    appendFallbackOption(targetSelect, selectedValue);
                    syncNiceSelect(targetSelect);
                    return;
                }

                targetSelect.disabled = false;
                var appendedOptions = 0;
                var shouldAutoSelectFirst = targetSelect.id === 'stop_name' && selectedValue === '';

                for (var index = 0; index < sourceSelect.options.length; index++) {
                    var sourceOption = sourceSelect.options[index];
                    var optionType = String(sourceOption.getAttribute('data-type') || '').toLowerCase();
                    var allowOption = optionType === 'end';

                    if (!allowOption) {
                        continue;
                    }

                    var optionValue = sourceOption.value;
                    var isSelected = optionValue === selectedValue || (shouldAutoSelectFirst && appendedOptions === 0);

                    var optionMarkup = '<option value="' + escapeHtml(optionValue) + '" data-lat="' +
                        escapeHtml(sourceOption.getAttribute('data-lat')) + '" data-lng="' +
                        escapeHtml(sourceOption.getAttribute('data-lng')) + '" data-sequence="' +
                        escapeHtml(sourceOption.getAttribute('data-sequence')) + '" data-type="' +
                        escapeHtml(sourceOption.getAttribute('data-type')) + '" ' +
                        (isSelected ? 'selected' : '') + '>' +
                        escapeHtml(optionValue) + '</option>';
                    targetSelect.insertAdjacentHTML('beforeend', optionMarkup);
                    appendedOptions++;
                }

                targetSelect.disabled = appendedOptions === 0;
                appendFallbackOption(targetSelect, selectedValue);

                syncNiceSelect(targetSelect);
            }

            function loadRoutePoints(routeId, selectedPickup, selectedStop) {
                var normalizedRouteId = parseInt(routeId, 10) || 0;
                selectedPickup = selectedPickup || '';
                selectedStop = selectedStop || '';

                if (normalizedRouteId <= 0) {
                    fillPickupNames(null, currentPickupName);
                    fillStartPoint(null);
                    stopSelect.innerHTML = '<option value="">Select Stop Name</option>';
                    appendFallbackOption(stopSelect, selectedStop);
                    stopSelect.disabled = true;
                    syncNiceSelect(stopSelect);
                    return;
                }

                var sourceSelect = getRouteSource(normalizedRouteId);
                fillStartPoint(sourceSelect);
                fillPickupNames(sourceSelect, selectedPickup);
                copySourceOptions(sourceSelect, stopSelect, selectedStop);
                if (stopSelect.value) {
                    fillRouteMetaFrom(stopSelect, null);
                }
            }

            loadRoutePoints(routeSelect.value, currentPickupName, currentStopName);

            function handleRouteChange() {
                latitudeInput.value = '';
                longitudeInput.value = '';
                sequenceInput.value = '';
                loadRoutePoints(this.value);
            }

            function handleStopChange() {
                fillRouteMetaFrom(this, null);
            }

            routeSelect.addEventListener('change', handleRouteChange);
            routeSelect.addEventListener('input', handleRouteChange);
            stopSelect.addEventListener('change', handleStopChange);

            window.addEventListener('load', function() {
                if (!window.jQuery) {
                    return;
                }

                window.jQuery(document)
                    .off('change.stopPickupRoute', '#route_id')
                    .on('change.stopPickupRoute', '#route_id', handleRouteChange)
                    .off('change.stopPickupStop', '#stop_name')
                    .on('change.stopPickupStop', '#stop_name', handleStopChange);
            });
        })();


        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            var formData = new FormData(document.getElementById('stopPickupForm'));
            var isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('route_id')) showError('#route_id', 'Route Name is required');
            if (!formData.get('stop_name')) showError('#stop_name', 'Stop Name is required');
            if (!formData.get('latitude')) showError('#latitude', 'Latitude is required');
            if (!formData.get('longitude')) showError('#longitude', 'Longitude is required');
            if (!formData.get('sequence_order')) showError('#sequence_order', 'Sequence Order is required');

            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: function() {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route('api.stopPickup.update', $stopPickup->id) }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                    'Accept': 'application/json'
                },
                success: function(data) {
                    Swal.close();
                    if (data && data.success) {
                        notify('success', data.message);
                        setTimeout(function() {
                            window.location.href = '{{ route('stopPickup.index') }}';
                        }, 1500);
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                    notify('error', response && response.message ? response.message : 'Something went wrong');
                }
            });

        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });

        document.getElementById('route_id').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('stop_name').addEventListener('change', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('latitude').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('longitude').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('sequence_order').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });

        // real-time typing + paste validation
        $('#sequence_order').on('input paste', function() {
            var value = $(this).val();
            if (value && !/^\d*\.?\d*$/.test(value)) {
                $(this).val(value.slice(0, -1));
            }
        });
    </script>
@endsection
