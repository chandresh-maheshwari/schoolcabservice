@extends('admin_layout.index')

@section('content')
@include('partials.toaster')

@php
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
    $schoolSlug = request()->route('schoolSlug');
    $isSchoolPanel = filled($schoolSlug) && is_string($routeName) && str_starts_with($routeName, 'school.');

    $routesUpdateUrl = $isSchoolPanel
        ? route('school.routes.update', ['schoolSlug' => $schoolSlug, 'route' => $route->id])
        : route('routes.update', $route->id);

    $routesIndexUrl = $isSchoolPanel
        ? route('school.routes.index', ['schoolSlug' => $schoolSlug])
        : route('routes.index');
@endphp

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4>Edit Route Details</h4>
        </div>

        <div class="card-body">
            <form id="routeForm">
                @csrf
                @method('PUT')

                {{-- Route Name --}}
                <div class="form-group">
                    <label><b>Route Name</b> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="name"
                        value="{{ $route->name }}">
                    <span class="error-message text-danger"></span>
                </div>

                {{-- Vehicle --}}
                <div class="form-group">
                    <label><b>Vehicle</b> <span class="text-danger">*</span></label>
                    <select class="form-control" name="bus_id" id="bus_id">
                        <option value="">Select Vehicle</option>
                        @foreach ($buses as $bus)
                            <option value="{{ $bus->id }}"
                                {{ $route->bus_id == $bus->id ? 'selected' : '' }}>
                                {{ $bus->vehicle_number }}
                            </option>
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
                            <option value="{{ $driver->id }}"
                                {{ $route->driver_id == $driver->id ? 'selected' : '' }}>
                                {{ $driver->driver_name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="error-message text-danger"></span>
                </div>

                {{-- MAP --}}
                <div class="form-group">
                    <label><b>Edit Route on Map</b> <span class="text-danger">*</span></label>
                    <div id="map" style="height:400px;"></div>
                    <small class="text-muted">
                        👉 Click map to add more points / stops
                    </small>
                </div>

                {{-- Hidden Fields --}}
                <input type="hidden" name="geojson" id="geojson">
                <input type="hidden" name="stops" id="stops">

                <button type="button" class="btn btn-primary" id="updateBtn">
                    Update Route
                </button>

                <a href="{{ $routesIndexUrl }}" class="btn btn-secondary">
                    Cancel
                </a>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
let map, polyline;
let routeCoords = [];
let stops = [];
let markers = [];
let routeLatLngs = [];

const rawGeoJson = @json($route->geojson ?? null);
const rawStops   = @json($route->stops ?? []);

function normalizeGeoJson(data) {

    // Case 1: Already array [[lng,lat]]
    if (Array.isArray(data)) {
        return data;
    }

    // Case 2: GeoJSON object {type, coordinates}
    if (data && typeof data === 'object' && Array.isArray(data.coordinates)) {
        return data.coordinates;
    }

    return [];
}

function initMap() {

    routeCoords = normalizeGeoJson(rawGeoJson);
    stops = Array.isArray(rawStops) ? rawStops : [];

    if (routeCoords.length === 0) {
        alert('Route coordinates not found in database');
        return;
    }

    map = L.map('map').setView([routeCoords[0][1], routeCoords[0][0]], 13);

    const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        crossOrigin: true,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    tileLayer.on('tileerror', () => {
        notify('error', 'Map tiles failed to load. Please hard refresh and try again.');
    });

    routeLatLngs = routeCoords.map(p => [p[1], p[0]]);
    polyline = L.polyline(routeLatLngs, { color: '#2C9DD4', weight: 4 }).addTo(map);

    // Existing stops
    stops.forEach(stop => {
        markers.push(L.marker([stop.lat, stop.lng], { title: stop.name }).addTo(map));
    });

    updateHiddenFields();

    map.on('click', function (e) {
        addRoutePoint(e.latlng);
    });
}

function addRoutePoint(latLng) {

    routeCoords.push([latLng.lng, latLng.lat]);
    routeLatLngs.push([latLng.lat, latLng.lng]);
    polyline.setLatLngs(routeLatLngs);
    markers.push(L.marker([latLng.lat, latLng.lng]).addTo(map));

    stops.push({
        name: "Stop " + stops.length,
        lat: latLng.lat,
        lng: latLng.lng
    });

    updateHiddenFields();
}

function updateHiddenFields() {
    document.getElementById('geojson').value = JSON.stringify({
        type: "LineString",
        coordinates: routeCoords
    });
    document.getElementById('stops').value  = JSON.stringify(stops);
}

window.onload = initMap;
// window.onload = initMap;

$('#updateBtn').on('click', function () {

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

    if (!valid) return;

    Swal.fire({ title: 'Updating...', didOpen: () => Swal.showLoading() });

    fetch(@json($routesUpdateUrl), {
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
            notify('success', 'Route updated successfully');
             setTimeout(() => {
                            window.location.href = @json($routesIndexUrl);
                        }, 1500);
        } else {
            notify('error', data.message);
        }
    })
    .catch((error) => {
        Swal.close();
        notify('error', error?.message || 'Route update request failed');
    });
});
</script>
@endsection
