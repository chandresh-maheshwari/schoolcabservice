@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

    @php
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $schoolSlug = request()->route('schoolSlug');
        $isSchoolPanel = filled($schoolSlug) && is_string($routeName) && str_starts_with($routeName, 'school.');

        $routesStoreUrl = $isSchoolPanel
            ? route('school.routes.store', ['schoolSlug' => $schoolSlug])
            : route('routes.store');

        $routesIndexUrl = $isSchoolPanel
            ? route('school.routes.index', ['schoolSlug' => $schoolSlug])
            : route('routes.index');
    @endphp

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4>Add Route Details</h4>
            </div>

            <div class="card-body">
                <form id="routeForm">
                    @csrf

                    {{-- Route Name --}}
                    <div class="form-group">
                        <label><b>Route Name</b> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="name">
                        <span class="error-message text-danger"></span>
                    </div>

                    {{-- Vehicle --}}
                    <div class="form-group">
                        <label><b>Vehicle</b> <span class="text-danger">*</span></label>
                        <select class="form-control" name="bus_id" id="bus_id">
                            <option value="">Select Vehicle</option>
                            @foreach ($buses as $bus)
                                <option value="{{ $bus->id }}">{{ $bus->vehicle_number }}</option>
                            @endforeach
                        </select>
                        <span class="error-message text-danger"></span>
                    </div>

                    {{-- Driver --}}
                    <div class="form-group">
                        <label><b>Driver</b> <span class="text-danger">*</span></label>
                        <select class="form-control" name="driver_id" id="driver_id">
                            <option value="">Select Driver</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->driver_name }}</option>
                            @endforeach
                        </select>
                        <span class="error-message text-danger"></span>
                    </div>

                    {{-- MAP --}}
                    <div class="form-group">
                        <label><b>Draw Route on Map</b> <span class="text-danger">*</span></label>
                        <div id="map" style="height: 400px;"></div>
                        <div class="mt-2 d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="undoPointBtn">Undo last point</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="clearRouteBtn">Clear</button>
                        </div>
                        <small class="text-muted">
                            👉 Click on map to draw route & stops
                        </small>
                    </div>

                    {{-- Hidden Fields --}}
                    <input type="hidden" name="geojson" id="geojson">
                    <input type="hidden" name="stops" id="stops">

                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                    <a href="{{ $routesIndexUrl }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- GOOGLE MAP --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        let map, polyline;
        let routeCoords = []; // [[lng,lat]]
        let routeLatLngs = []; // [[lat,lng]]
        let stops = [];
        let markers = [];

        function initMap() {
            map = L.map('map').setView([23.0225, 72.5714], 12);

            const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                crossOrigin: true,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            tileLayer.on('tileerror', () => {
                notify('error', 'Map tiles failed to load. Please hard refresh and try again.');
            });

            polyline = L.polyline([], {
                color: '#2C9DD4',
                weight: 4
            }).addTo(map);

            map.on('click', function(e) {
                addRoutePoint(e.latlng);
            });

            const undoBtn = document.getElementById('undoPointBtn');
            if (undoBtn) undoBtn.addEventListener('click', undoLastPoint);
            const clearBtn = document.getElementById('clearRouteBtn');
            if (clearBtn) clearBtn.addEventListener('click', clearRoute);
        }

        function addRoutePoint(latLng) {
            routeCoords.push([latLng.lng, latLng.lat]);
            routeLatLngs.push([latLng.lat, latLng.lng]);
            polyline.setLatLngs(routeLatLngs);

            const marker = L.marker([latLng.lat, latLng.lng]).addTo(map);
            markers.push(marker);

            stops.push({
                name: "Stop " + (stops.length + 1),
                lat: latLng.lat,
                lng: latLng.lng
            });

            updateHiddenFields();
        }

        function undoLastPoint() {
            if (routeCoords.length === 0) return;
            routeCoords.pop();
            routeLatLngs.pop();
            const marker = markers.pop();
            if (marker) {
                map.removeLayer(marker);
            }
            stops.pop();
            polyline.setLatLngs(routeLatLngs);
            updateHiddenFields();
        }

        function clearRoute() {
            routeCoords = [];
            routeLatLngs = [];
            stops = [];
            markers.forEach(m => map.removeLayer(m));
            markers = [];
            polyline.setLatLngs([]);
            updateHiddenFields();
        }

        function updateHiddenFields() {
            document.getElementById('geojson').value = JSON.stringify({
                type: "LineString",
                coordinates: routeCoords
            });

            document.getElementById('stops').value = JSON.stringify(stops);
        }

        window.onload = initMap;

        $('#submitBtn').on('click', function() {
            let formData = new FormData(document.getElementById('routeForm'));
            let valid = true;

            $('.error-message').text('');

            if (!formData.get('name')) {
                $('#name').next('.error-message').text('Route name required');
                valid = false;
            }
            if (!formData.get('bus_id')) {
                $('#bus_id').next('.error-message').text('Vehicle required');
                valid = false;
            }
            if (!formData.get('driver_id')) {
                $('#driver_id').next('.error-message').text('Driver required');
                valid = false;
            }
            if (!formData.get('geojson')) {
                alert('Please draw route on map');
                valid = false;
            }

            if (!valid) return;

            Swal.fire({
                title: 'Saving...',
                didOpen: () => Swal.showLoading()
            });

            fetch(@json($routesStoreUrl), {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', 'Route created successfully');
                        setTimeout(() => {
                            window.location.href = @json($routesIndexUrl);
                        }, 1500);
                    } else {
                        notify('error', data.message);
                    }
                })
                .catch((error) => {
                    Swal.close();
                    notify('error', error?.message || 'Route create request failed');
                });
        });
        $(document).ready(function () {

    function handleEmptySelect(selector, message) {

        var $select = $(selector);

        function showAlert() {
            Swal.fire({
                icon: 'warning',
                title: 'Alert',
                text: message,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        }

        // Select2 case
        if ($select.hasClass("select2-hidden-accessible")) {

            $select.on('select2:opening', function (e) {
                if (this.options.length <= 1) {
                    e.preventDefault();
                    showAlert();
                }
            });

        } else {
            // Normal select fallback
            $select.on('mousedown', function (e) {
                if (this.options.length <= 1) {
                    e.preventDefault();
                    $(this).blur();
                    showAlert();
                    return false;
                }
            });
        }
    }

    // ✅ Driver dropdown
    handleEmptySelect(
        '#driver_id',
        'No unassigned driver available. Please add a driver or free one from another route.'
    );

    // ✅ Vehicle / Bus dropdown
    handleEmptySelect(
        '#bus_id',
        'No unassigned vehicle available. Please add a vehicle or free one from another route.'
    );

});
    </script>
@endsection
