<style>
    .route-builder-layout {
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr);
        gap: 1rem;
        align-items: stretch;
    }

    .route-directions-sidebar {
        background: #fff;
        border: 1px solid #dbe7f1;
        border-radius: 18px;
        overflow: hidden;
        min-height: 620px;
        max-height: 620px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    .route-directions-header {
        padding: 0.8rem 1rem 0.45rem;
        border-bottom: 1px solid #edf2f7;
        background: #ffffff;
    }

    .route-directions-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #0f4c5c;
    }

    .route-directions-subtitle {
        margin-top: 0.2rem;
        font-size: 0.82rem;
        color: #6b7f90;
    }

    .route-directions-body {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 0.75rem 0.85rem 1rem;
        background: #ffffff;
    }

    .route-direction-row {
        display: grid;
        grid-template-columns: 22px minmax(0, 1fr);
        gap: 0.7rem;
        align-items: start;
        position: relative;
    }

    .route-direction-row + .route-direction-row {
        margin-top: 0.45rem;
    }

    .route-direction-row-end {
        margin-top: 0.65rem;
    }

    .route-direction-row-add {
        margin-top: 0.85rem;
    }

    .route-direction-marker-col {
        position: relative;
        display: flex;
        justify-content: center;
        min-height: 52px;
    }

    .route-direction-marker-col::after {
        content: '';
        position: absolute;
        top: 18px;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        border-left: 2px dotted #b8c8d6;
    }

    .route-direction-row:last-child .route-direction-marker-col::after,
    .route-direction-row.route-direction-row-end .route-direction-marker-col::after,
    .route-direction-row.route-direction-row-add .route-direction-marker-col::after {
        display: none;
    }

    .route-direction-marker {
        width: 12px;
        height: 12px;
        border-radius: 999px;
        margin-top: 8px;
        z-index: 1;
        background: #1971c2;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px #b3cde0;
    }

    .route-direction-row-start .route-direction-marker {
        background: #ffffff;
        border: 2px solid #111827;
        box-shadow: none;
    }

    .route-direction-row-end .route-direction-marker {
        background: #ffffff;
        border: 2px solid #ef4444;
        box-shadow: none;
    }

    .route-direction-row-add .route-direction-marker {
        width: 18px;
        height: 18px;
        margin-top: 5px;
        border: 2px solid #374151;
        background: #fff;
        box-shadow: none;
        position: relative;
    }

    .route-direction-row-add .route-direction-marker::before,
    .route-direction-row-add .route-direction-marker::after {
        content: '';
        position: absolute;
        background: #374151;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }

    .route-direction-row-add .route-direction-marker::before {
        width: 10px;
        height: 2px;
    }

    .route-direction-row-add .route-direction-marker::after {
        width: 2px;
        height: 10px;
    }

    .route-direction-card {
        border: 1px solid #d8e1e8;
        border-radius: 10px;
        background: #fff;
        padding: 0.2rem 0.3rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .route-direction-card:focus-within {
        border-color: #0ea5b7;
        box-shadow: 0 0 0 3px rgba(14, 165, 183, 0.12);
    }

    .route-direction-inputbar {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.35rem;
        align-items: center;
    }

    .route-direction-input {
        border: 0;
        box-shadow: none;
        height: 36px;
        padding: 0 0.45rem;
        font-size: 0.95rem;
        background: transparent;
    }

    .route-direction-input:focus {
        box-shadow: none;
    }

    .route-point-meta {
        margin: 0.15rem 0.45rem 0.35rem;
        font-size: 0.77rem;
        color: #5b7083;
    }

    .route-direction-actions {
        display: flex;
        gap: 0.25rem;
        align-items: center;
        flex-shrink: 0;
    }

    .route-direction-btn {
        border: 0;
        background: transparent;
        color: #0b7285;
        min-width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }

    .route-direction-btn:hover {
        background: #eef9fb;
        color: #075985;
    }

    .route-direction-btn.route-direction-btn-danger {
        color: #e11d48;
    }

    .route-direction-btn.route-direction-btn-danger:hover {
        background: #fff1f2;
    }

    .route-direction-btn.route-drag-handle {
        cursor: grab;
        color: #475569;
        font-size: 1rem;
    }

    .route-direction-btn.route-drag-handle:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .route-direction-row.route-dragging {
        opacity: 0.45;
    }

    .route-direction-row.route-drop-target .route-direction-card {
        border-color: #0ea5b7;
        box-shadow: 0 0 0 3px rgba(14, 165, 183, 0.12);
    }

    .route-search-wrap {
        position: relative;
    }

    .route-search-results {
        position: absolute;
        top: calc(100% + 0.25rem);
        left: 0;
        right: 0;
        z-index: 1000;
        max-height: 220px;
        overflow-y: auto;
        border: 1px solid #dbe7f1;
        border-radius: 12px;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
    }

    .route-direction-add-btn {
        width: 100%;
        border: 0;
        background: transparent;
        color: #334155;
        text-align: left;
        padding: 0.35rem 0 0.15rem;
        font-size: 0.95rem;
    }

    .route-direction-add-btn:hover {
        color: #0f766e;
    }

    #pickupPointsContainer {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }

    #pickupPointsContainer:not(:empty) {
        margin-top: 0.55rem;
    }

    .route-options-panel {
        margin-top: 0.9rem;
        border-top: 1px solid #edf2f7;
        padding-top: 0.8rem;
    }

    .route-options-title {
        margin: 0 0 0.55rem;
        font-size: 0.92rem;
        font-weight: 700;
        color: #0f4c5c;
    }

    .route-options-list {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }

    .route-option-card {
        border: 1px solid #dbe7f1;
        border-radius: 12px;
        background: #fff;
        padding: 0.7rem 0.8rem;
        cursor: pointer;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    .route-option-card:hover {
        border-color: #94d2db;
        background: #fbfeff;
    }

    .route-option-card.route-option-card-active {
        border-color: #0b7285;
        box-shadow: 0 0 0 3px rgba(11, 114, 133, 0.12);
        background: #f4fbfc;
    }

    .route-option-top {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .route-option-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
    }

    .route-option-duration {
        font-size: 1rem;
        font-weight: 700;
        color: #16a34a;
        white-space: nowrap;
    }

    .route-option-distance {
        margin-top: 0.1rem;
        font-size: 0.84rem;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        gap: 0.38rem;
    }

    .route-option-distance-icon {
        color: #0f172a;
        font-size: 0.8rem;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .route-option-distance-icon svg {
        width: 14px;
        height: 14px;
        display: block;
    }

    .route-option-subtext {
        margin-top: 0.35rem;
        font-size: 0.82rem;
        color: #475569;
    }

    .route-option-empty {
        font-size: 0.84rem;
        color: #64748b;
    }

    .route-map-panel {
        border: 1px solid #d6e8f5;
        border-radius: 18px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        min-height: 620px;
    }

    .route-map-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.55rem 0.85rem;
        border-bottom: 1px solid #e6f1f7;
        background: #ffffff;
    }

    .route-map-toolbar-text {
        font-size: 0.82rem;
        color: #64748b;
    }

    #routeBuilderMap {
        height: 570px;
    }

    .route-marker-badge {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.78rem;
        font-weight: 700;
        border: 2px solid #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.16);
    }

    .route-marker-start {
        background: #2f9e44;
    }

    .route-marker-pickup {
        background: #1971c2;
    }

    .route-marker-end {
        background: #e03131;
    }

    .route-marker-popup-title {
        font-weight: 700;
        color: #134b5f;
    }

    @media (max-width: 1199px) {
        .route-builder-layout {
            grid-template-columns: 330px minmax(0, 1fr);
        }
    }

    @media (max-width: 991px) {
        .route-builder-layout {
            grid-template-columns: 1fr;
        }

        .route-directions-sidebar,
        .route-map-panel {
            min-height: auto;
            max-height: none;
        }

        #routeBuilderMap {
            height: 420px;
        }
    }
