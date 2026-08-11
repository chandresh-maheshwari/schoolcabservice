@php
    $addChildModalId = $addChildModalId ?? 'addChildModal';
    $addChildTriggerId = $addChildTriggerId ?? ($addChildModalId . 'Trigger');
    $addChildParentId = isset($addChildParentId) && $addChildParentId ? (int) $addChildParentId : null;
    $addChildCurrentUrl = url()->full();
    $addChildSchoolSlug = request()->route('schoolSlug');
    $addChildIsSchoolUser = !empty($isSchoolUser);
    $addChildDefaultSchoolId = $defaultSchoolId ?? null;
    $addChildDefaultSchoolName = $defaultSchoolName ?? null;
    $addChildSchoolData = $schoolData ?? collect();
    $addChildRouteData = $routeData ?? collect();
    $addChildStopPickData = $stopPickData ?? collect();
    $addChildTransportOptions = collect($addChildStopPickData ?? [])->map(function ($stop) {
        return [
            'id' => (int) $stop->id,
            'route_id' => (int) $stop->route_id,
            'pickup_name' => (string) ($stop->pickup_name ?? ''),
            'stop_name' => (string) ($stop->stop_name ?? ''),
        ];
    })->values();
    $addChildRoutePointOptions = collect($addChildRouteData)
        ->mapWithKeys(function ($route) {
            $routeJson = is_array($route->route_json ?? null) ? $route->route_json : [];
            $points = collect();

            $appendPoint = function ($point, string $fallbackType) use ($points) {
                if (!is_array($point)) {
                    return;
                }

                $name = trim((string) ($point['name'] ?? $point['address'] ?? ''));
                if ($name === '') {
                    return;
                }

                $points->push([
                    'name' => $name,
                    'type' => strtolower(trim($fallbackType)) ?: 'pickup',
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
    $addChildTransportRouteMap = $addChildTransportOptions
        ->groupBy('route_id')
        ->map(function ($items) {
            return $items
                ->groupBy(function ($item) {
                    return strtolower(trim((string) ($item['pickup_name'] ?? ''))) . '|' .
                        strtolower(trim((string) ($item['stop_name'] ?? '')));
                })
                ->map(fn ($duplicateItems) => $duplicateItems->first())
                ->values()
                ->all();
        })
        ->all();
@endphp

<style>
    #{{ $addChildModalId }} .modal-dialog {
        max-width: 720px;
        margin: 1.25rem auto;
    }

    #{{ $addChildModalId }} .modal-content {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 24px 64px rgba(15, 23, 42, 0.22);
    }

    #{{ $addChildModalId }} .modal-header {
        background: linear-gradient(180deg, #f8fbff 0%, #f4f7fb 100%);
        border-bottom: 1px solid #e8eef6;
        padding: 16px 18px 14px;
        align-items: flex-start;
    }

    #{{ $addChildModalId }} .add-child-modal-header-text {
        min-width: 0;
    }

    #{{ $addChildModalId }} .modal-title {
        color: #2d336b;
        font-weight: 800;
        font-size: 22px;
        line-height: 1.1;
    }

    #{{ $addChildModalId }} .modal-body {
        max-height: none;
        overflow-y: visible;
        padding: 14px 18px 10px;
    }

    #{{ $addChildModalId }} .modal-footer {
        padding: 10px 18px 14px;
        border-top: 1px solid #e8eef6;
        background: #fcfdff;
    }

    #{{ $addChildModalId }} .add-child-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 14px;
    }

    #{{ $addChildModalId }} .add-child-modal-grid .form-group {
        margin-bottom: 0;
    }

    #{{ $addChildModalId }} .add-child-modal-grid .form-group label {
        margin-bottom: 6px;
        font-size: 14px;
        font-weight: 700;
    }

    #{{ $addChildModalId }} .add-child-modal-grid .form-control {
        min-height: 40px;
        padding-top: 8px;
        padding-bottom: 8px;
        border-radius: 12px;
        border-color: #d7e0ec;
        box-shadow: none;
    }

    #{{ $addChildModalId }} .add-child-modal-grid .form-group--full {
        grid-column: 1 / -1;
    }

    #{{ $addChildModalId }} .add-child-modal-grid .form-control:focus {
        border-color: #90a9df;
        box-shadow: 0 0 0 3px rgba(45, 51, 107, 0.10);
    }

    #{{ $addChildModalId }} .add-child-modal-panel {
        grid-column: 1 / -1;
        padding: 12px;
        border: 1px solid #e6edf6;
        border-radius: 16px;
        background: #fbfdff;
    }

    #{{ $addChildModalId }} .add-child-modal-panel-title {
        margin-bottom: 8px;
        color: #2d336b;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    #{{ $addChildModalId }} .add-child-modal-panel-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 16px;
    }

    #{{ $addChildModalId }} .add-child-upload-card {
        min-height: 118px;
        padding: 12px;
        border: 1px dashed #cfd9e8;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }

    #{{ $addChildModalId }} .add-child-upload-title {
        margin-bottom: 4px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 800;
        line-height: 1.35;
    }

    #{{ $addChildModalId }} .add-child-upload-note {
        margin-bottom: 8px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.45;
    }

    #{{ $addChildModalId }} .add-child-upload-btn {
        min-height: 38px;
        padding: 8px 14px;
        border-radius: 10px;
        background: #2d336b;
        border-color: #2d336b;
        font-weight: 700;
    }

    #{{ $addChildModalId }} .add-child-file-name {
        margin-top: 10px;
        color: #475569;
        font-size: 12px;
        word-break: break-word;
    }

    #{{ $addChildModalId }} .add-child-radio-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 2px;
    }

    #{{ $addChildModalId }} .add-child-radio-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border: 1px solid #d7e0ec;
        border-radius: 999px;
        background: #fff;
        font-weight: 600;
        color: #334155;
    }

    #{{ $addChildModalId }} .add-child-actions .btn {
        min-width: 88px;
        border-radius: 10px;
        font-weight: 700;
    }

    #{{ $addChildModalId }} .btn-close {
        opacity: 0.6;
    }

    #{{ $addChildModalId }} .dlt_btn_div img {
        width: 82px !important;
        height: 82px !important;
        margin-top: 8px !important;
    }

    @media (max-width: 767.98px) {
        #{{ $addChildModalId }} .modal-dialog {
            max-width: calc(100vw - 18px);
            margin: 0.5rem auto;
        }

        #{{ $addChildModalId }} .modal-body {
            padding: 12px 14px 10px;
        }

        #{{ $addChildModalId }} .modal-header,
        #{{ $addChildModalId }} .modal-footer {
            padding-left: 14px;
            padding-right: 14px;
        }

        #{{ $addChildModalId }} .add-child-modal-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        #{{ $addChildModalId }} .add-child-modal-panel-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<button type="button" class="btn btn-primary" id="{{ $addChildTriggerId }}" data-bs-toggle="modal"
    data-bs-target="#{{ $addChildModalId }}" style="background-color:#2d336b;">
    Add New Child
