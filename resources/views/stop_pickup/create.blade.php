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
                            Add Stop Or Pickup Point
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Stop Or Pickup Point </h4>
            </div>

            <div class="card-body">
                <form id="stopPickupForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Route Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="route_id" id="route_id">
                            <option value="">Select Route</option>
                            @foreach ($routeData as $route)
                                <option value="{{ $route->id }}">{{ $route->name }}</option>
                            @endforeach
                        </select>
                    </div>
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
                        <input type="hidden" id="pickup_name" name="pickup_name">
                    </div>
                    <div class="form-group">
                        <label>Stop Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="stop_name_display" readonly
                            placeholder="Select Route first">
                        <input type="hidden" id="stop_name" name="stop_name">
                    </div>

                    <div class="form-group">
                        <label>Latitude <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="latitude" name="latitude" step="any"
                            min="-90" max="90" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Longitude <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="longitude" name="longitude" step="any"
                            min="-180" max="180" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Squence Order <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="sequence_order" name="sequence_order" required
                            autocomplete="off" oninput="this.value = this.value < 1 ? '' : this.value">
                    </div>



                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
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
            var routeSelect = document.getElementById('route_id');
            var startPointDisplay = document.getElementById('start_point_display');
            var startPointNameInput = document.getElementById('start_point_name');
            var pickupNameDisplay = document.getElementById('pickup_name_display');
            var pickupNameInput = document.getElementById('pickup_name');
            var stopNameDisplay = document.getElementById('stop_name_display');
            var stopNameInput = document.getElementById('stop_name');
            var latitudeInput = document.getElementById('latitude');
            var longitudeInput = document.getElementById('longitude');
            var sequenceInput = document.getElementById('sequence_order');
            var routePointSources = document.getElementById('routePointSources');

            function resetDependentFields() {
                latitudeInput.value = '';
                longitudeInput.value = '';
                sequenceInput.value = '';
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

            function fillPickupNames(sourceSelect) {
                pickupNameDisplay.value = '';
                pickupNameInput.value = '';

                if (!sourceSelect || !sourceSelect.options.length) {
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
                    pickupNameDisplay.placeholder = 'No pickup point available';
                    return;
                }

                pickupNameDisplay.value = pickupDisplayNames.join('\n');
                pickupNameInput.value = pickupNames.join(', ');
            }

            function fillStopPoint(sourceSelect) {
                stopNameDisplay.value = '';
                stopNameInput.value = '';

                if (!sourceSelect || !sourceSelect.options.length) {
                    stopNameDisplay.placeholder = 'Select Route first';
                    resetDependentFields();
                    return;
                }

                for (var index = 0; index < sourceSelect.options.length; index++) {
                    var sourceOption = sourceSelect.options[index];
                    var optionType = String(sourceOption.getAttribute('data-type') || '').toLowerCase();

                    if (optionType !== 'end') {
                        continue;
                    }

                    stopNameDisplay.value = sourceOption.value;
                    stopNameInput.value = sourceOption.value;
                    latitudeInput.value = sourceOption.getAttribute('data-lat') || '';
                    longitudeInput.value = sourceOption.getAttribute('data-lng') || '';
                    sequenceInput.value = sourceOption.getAttribute('data-sequence') || '';
                    return;
                }

                stopNameDisplay.placeholder = 'No stop point available';
                resetDependentFields();
            }

            function loadRoutePoints(routeId, selectedPickup, selectedStop) {
                var normalizedRouteId = parseInt(routeId, 10) || 0;
                selectedPickup = selectedPickup || '';
                selectedStop = selectedStop || '';

                if (normalizedRouteId <= 0) {
                    fillPickupNames(null);
                    fillStartPoint(null);
                    fillStopPoint(null);
                    return;
                }

                var sourceSelect = getRouteSource(normalizedRouteId);
                resetDependentFields();
                fillStartPoint(sourceSelect);
                fillPickupNames(sourceSelect);
                fillStopPoint(sourceSelect);
            }

            loadRoutePoints(routeSelect.value);

            function handleRouteChange() {
                loadRoutePoints(this.value);
            }

            routeSelect.addEventListener('change', handleRouteChange);
            routeSelect.addEventListener('input', handleRouteChange);

            window.addEventListener('load', function() {
                if (!window.jQuery) {
                    return;
                }

                window.jQuery(document)
                    .off('change.stopPickupRoute', '#route_id')
                    .on('change.stopPickupRoute', '#route_id', handleRouteChange);
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
            if (!formData.get('stop_name')) showError('#stop_name_display', 'Stop Name is required');
            if (!formData.get('latitude')) showError('#latitude', 'Latitude is required');
            if (!formData.get('longitude')) showError('#longitude', 'Longitude is required');
            if (!formData.get('sequence_order')) showError('#sequence_order', 'Squence Order is required');


            function isValidPositive(value) {
                return /^[a-zA-Z0-9]+$/.test(value);
            }
            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: function() {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route('api.stopPickup.store') }}',
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
