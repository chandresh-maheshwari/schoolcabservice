@extends('admin_layout.index')

@section('content')
@include('partials.toaster')

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
                            <option value="{{ $bus->_id }}">{{ $bus->vehicle_number }}</option>
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
                            <option value="{{ $driver->_id }}">{{ $driver->driver_name }}</option>
                        @endforeach
                    </select>
                    <span class="error-message text-danger"></span>
                </div>

                {{-- MAP --}}
                <div class="form-group">
                    <label><b>Draw Route on Map</b> <span class="text-danger">*</span></label>
                    <div id="map" style="height: 400px;"></div>
                    <small class="text-muted">
                        👉 Click on map to draw route & stops
                    </small>
                </div>

                {{-- Hidden Fields --}}
                <input type="hidden" name="geojson" id="geojson">
                <input type="hidden" name="stops" id="stops">

                <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                <a href="{{ route('routes.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

{{-- GOOGLE MAP --}}
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAOVYRIgupAurZup5y1PRh8Ismb1A3lLao"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
let map, polyline;
let routeCoords = [];
let stops = [];

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: 23.0225, lng: 72.5714 },
        zoom: 12
    });

    polyline = new google.maps.Polyline({
        map: map,
        strokeColor: "#2C9DD4",
        strokeWeight: 4
    });

    map.addListener("click", function (e) {
        addRoutePoint(e.latLng);
    });
}

function addRoutePoint(latLng) {
    routeCoords.push([latLng.lng(), latLng.lat()]);
    polyline.setPath(routeCoords.map(c => ({ lat: c[1], lng: c[0] })));

    // Add stop marker
    const marker = new google.maps.Marker({
        position: latLng,
        map: map
    });

    stops.push({
        name: "Stop " + stops.length,
        lat: latLng.lat(),
        lng: latLng.lng()
    });

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

$('#submitBtn').on('click', function () {
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

    Swal.fire({ title: 'Saving...', didOpen: () => Swal.showLoading() });

    fetch('{{ route('routes.store') }}', {
        method: 'POST',
        body: formData,
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            notify('success', 'Route created successfully');
             setTimeout(() => {
                            window.location.href = '{{ route('routes.index') }}';
                        }, 1500);
        } else {
            notify('error', data.message);
        }
    });
});
</script>
@endsection