</button>

<div class="modal fade" id="{{ $addChildModalId }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="add-child-modal-header-text">
                    <h5 class="modal-title">Add New Child</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="{{ $addChildModalId }}Form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $addChildParentId }}">
                    <div class="add-child-modal-grid">
                    <div class="form-group form-group--full">
                        <label>Child Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="{{ $addChildModalId }}_child_name" name="child_name" autocomplete="off">
                    </div>

                    <div class="add-child-modal-panel">
                        <div class="add-child-modal-panel-title">Transport Details</div>
                        <div class="add-child-modal-panel-grid">
                            <div class="form-group">
                                <label>School Name <span style="color:red;">*</span></label>
                                @if ($addChildIsSchoolUser && !empty($addChildDefaultSchoolId))
                                    <input type="hidden" name="school_id" id="{{ $addChildModalId }}_school_id" value="{{ $addChildDefaultSchoolId }}">
                                    <input type="text" class="form-control" value="{{ $addChildDefaultSchoolName ?? 'School' }}" disabled>
                                @else
                                    <select class="form-control" name="school_id" id="{{ $addChildModalId }}_school_id">
                                        <option value="">Select School Name</option>
                                        @foreach ($addChildSchoolData as $type)
                                            <option value="{{ $type->id }}">{{ $type->school_name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <div class="form-group">
                                <label>Route Name <span style="color:red;">*</span></label>
                                <select class="form-control" name="route_id" id="{{ $addChildModalId }}_route_id">
                                    <option value="">Select Route Name</option>
                                    @foreach ($addChildRouteData as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Start Point</label>
                                <input type="text" class="form-control" id="{{ $addChildModalId }}_start_point_display" readonly
                                    placeholder="Select Route first">
                            </div>

                            <div class="form-group">
                                <label>Pickup Point <span style="color:red;">*</span></label>
                                <select class="form-control" name="pickup_name" id="{{ $addChildModalId }}_pickup_point_select">
                                    <option value="">Select Route first</option>
                                </select>
                            </div>

                            <input type="hidden" id="{{ $addChildModalId }}_pickup_name">

                            <div class="form-group">
                                <label>Stop Name</label>
                                <input type="text" class="form-control" id="{{ $addChildModalId }}_stop_name_display" readonly
                                    placeholder="Select Route first">
                                <input type="hidden" name="stop_name" id="{{ $addChildModalId }}_stop_name">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Gender <span style="color:red;">*</span></label>
                        <div id="{{ $addChildModalId }}_genderGroup" class="add-child-radio-group">
                            <label class="add-child-radio-chip">
                                <input type="radio" name="gender" value="Male">
                                <span>Male</span>
                            </label>
                            <label class="add-child-radio-chip">
                                <input type="radio" name="gender" value="Female">
                                <span>Female</span>
                            </label>
                            <label class="add-child-radio-chip">
                                <input type="radio" name="gender" value="Other">
                                <span>Other</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Date Of Birth <span style="color:red;">*</span></label>
                        <input type="date" class="form-control" name="date_of_birth" id="{{ $addChildModalId }}_date_of_birth" max="{{ now()->toDateString() }}">
                    </div>

                    <div class="add-child-upload-card">
                        <div class="add-child-upload-title">Profile Image <span style="color:red;">*</span></div>
                        <div class="add-child-upload-note">Minimum 636 x 424 pixels.</div>
                        <button type="button" class="btn btn-primary add-child-upload-btn" id="{{ $addChildModalId }}_ImageBtn"
                            onclick="document.getElementById('{{ $addChildModalId }}_image').click();">Upload Image</button>
                        <input type="file" id="{{ $addChildModalId }}_image" name="image" accept="image/*" style="display:none;">
                        <div class="add-child-file-name" id="{{ $addChildModalId }}_imageName"></div>
                        <div class="dlt_btn_div" id="{{ $addChildModalId }}_imageWrap" style="display:none;">
                            <img id="{{ $addChildModalId }}_imagePreview" src="#" alt="Image Preview"
                                style="display:none; width:100px; height:100px; margin-top:10px;">
                        </div>
                    </div>

                    <div class="add-child-upload-card">
                        <div class="add-child-upload-title">Aadhar Card Image / PDF <span style="color:red;">*</span></div>
                        <div class="add-child-upload-note">Image minimum 800 x 600 pixels or upload a PDF.</div>
                        <button type="button" class="btn btn-primary add-child-upload-btn" id="{{ $addChildModalId }}_ImageBtn1"
                            onclick="document.getElementById('{{ $addChildModalId }}_child_adhaar_card_image').click();">Upload File</button>
                        <input type="file" id="{{ $addChildModalId }}_child_adhaar_card_image" name="child_adhaar_card_image"
                            accept="image/*,application/pdf" style="display:none;">
                        <div class="add-child-file-name" id="{{ $addChildModalId }}_imageName1"></div>
                        <div class="dlt_btn_div" id="{{ $addChildModalId }}_imageWrap1" style="display:none;">
                            <img id="{{ $addChildModalId }}_imagePreview1" src="#" alt="Image Preview"
                                style="display:none; width:100px; height:100px; margin-top:10px;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Class <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="{{ $addChildModalId }}_class" name="class"
                            autocomplete="off" oninput="this.value = this.value < 1 ? '' : this.value">
                    </div>

                    <div class="form-group">
                        <label>Section <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="{{ $addChildModalId }}_section" name="section" autocomplete="off">
                    </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer add-child-actions">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="{{ $addChildModalId }}SubmitBtn">Save Child</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const modalId = @json($addChildModalId);
        const routePointsMap = @json($addChildRoutePointOptions);
        const transportRouteMap = @json($addChildTransportRouteMap);
        const currentUrl = @json($addChildCurrentUrl);
        const form = document.getElementById(modalId + 'Form');
        const routeSelect = document.getElementById(modalId + '_route_id');
        const schoolSelect = document.getElementById(modalId + '_school_id');
        const startPointDisplay = document.getElementById(modalId + '_start_point_display');
        const pickupPointSelect = document.getElementById(modalId + '_pickup_point_select');
        const pickupNameInput = document.getElementById(modalId + '_pickup_name');
        const stopNameDisplay = document.getElementById(modalId + '_stop_name_display');
        const stopNameInput = document.getElementById(modalId + '_stop_name');
        const imageInput = document.getElementById(modalId + '_image');
        const imageName = document.getElementById(modalId + '_imageName');
        const imagePreview = document.getElementById(modalId + '_imagePreview');
        const imageWrap = document.getElementById(modalId + '_imageWrap');
        const adhaarInput = document.getElementById(modalId + '_child_adhaar_card_image');
        const adhaarName = document.getElementById(modalId + '_imageName1');
        const adhaarPreview = document.getElementById(modalId + '_imagePreview1');
        const adhaarWrap = document.getElementById(modalId + '_imageWrap1');
        const submitBtn = document.getElementById(modalId + 'SubmitBtn');
        const triggerBtn = document.getElementById(@json($addChildTriggerId));
        const $modal = window.jQuery ? window.jQuery('#' + modalId) : null;
        const modalEl = document.getElementById(modalId);
        let transportWatcherId = null;
        let lastObservedRouteValue = null;
        let lastObservedPickupValue = null;

        function getModalInstance() {
            if (!modalEl || !window.bootstrap || !window.bootstrap.Modal) {
                return null;
            }

            return window.bootstrap.Modal.getOrCreateInstance(modalEl, {
                backdrop: 'static',
                keyboard: false
            });
        }

        function getRoutePoints(routeId) {
            const normalizedRouteId = String(parseInt(routeId, 10) || 0);
            return Array.isArray(routePointsMap[normalizedRouteId]) ? routePointsMap[normalizedRouteId] : [];
        }

        function resetTransportFields() {
            startPointDisplay.value = '';
            pickupPointSelect.innerHTML = '<option value="">Select Route first</option>';
            pickupPointSelect.disabled = true;
            stopNameDisplay.value = '';
            pickupNameInput.value = '';
            stopNameInput.value = '';
            startPointDisplay.placeholder = 'Select Route first';
            stopNameDisplay.placeholder = 'Select Route first';
        }

        function syncStopNameFromPickupOption() {
            const selectedOption = pickupPointSelect.options[pickupPointSelect.selectedIndex];
            const selectedStopName = selectedOption && selectedOption.dataset
                ? String(selectedOption.dataset.stopName || '').trim()
                : '';

            if (selectedStopName && !stopNameDisplay.value.trim()) {
                stopNameDisplay.value = selectedStopName;
            }
        }

        function setSelectedPickup() {
            const selectedOption = pickupPointSelect.options[pickupPointSelect.selectedIndex];
            const pickupId = pickupPointSelect.value || '';
            const selectedStopName = selectedOption && selectedOption.dataset
                ? String(selectedOption.dataset.stopName || '').trim()
                : '';

            pickupNameInput.value = pickupId;
            stopNameInput.value = pickupId;
            stopNameDisplay.value = selectedStopName;
            syncStopNameFromPickupOption();
        }

        function renderTransportDetails(routeId) {
            const normalizedRouteId = parseInt(routeId, 10) || 0;
            const routePoints = getRoutePoints(normalizedRouteId);
            const transportItems = Array.isArray(transportRouteMap[String(normalizedRouteId)])
                ? transportRouteMap[String(normalizedRouteId)]
                : [];

            resetTransportFields();

            if (normalizedRouteId <= 0) {
                return;
            }

            pickupPointSelect.innerHTML = '<option value="">Select Pickup Point</option>';
            pickupPointSelect.disabled = false;

            transportItems.forEach(item => {
                const pickupName = String(item.pickup_name || '').trim();
                if (!pickupName) return;

                const option = document.createElement('option');
                option.value = String(item.id || '');
                option.textContent = pickupName;
                option.dataset.stopName = String(item.stop_name || '').trim();
                pickupPointSelect.appendChild(option);
            });

            if (pickupPointSelect.options.length > 1) {
                pickupPointSelect.selectedIndex = 1;
                setSelectedPickup();
            } else {
                pickupPointSelect.innerHTML = '<option value="">No pickup points available</option>';
                pickupPointSelect.disabled = true;
            }

            routePoints.forEach(point => {
                const pointType = String(point.type || '').toLowerCase();
                const pointName = String(point.name || '').trim();

                if (!pointName) return;
                if (pointType === 'start') startPointDisplay.value = pointName;
                if (pointType === 'end' && !stopNameDisplay.value) stopNameDisplay.value = pointName;
            });

            if (!startPointDisplay.value) {
                startPointDisplay.placeholder = 'No start point available';
            }
            if (!stopNameDisplay.value) {
                stopNameDisplay.placeholder = 'No stop point available';
            }

            syncStopNameFromPickupOption();
        }

        function previewSelectedFile(input, nameTarget, previewTarget, wrapTarget) {
            const file = input.files && input.files[0] ? input.files[0] : null;
            if (!file) {
                nameTarget.textContent = '';
                previewTarget.style.display = 'none';
                wrapTarget.style.display = 'none';
                return;
            }

            nameTarget.textContent = file.name;
            wrapTarget.style.display = 'block';

            if ((file.type || '').toLowerCase() === 'application/pdf') {
                previewTarget.removeAttribute('src');
                previewTarget.style.display = 'none';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                previewTarget.src = event.target && event.target.result ? event.target.result : '#';
                previewTarget.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }

        function clearErrors() {
            if (!window.jQuery) return;
            window.jQuery(form).find('.error-message').remove();
        }

        function showError(selector, message) {
            if (!window.jQuery) return;
            window.jQuery(selector).after('<span class="error-message" style="color:red;">' + message + '</span>');
        }

        function resetModalForm() {
            form.reset();
            resetTransportFields();
            imageName.textContent = '';
            imagePreview.removeAttribute('src');
            imagePreview.style.display = 'none';
            imageWrap.style.display = 'none';
            adhaarName.textContent = '';
            adhaarPreview.removeAttribute('src');
            adhaarPreview.style.display = 'none';
            adhaarWrap.style.display = 'none';
            clearErrors();
        }

        function syncTransportWatcher(forceRender) {
            if (!routeSelect || !pickupPointSelect) {
                return;
            }

            const currentRouteValue = String(routeSelect.value || '');
            const currentPickupValue = String(pickupPointSelect.value || '');

            if (forceRender || currentRouteValue !== lastObservedRouteValue) {
                lastObservedRouteValue = currentRouteValue;
                renderTransportDetails(currentRouteValue);
            }

            if (forceRender || currentPickupValue !== lastObservedPickupValue) {
                lastObservedPickupValue = currentPickupValue;
                setSelectedPickup();
            }
        }

        function startTransportWatcher(forceRender) {
            syncTransportWatcher(!!forceRender);

            if (transportWatcherId !== null) {
                return;
            }

            transportWatcherId = window.setInterval(function () {
                syncTransportWatcher(false);
            }, 200);
        }

        function stopTransportWatcher() {
            if (transportWatcherId !== null) {
                window.clearInterval(transportWatcherId);
                transportWatcherId = null;
            }

            lastObservedRouteValue = null;
            lastObservedPickupValue = null;
        }

        if (routeSelect) {
            routeSelect.addEventListener('change', function () {
                renderTransportDetails(this.value);
            });

            routeSelect.addEventListener('input', function () {
                renderTransportDetails(this.value);
            });
        }

        if (pickupPointSelect) {
            pickupPointSelect.addEventListener('change', function () {
                setSelectedPickup();
            });
        }

        if (imageInput) {
            imageInput.addEventListener('change', function () {
                previewSelectedFile(imageInput, imageName, imagePreview, imageWrap);
            });
        }

        if (adhaarInput) {
            adhaarInput.addEventListener('change', function () {
                previewSelectedFile(adhaarInput, adhaarName, adhaarPreview, adhaarWrap);
            });
        }

        if (modalEl) {
            modalEl.addEventListener('shown.bs.modal', function () {
                startTransportWatcher(true);
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                stopTransportWatcher();
                resetModalForm();
            });
        }

        if (window.jQuery) {
            window.jQuery(document)
                .off('change.addChildRoute.' + modalId, '#' + modalId + '_route_id')
                .on('change.addChildRoute.' + modalId, '#' + modalId + '_route_id', function () {
                    renderTransportDetails(this.value);
                });

            window.jQuery(document)
                .off('shown.bs.modal.addChild.' + modalId, '#' + modalId)
                .on('shown.bs.modal.addChild.' + modalId, '#' + modalId, function () {
                    startTransportWatcher(true);
                });
        }

        if (triggerBtn) {
            triggerBtn.addEventListener('click', function () {
                startTransportWatcher(true);
                const modalInstance = getModalInstance();
                if (modalInstance) {
                    modalInstance.show();
                }
            });
        }

        if (submitBtn) {
            submitBtn.addEventListener('click', function () {
                clearErrors();

                const formData = new FormData(form);
                let isValid = true;

                if (!formData.get('child_name')) {
                    showError('#' + modalId + '_child_name', 'Child Name is required');
                    isValid = false;
                }

                if (schoolSelect && !schoolSelect.value) {
                    showError('#' + modalId + '_school_id', 'School Name is required');
                    isValid = false;
                }

                if (!formData.get('route_id')) {
                    showError('#' + modalId + '_route_id', 'Route Name is required');
                    isValid = false;
                }

                if (!formData.get('pickup_name')) {
                    showError('#' + modalId + '_pickup_point_select', 'Pickup Point is required');
                    isValid = false;
                }

                if (!stopNameDisplay.value.trim()) {
                    showError('#' + modalId + '_stop_name_display', 'Stop Name is required');
                    isValid = false;
                }

                if (!formData.get('gender')) {
                    showError('#' + modalId + '_genderGroup', 'Gender is required');
                    isValid = false;
                }

                if (!formData.get('date_of_birth')) {
                    showError('#' + modalId + '_date_of_birth', 'Date Of Birth is required');
                    isValid = false;
                } else {
                    const selectedDob = formData.get('date_of_birth');
                    const today = '{{ now()->toDateString() }}';
                    if (selectedDob > today) {
                        showError('#' + modalId + '_date_of_birth', 'Future Date Of Birth is not allowed');
                        isValid = false;
                    }
                }

                if (!formData.get('image') || !formData.get('image').name) {
                    showError('#' + modalId + '_ImageBtn', 'Image is required.');
                    isValid = false;
                }

                if (!formData.get('child_adhaar_card_image') || !formData.get('child_adhaar_card_image').name) {
                    showError('#' + modalId + '_ImageBtn1', 'Child Adhaar Card Image is required.');
                    isValid = false;
                }

                if (!formData.get('class')) {
                    showError('#' + modalId + '_class', 'Class is required');
                    isValid = false;
                }

                if (!formData.get('section')) {
                    showError('#' + modalId + '_section', 'Section is required');
                    isValid = false;
                }

                if (!isValid) {
                    return;
                }

                Swal.fire({
                    title: 'Please wait...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(@json(route('api.child.store')), {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    }
                })
                    .then(async res => {
                        let data;
                        try {
                            data = await res.json();
                        } catch (e) {
                            throw 'Invalid server response';
                        }

                        if (!res.ok || data.success === false) {
                            let errorMsg = data.message || 'Something went wrong';
                            if (data.errors) {
                                errorMsg = Object.values(data.errors).flat().join('<br>');
                            }
                            throw errorMsg;
                        }

                        return data;
                    })
                    .then(data => {
                        Swal.close();
                        notify('success', data.message || 'Child created successfully!');
                        const modalInstance = getModalInstance();
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                        setTimeout(() => {
                            if (typeof window.__childModuleLoadPage === 'function') {
                                window.__childModuleLoadPage(currentUrl);
                            } else {
                                window.location.reload();
                            }
                        }, 350);
                    })
                    .catch(error => {
                        Swal.close();
                        notify(
                            'error',
                            typeof error === 'string'
                                ? error
                                : (error.message || 'An unexpected error occurred.')
                        );
                    });
            });
        }
    })();
</script>