</style>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4>{{ $formHeading }}</h4>
        </div>

        <div class="card-body">
            <form id="{{ $formId }}">
                @csrf
                @if (($formMethod ?? 'POST') !== 'POST')
                    @method($formMethod)
                @endif

                <div class="form-group">
                    <label><b>Route Name</b> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $routeRecord->name ?? '') }}">
                    <span class="error-message text-danger"></span>
                </div>

                <div class="form-group">
                    <label><b>Vehicle</b> <span class="text-danger">*</span></label>
                    <select class="form-control" name="bus_id" id="bus_id">
                        <option value="">Select Vehicle</option>
                        @foreach ($buses as $bus)
                            <option value="{{ $bus->id }}" {{ (int) old('bus_id', $routeRecord->bus_id ?? 0) === (int) $bus->id ? 'selected' : '' }}>
                                {{ $bus->vehicle_number }}
                            </option>
                        @endforeach
                    </select>
                    <span class="error-message text-danger"></span>
                </div>

                <div class="form-group">
                    <label><b>Driver</b> <span class="text-danger">*</span></label>
                    <select class="form-control" name="driver_id" id="driver_id">
                        <option value="">Select Driver</option>
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ (int) old('driver_id', $routeRecord->driver_id ?? 0) === (int) $driver->id ? 'selected' : '' }}>
                                {{ $driver->driver_name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="error-message text-danger"></span>
                </div>

                <div class="route-builder-layout">
                    <div class="route-directions-sidebar">
                        <div class="route-directions-header">
                            <h5 class="route-directions-title">Route Points</h5>
                            <div class="route-directions-subtitle">Search, reorder destinations, or pick directly from map.</div>
                        </div>

                        <div class="route-directions-body">
                            <div class="route-direction-row route-direction-row-start">
                                <div class="route-direction-marker-col">
                                    <div class="route-direction-marker"></div>
                                </div>
                                <div class="route-direction-card route-search-wrap">
                                    <div class="route-direction-inputbar">
                                        <input type="text" class="form-control route-direction-input" id="startPointInput" placeholder="Choose starting point" autocomplete="off">
                                        <div class="route-direction-actions">
                                            <button type="button" class="route-direction-btn" id="startPointMapBtn" title="Pick on map">+</button>
                                            <button type="button" class="route-direction-btn" id="startPointClearBtn" title="Clear">x</button>
                                        </div>
                                    </div>
                                    <div class="route-point-meta d-none" id="startPointMeta"></div>
                                    <div class="route-search-results list-group d-none" id="startPointResults"></div>
                                </div>
                            </div>

                            <div id="pickupPointsContainer"></div>

                            <div class="route-direction-row route-direction-row-end">
                                <div class="route-direction-marker-col">
                                    <div class="route-direction-marker"></div>
                                </div>
                                <div class="route-direction-card route-search-wrap">
                                    <div class="route-direction-inputbar">
                                        <input type="text" class="form-control route-direction-input" id="endPointInput" placeholder="Choose ending point" autocomplete="off">
                                        <div class="route-direction-actions">
                                            <button type="button" class="route-direction-btn" id="endPointMapBtn" title="Pick on map">+</button>
                                            <button type="button" class="route-direction-btn" id="endPointClearBtn" title="Clear">x</button>
                                        </div>
                                    </div>
                                    <div class="route-point-meta d-none" id="endPointMeta"></div>
                                    <div class="route-search-results list-group d-none" id="endPointResults"></div>
                                </div>
                            </div>

                            <div class="route-direction-row route-direction-row-add d-none" id="addDestinationRow">
                                <div class="route-direction-marker-col">
                                    <div class="route-direction-marker"></div>
                                </div>
                                <div>
                                    <button type="button" class="route-direction-add-btn" id="addPickupPointBtn">Add destination</button>
                                </div>
                            </div>

                            <div class="route-options-panel">
                                <h6 class="route-options-title">Route Options</h6>
                                <div id="routeOptionsContainer" class="route-options-list">
                                    <div class="route-option-empty">Add start and end points to see distance and time.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="route-map-panel">
                        <div class="route-map-toolbar">
                            <div class="route-map-toolbar-text" id="routeMapSelectionStatus">Search a place or click the add buttons, then choose points on map.</div>
                            <div class="d-flex gap-2 flex-wrap justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="fitRouteBtn">Focus Route</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllRoutePointsBtn">Clear All</button>
                            </div>
                        </div>
                        <div id="routeBuilderMap"></div>
                    </div>
                </div>

                <input type="hidden" name="route_json" id="route_json">

                <div class="mt-3">
                    <button type="button" class="btn btn-primary" id="{{ $submitButtonId }}">{{ $submitButtonText }}</button>
                    <a href="{{ $routesIndexUrl }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/route-builder.js') }}?v={{ filemtime(public_path('js/route-builder.js')) }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.initRouteBuilder({
            formId: @json($formId),
            mapId: 'routeBuilderMap',
            routeJsonInputId: 'route_json',
            submitButtonId: @json($submitButtonId),
            clearAllButtonId: 'clearAllRoutePointsBtn',
            fitRouteButtonId: 'fitRouteBtn',
            addPickupButtonId: 'addPickupPointBtn',
            pickupsContainerId: 'pickupPointsContainer',
            addDestinationRowId: 'addDestinationRow',
            routeOptionsContainerId: 'routeOptionsContainer',
            mapSelectionStatusId: 'routeMapSelectionStatus',
            startPointPrefix: 'startPoint',
            endPointPrefix: 'endPoint',
            submitUrl: @json($routesActionUrl),
            indexUrl: @json($routesIndexUrl),
            routePreviewUrl: @json(filled(config('services.google_maps.api_key')) ? ($routePreviewUrl ?? null) : null),
            googleMapsApiKey: @json(filled(config('services.google_maps.api_key')) ? config('services.google_maps.api_key') : null),
            csrfToken: @json(csrf_token()),
            initialRouteJson: @json($routeRecord->route_json ?? null),
            loadingText: @json($loadingText),
            successText: @json($successText)
        });
    });
</script>
