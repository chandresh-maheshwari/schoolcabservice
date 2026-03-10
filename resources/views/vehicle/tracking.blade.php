@extends('admin_layout.index')

@section('content')
<div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('vehicle.index') }}">Vehicle</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Tracking</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h4 class="mb-0">Vehicle Live Tracking (India)</h4>
            <div class="d-flex gap-3 flex-wrap align-items-center">
                <span><b>Vehicles:</b> <span id="trackedVehiclesCount">0</span></span>
                <span><b>With Location:</b> <span id="gpsVehiclesCount">0</span></span>
                <span><b>Last Refresh:</b> <span id="lastUpdatedAt">-</span></span>
                <button type="button" id="runTrackingDemo" class="btn btn-sm btn-danger">Run Demo</button>
            </div>
        </div>
        <div class="card-body">
            <div id="trackingNotice" class="alert alert-warning d-none" role="alert"></div>
            <div id="liveTrackingMap" style="height: 70vh; min-height: 480px; border-radius: 8px;"></div>
            <small class="text-muted d-block mt-2">Red marker: current live position only (no history trail). Demo button ek random route par moving marker dikhata hai.</small>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const trackingApiBase = '{{ route('api.vehicle.tracking.live') }}';
    const focusDriverId = @json($focusDriverId);
    const trackingApiUrl = new URL(trackingApiBase, window.location.origin);
    if (focusDriverId) {
        trackingApiUrl.searchParams.set('driver_id', focusDriverId);
    }
    const indiaBounds = L.latLngBounds([6.5, 68.0], [37.6, 97.5]);
    const map = L.map('liveTrackingMap', {
        maxBounds: indiaBounds,
        maxBoundsViscosity: 1.0
    }).setView([22.9734, 78.6569], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const markers = {};
    const markerAnimations = {};
    const locationNameCache = {};
    const pendingGeocode = {};
    const demoStops = [
        { name: 'Vadodara Station', point: [22.3042, 73.1812] },
        { name: 'Sayaji Baug', point: [22.3149, 73.1882] },
        { name: 'Akota Stadium', point: [22.2966, 73.1687] },
        { name: 'Laxmipura', point: [22.3368, 73.1647] },
        { name: 'Nizampura', point: [22.3308, 73.1781] },
        { name: 'Manjalpur', point: [22.2737, 73.1926] }
    ];
    const demoAnimationKey = 'demo-marker';
    let fittedOnce = false;
    let focusHandled = false;
    let demoMarker = null;
    let demoRouteLine = null;
    let demoRouteTimer = null;
    let demoRoutePoints = [];
    let demoRouteIndex = 0;
    const followFocusedDriver = true;

    const markerColor = () => '#d63031';

    function clearStaleLayers(activeVehicleIds) {
        Object.keys(markers).forEach((id) => {
            if (!activeVehicleIds.includes(parseInt(id, 10))) {
                stopMarkerAnimation(id);
                map.removeLayer(markers[id]);
                delete markers[id];
            }
        });
    }

    function getStatusLabel(status) {
        if (status === 1 || status === '1' || status === 'active') {
            return 'Active';
        }
        if (status === 0 || status === '0' || status === 'inactive') {
            return 'Inactive';
        }
        return status ? String(status) : '-';
    }

    function toFiniteNumber(value) {
        const number = Number(value);
        return Number.isFinite(number) ? number : null;
    }

    function stopMarkerAnimation(animationKey) {
        if (!markerAnimations[animationKey]) {
            return;
        }

        cancelAnimationFrame(markerAnimations[animationKey]);
        delete markerAnimations[animationKey];
    }

    function animateMarkerLayer(animationKey, marker, point, duration = 4500, onProgress = null) {
        if (!marker) {
            return;
        }

        stopMarkerAnimation(animationKey);

        const startLatLng = marker.getLatLng();
        if (!startLatLng || (startLatLng.lat === point[0] && startLatLng.lng === point[1])) {
            marker.setLatLng(point);
            return;
        }

        const startTime = performance.now();

        const step = (currentTime) => {
            const progress = Math.min((currentTime - startTime) / duration, 1);
            const nextLat = startLatLng.lat + ((point[0] - startLatLng.lat) * progress);
            const nextLng = startLatLng.lng + ((point[1] - startLatLng.lng) * progress);

            marker.setLatLng([nextLat, nextLng]);

            if (typeof onProgress === 'function') {
                onProgress([nextLat, nextLng]);
            }

            if (progress < 1) {
                markerAnimations[animationKey] = requestAnimationFrame(step);
            } else {
                delete markerAnimations[animationKey];
            }
        };

        markerAnimations[animationKey] = requestAnimationFrame(step);
    }

    function animateMarkerTo(vehicleId, point, duration = 4500) {
        const marker = markers[vehicleId];

        animateMarkerLayer(vehicleId, marker, point, duration, (nextPoint) => {
            if (focusDriverId && vehicleId === Number(focusDriverId) && followFocusedDriver) {
                map.panTo(nextPoint, {
                    animate: false
                });
            }
        });
    }

    function buildDemoRoutePoints(startPoint, endPoint, totalSteps = 14) {
        const points = [];
        const curveStrength = Math.max(
            Math.abs(endPoint[0] - startPoint[0]),
            Math.abs(endPoint[1] - startPoint[1])
        ) * 0.18;

        for (let index = 0; index <= totalSteps; index += 1) {
            const progress = index / totalSteps;
            const arcOffset = Math.sin(progress * Math.PI) * curveStrength;

            points.push([
                startPoint[0] + ((endPoint[0] - startPoint[0]) * progress) + (arcOffset * 0.35),
                startPoint[1] + ((endPoint[1] - startPoint[1]) * progress) - (arcOffset * 0.25)
            ]);
        }

        return points;
    }

    function pickDemoStops() {
        const startIndex = Math.floor(Math.random() * demoStops.length);
        let endIndex = Math.floor(Math.random() * demoStops.length);

        while (endIndex === startIndex) {
            endIndex = Math.floor(Math.random() * demoStops.length);
        }

        return {
            start: demoStops[startIndex],
            end: demoStops[endIndex]
        };
    }

    function stopDemoRoute() {
        if (demoRouteTimer) {
            clearTimeout(demoRouteTimer);
            demoRouteTimer = null;
        }

        stopMarkerAnimation(demoAnimationKey);
    }

    function setDemoButtonState(isRunning) {
        const demoButton = document.getElementById('runTrackingDemo');
        if (!demoButton) {
            return;
        }

        demoButton.disabled = isRunning;
        demoButton.textContent = isRunning ? 'Demo Running...' : 'Run Demo';
    }

    function runDemoStep() {
        if (!demoMarker || demoRouteIndex >= demoRoutePoints.length) {
            setDemoButtonState(false);
            demoRouteTimer = null;
            return;
        }

        const nextPoint = demoRoutePoints[demoRouteIndex];
        demoRouteIndex += 1;

        animateMarkerLayer(demoAnimationKey, demoMarker, nextPoint, 900, (currentPoint) => {
            map.panTo(currentPoint, {
                animate: false
            });
        });

        if (demoRouteIndex < demoRoutePoints.length) {
            demoRouteTimer = setTimeout(runDemoStep, 1000);
        } else {
            demoRouteTimer = setTimeout(() => {
                setDemoButtonState(false);
                demoRouteTimer = null;
            }, 1000);
        }
    }

    function startRandomDemoRoute() {
        const { start, end } = pickDemoStops();

        stopDemoRoute();

        demoRoutePoints = buildDemoRoutePoints(start.point, end.point);
        demoRouteIndex = 0;

        if (demoRouteLine) {
            map.removeLayer(demoRouteLine);
        }

        demoRouteLine = L.polyline(demoRoutePoints, {
            color: '#d63031',
            weight: 3,
            opacity: 0.45,
            dashArray: '8, 8'
        }).addTo(map);

        if (!demoMarker) {
            demoMarker = L.circleMarker(start.point, {
                radius: 9,
                color: '#d63031',
                fillColor: '#d63031',
                fillOpacity: 0.9,
                weight: 2
            }).addTo(map);
        } else {
            demoMarker.setLatLng(start.point);
        }

        demoMarker.bindPopup(
            `<b>Demo Vehicle</b><br><b>From:</b> ${start.name}<br><b>To:</b> ${end.name}`
        ).openPopup();

        map.fitBounds(L.latLngBounds(demoRoutePoints), {
            padding: [40, 40],
            maxZoom: 14
        });

        setDemoButtonState(true);
        runDemoStep();
    }

    function buildPopupHtml(vehicle, locationLabel) {
        const latValue = toFiniteNumber(vehicle.latitude);
        const lngValue = toFiniteNumber(vehicle.longitude);
        const latLngLabel = latValue !== null && lngValue !== null
            ? `${latValue.toFixed(6)}, ${lngValue.toFixed(6)}`
            : '-';

        return `<b>Vehicle:</b> ${vehicle.vehicle_number}<br>` +
            `<b>Driver:</b> ${vehicle.driver_name || '-'}<br>` +
            `<b>Status:</b> ${getStatusLabel(vehicle.status)}<br>` +
            `<b>Source:</b> ${vehicle.source || '-'}<br>` +
            `<b>Recorded:</b> ${vehicle.recorded_at ? new Date(vehicle.recorded_at).toLocaleString('en-IN') : '-'}<br>` +
            `<b>Location Name:</b> ${locationLabel || '-'}<br>` +
            `<b>Location:</b> ${latLngLabel}`;
    }

    function getLocationKey(lat, lng) {
        return `${Number(lat).toFixed(3)},${Number(lng).toFixed(3)}`;
    }

    function ensureLocationName(vehicleId, vehicle) {
        const key = getLocationKey(vehicle.latitude, vehicle.longitude);

        if (locationNameCache[key] || pendingGeocode[key]) {
            return;
        }

        pendingGeocode[key] = true;

        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${vehicle.latitude}&lon=${vehicle.longitude}&zoom=16&addressdetails=1`, {
            headers: {
                'Accept': 'application/json',
                'Accept-Language': 'en'
            }
        })
            .then((res) => res.ok ? res.json() : null)
            .then((data) => {
                let place = '-';
                if (data) {
                    place = data.name || data.display_name || '-';
                }
                locationNameCache[key] = place;

                if (markers[vehicleId]) {
                    markers[vehicleId].bindPopup(buildPopupHtml(vehicle, place));
                    if (markers[vehicleId].isPopupOpen()) {
                        markers[vehicleId].openPopup();
                    }
                }
            })
            .catch(() => {
                locationNameCache[key] = '-';
            })
            .finally(() => {
                delete pendingGeocode[key];
            });
    }

    function renderVehicles(payload) {
        const vehicles = payload.vehicles || [];
        const activeVehicleIds = vehicles
            .map((v) => parseInt(v.id, 10))
            .filter((id) => Number.isFinite(id));
        clearStaleLayers(activeVehicleIds);

        const bounds = [];
        let locationCount = 0;

        vehicles.forEach((vehicle) => {
            const vehicleId = parseInt(vehicle.id, 10);
            if (!Number.isFinite(vehicleId)) {
                return;
            }

            const latitude = toFiniteNumber(vehicle.latitude);
            const longitude = toFiniteNumber(vehicle.longitude);

            if (latitude !== null && longitude !== null) {
                locationCount += 1;
                const point = [latitude, longitude];
                bounds.push(point);

                if (!markers[vehicleId]) {
                    markers[vehicleId] = L.circleMarker(point, {
                        radius: 8,
                        color: markerColor(vehicle.status),
                        fillColor: markerColor(vehicle.status),
                        fillOpacity: 0.85,
                        weight: 2
                    }).addTo(map);
                } else {
                    animateMarkerTo(vehicleId, point);
                    markers[vehicleId].setStyle({
                        color: markerColor(vehicle.status),
                        fillColor: markerColor(vehicle.status)
                    });
                }

                const locKey = getLocationKey(latitude, longitude);
                const locationLabel = locationNameCache[locKey] || 'Fetching area...';
                markers[vehicleId].bindPopup(buildPopupHtml(vehicle, locationLabel));
                ensureLocationName(vehicleId, {
                    ...vehicle,
                    latitude,
                    longitude
                });
            } else if (markers[vehicleId]) {
                stopMarkerAnimation(vehicleId);
                map.removeLayer(markers[vehicleId]);
                delete markers[vehicleId];
            }
        });

        if (focusDriverId && markers[focusDriverId] && !focusHandled) {
            map.setView(markers[focusDriverId].getLatLng(), 13);
            markers[focusDriverId].openPopup();
            fittedOnce = true;
            focusHandled = true;
        } else if (focusDriverId && markers[focusDriverId] && followFocusedDriver) {
            map.panTo(markers[focusDriverId].getLatLng(), {
                animate: true,
                duration: 0.8
            });
        } else if (!fittedOnce && bounds.length > 0) {
            map.fitBounds(bounds, {
                padding: [30, 30],
                maxZoom: 11
            });
            fittedOnce = true;
        } else if (!fittedOnce) {
            map.fitBounds(indiaBounds, { padding: [15, 15] });
            fittedOnce = true;
        }

        const notice = document.getElementById('trackingNotice');
        if (payload.schema_ready === false) {
            notice.classList.remove('d-none');
            notice.textContent = 'driverdetails table me currentLat/currentLng columns available nahi hain.';
        } else if (payload.selection_resolved === false) {
            notice.classList.remove('d-none');
            notice.textContent = payload.message || 'Selected vehicle ke liye live tracking mapping nahi mili.';
        } else if (vehicles.length > 0 && locationCount === 0) {
            notice.classList.remove('d-none');
            notice.textContent = 'Current location nahi mil rahi. App se currentLat/currentLng push karein.';
        } else {
            notice.classList.add('d-none');
            notice.textContent = '';
        }

        document.getElementById('gpsVehiclesCount').textContent = locationCount;
    }

    async function refreshTracking() {
        try {
            trackingApiUrl.searchParams.set('_ts', Date.now().toString());
            const res = await fetch(trackingApiUrl.toString(), {
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            });

            if (!res.ok) {
                return;
            }

            const payload = await res.json();
            renderVehicles(payload);

            document.getElementById('trackedVehiclesCount').textContent = (payload.vehicles || []).length;
            document.getElementById('lastUpdatedAt').textContent = payload.updated_at ?
                new Date(payload.updated_at).toLocaleTimeString('en-IN') : '-';
        } catch (error) {
            console.error('Vehicle tracking refresh failed:', error);
        }
    }

    document.getElementById('runTrackingDemo').addEventListener('click', startRandomDemoRoute);
    refreshTracking();
    setInterval(refreshTracking, 5000);
</script>
@endsection
