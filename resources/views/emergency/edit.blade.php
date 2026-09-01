@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    @php
        $schoolSlug = request()->route('schoolSlug');
        $dashboardRoute = $schoolSlug ? route('school.dashboard', ['schoolSlug' => $schoolSlug]) : route('admin_layout.index');
        $indexRoute = $schoolSlug ? route('school.emergency.index', ['schoolSlug' => $schoolSlug]) : route('emergency.index');
        $vehicleDriversBaseUrl = $schoolSlug ? url($schoolSlug . '/routes/vehicle') : url('admin/routes/vehicle');
    @endphp

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item">
                            <a class="breadcrumbLink" href="{{ $dashboardRoute }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Edit Emergency
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Emergency Details</h4>
            </div>

            <div class="card-body">
                <form id="emergencyForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Driver Name --}}
                    <div class="form-group">
                        <label> Driver Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control"
                            value="{{ optional($drivers->firstWhere('id', $emergency->driver_id))->driver_name ?? '-' }}"
                            readonly>
                    </div>

                    {{-- Vehicle Number --}}
                    <div class="form-group">
                        <label>Vehicle Number <span style="color:red;">*</span></label>
                        <input type="text" class="form-control"
                            value="{{ optional($vehicles->firstWhere('id', $emergency->vehicle_id))->vehicle_number ?? '-' }}"
                            readonly>
                    </div>

                    {{-- Reported By --}}
                    <div class="form-group">
                        <label>Reported By <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" value="{{ ucfirst($emergency->reported_by ?? '-') }}" readonly>
                    </div>

                    {{-- Emergency Type --}}
                    <div class="form-group">
                        <label>Emergency Type <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" value="{{ $emergency->emergency_type ?? '-' }}" readonly>
                    </div>

                    {{-- Description --}}
                    <div class="form-group">
                        <label>Description <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ $emergency->description ?? '' }}</textarea>
                    </div>

                    {{-- Contact Number --}}
                    <div class="form-group">
                        <label>Contact Number <span style="color:red;">*</span></label>
                        <input type="tel" class="form-control" id="contact_number" name="contact_number"
                            value="{{ old('contact_number', $emergency->contact_number) }}" minlength="10" maxlength="11"
                            pattern="[0-9]{10,11}" required autocomplete="off" readonly>
                    </div>

                    <div class="form-group">
                        <label>Emergency Status <span style="color:red;">*</span></label>
                        <select class="form-control" id="status" name="status">
                            <option value="1" {{ (int) ($emergency->status ?? 0) === 1 ? 'selected' : '' }}>In Process</option>
                            <option value="0" {{ (int) ($emergency->status ?? 0) === 0 ? 'selected' : '' }}>Resolved</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Additional Comment</label>
                        <textarea class="form-control" id="additional_comment" name="additional_comment" rows="4"
                            placeholder="Enter additional comment">{{ old('additional_comment', $emergency->additional_comment ?? '') }}</textarea>
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ $indexRoute }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>

        <div class="card mt-4" id="handover-workflow">
            <div class="card-header">
                <h4 class="about-us-create-header mb-0">Replacement Workflow</h4>
            </div>
            <div class="card-body">
                @if ((int) ($emergency->status ?? 0) !== 1)
                    <div class="alert alert-info mb-0">
                        This emergency is already resolved. Re-open it if you need to continue the replacement workflow.
                    </div>
                @elseif ((int) ($emergency->vehicle_id ?? 0) <= 0)
                    <div class="alert alert-warning mb-0">
                        This emergency does not have a linked vehicle, so replacement handover cannot be started from Laravel.
                    </div>
                @else
                    <p class="text-muted mb-3">
                        Assign a replacement vehicle and driver here. The assigned replacement driver confirms arrival and resumes the trip from their mobile app; this page shows the live handover status.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="replacement_vehicle_id" class="form-label">Replacement Vehicle</label>
                            <select class="form-control" id="replacement_vehicle_id" name="replacement_vehicle_id">
                                <option value="">Select replacement vehicle</option>
                                @foreach ($replacementVehicles as $replacementVehicle)
                                    <option value="{{ (int) $replacementVehicle->id }}"
                                        data-default-driver-id="{{ (int) ($replacementVehicle->driver_id ?? 0) }}">
                                        {{ $replacementVehicle->vehicle_number }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Current emergency vehicle is automatically excluded from this list.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="replacement_driver_id" class="form-label">Replacement Driver</label>
                            <select class="form-control" id="replacement_driver_id" name="replacement_driver_id" disabled>
                                <option value="">Select replacement driver</option>
                            </select>
                            <small class="text-muted">Driver options are loaded from the selected replacement vehicle.</small>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="button" class="btn btn-primary" id="assignReplacementBtn">
                            Assign Replacement
                        </button>
                        <button type="button" class="btn btn-outline-warning" id="markArrivedBtn" disabled>
                            Waiting for Driver Arrival
                        </button>
                        <button type="button" class="btn btn-outline-success" id="continueTripBtn" disabled>
                            Waiting for Driver Continue
                        </button>
                        <button type="button" class="btn btn-link btn-sm" id="refreshHandoverStatusBtn">
                            Refresh Status
                        </button>
                    </div>

                    <div class="alert alert-light border mt-4 mb-0">
                        <strong>Current Emergency Vehicle:</strong>
                        {{ optional($vehicles->firstWhere('id', $emergency->vehicle_id))->vehicle_number ?? '-' }}
                        <br>
                        <strong>Current Driver:</strong>
                        {{ optional($drivers->firstWhere('id', $emergency->driver_id))->driver_name ?? '-' }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        CKEDITOR.replace('description', {
            readOnly: true
        });

        const handoverApiUrl = @json(route('api.emergency.handover', $emergency->id));
        const handoverStatusApiUrl = @json(route('api.emergency.handover-status', $emergency->id));
        const vehicleDriversBaseUrl = @json($vehicleDriversBaseUrl);
        const emergencyStatusIsActive = {{ (int) ($emergency->status ?? 0) === 1 ? 'true' : 'false' }};
        const emergencyVehicleId = {{ (int) ($emergency->vehicle_id ?? 0) }};
        let handoverStatusPoller = null;
        let handoverStatusRequestInFlight = false;

        function stopHandoverStatusPolling() {
            if (handoverStatusPoller) {
                window.clearInterval(handoverStatusPoller);
                handoverStatusPoller = null;
            }
        }

        function ensureHandoverStatusNode() {
            let statusNode = document.getElementById('handoverStatusMessage');
            if (statusNode) {
                return statusNode;
            }

            const workflowCardBody = document.querySelector('#handover-workflow .card-body');
            if (!workflowCardBody) {
                return null;
            }

            statusNode = document.createElement('div');
            statusNode.id = 'handoverStatusMessage';
            statusNode.className = 'alert alert-light border mt-3 mb-0';
            workflowCardBody.appendChild(statusNode);

            return statusNode;
        }

        function setButtonState($button, enabled, activeLabel, disabledLabel = activeLabel) {
            if (!$button.length) {
                return;
            }

            if (!$button.data('active-label')) {
                $button.data('active-label', activeLabel);
            }
            if (!$button.data('disabled-label')) {
                $button.data('disabled-label', disabledLabel);
            }

            $button.prop('disabled', !enabled);
            $button.text(enabled ? ($button.data('active-label') || activeLabel) : ($button.data('disabled-label') || disabledLabel));
        }

        function applyHandoverStatus(statusPayload) {
            const stage = String(statusPayload?.stage || 'awaiting_replacement_assignment').trim();
            const message = String(statusPayload?.message || '').trim();
            const $assignButton = $('#assignReplacementBtn');
            const $arrivalButton = $('#markArrivedBtn');
            const $continueButton = $('#continueTripBtn');
            const $vehicleSelect = $('#replacement_vehicle_id');
            const $driverSelect = $('#replacement_driver_id');
            const statusNode = ensureHandoverStatusNode();

            setButtonState($assignButton, stage === 'awaiting_replacement_assignment', 'Assign Replacement', 'Replacement Assigned');

            // Arrival and trip continuation are driver-app actions. The admin page
            // only displays their state, so a page refresh cannot change the trip.
            setButtonState(
                $arrivalButton,
                false,
                'Waiting for Driver Arrival',
                stage === 'replacement_arrived' || stage === 'continued'
                    ? 'Arrival Recorded'
                    : 'Waiting for Driver Arrival'
            );
            setButtonState(
                $continueButton,
                false,
                'Waiting for Driver Continue',
                stage === 'continued' ? 'Trip Continued' : 'Waiting for Driver Continue'
            );

            if (stage === 'continued') {
                $vehicleSelect.prop('disabled', true);
                $driverSelect.prop('disabled', true);
            } else if (stage === 'replacement_assigned' || stage === 'replacement_arrived') {
                $vehicleSelect.prop('disabled', true);
                $driverSelect.prop('disabled', true);
            } else {
                $vehicleSelect.prop('disabled', false);
                if (!$vehicleSelect.val()) {
                    $driverSelect.prop('disabled', true);
                }
            }

            if (statusNode) {
                statusNode.className = stage === 'continued'
                    ? 'alert alert-success border mt-3 mb-0'
                    : stage === 'replacement_arrived'
                    ? 'alert alert-info border mt-3 mb-0'
                    : 'alert alert-light border mt-3 mb-0';
                statusNode.textContent = message || 'Replacement workflow is ready.';
            }

            if (stage === 'continued') {
                stopHandoverStatusPolling();
            }
        }

        async function fetchHandoverStatus({ silent = true } = {}) {
            if (handoverStatusRequestInFlight) {
                return;
            }

            handoverStatusRequestInFlight = true;
            try {
                const response = await fetch(handoverStatusApiUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    if (!silent) {
                        showWarningModal(payload.message || 'Unable to load replacement workflow status.');
                    }
                    return;
                }

                applyHandoverStatus(payload);
            } catch (error) {
                if (!silent) {
                    notify('error', 'Unable to refresh replacement workflow status right now.');
                }
            } finally {
                handoverStatusRequestInFlight = false;
            }
        }

        $('#updateBtn').on('click', function() {
            $('.error-message').remove();

            let formData = new FormData(document.getElementById('emergencyForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('status')) {
                showError('#status', 'Emergency Status is required');
            }

            if (!isValid) {
                return;
            }

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.emergency.update', $emergency->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'Accept': 'application/json',
                        'X-HTTP-Method-Override': 'PUT'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', data.message || 'Emergency status updated successfully!');
                        setTimeout(() => window.location.href = '{{ $indexRoute }}', 1200);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                })
                .catch(() => {
                    Swal.close();
                    notify('error', 'Something went wrong');
                });
        });

        $('#status').on('change', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });

        $('#additional_comment').on('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });

        async function loadReplacementDrivers(vehicleId, preferredDriverId = null) {
            const $driverSelect = $('#replacement_driver_id');
            $driverSelect.prop('disabled', true).html('<option value="">Loading drivers...</option>');

            if (!vehicleId) {
                $driverSelect.html('<option value="">Select replacement driver</option>');
                return;
            }

            try {
                const response = await fetch(`${vehicleDriversBaseUrl}/${encodeURIComponent(vehicleId)}/drivers`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const payload = await response.json();
                const drivers = Array.isArray(payload.drivers) ? payload.drivers : [];

                if (!drivers.length) {
                    $driverSelect.html('<option value="">No available driver found for this vehicle</option>');
                    return;
                }

                const options = ['<option value="">Select replacement driver</option>'];
                drivers.forEach((driver) => {
                    const driverId = Number(driver.id || 0);
                    const selected = preferredDriverId && Number(preferredDriverId) === driverId ? 'selected' : '';
                    options.push(`<option value="${driverId}" ${selected}>${String(driver.driver_name || 'Driver')}</option>`);
                });

                $driverSelect.html(options.join(''));
                $driverSelect.prop('disabled', false);
            } catch (error) {
                $driverSelect.html('<option value="">Unable to load drivers</option>');
                notify('error', 'Unable to load replacement drivers right now.');
            }
        }

        async function submitEmergencyHandover(action) {
            if (!emergencyStatusIsActive) {
                showWarningModal('Only active emergencies can use the replacement workflow.');
                return;
            }

            if (!emergencyVehicleId) {
                showWarningModal('Current emergency vehicle is missing for this handover.');
                return;
            }

            const formData = new FormData();
            formData.append('action', action);
            formData.append('_token', $('input[name="_token"]').val());

            if (action === 'assign_replacement') {
                const replacementVehicleId = $('#replacement_vehicle_id').val();
                const replacementDriverId = $('#replacement_driver_id').val();

                if (!replacementVehicleId || !replacementDriverId) {
                    showWarningModal('Please select both replacement vehicle and replacement driver.');
                    return;
                }

                formData.append('replacement_vehicle_id', replacementVehicleId);
                formData.append('replacement_driver_id', replacementDriverId);
            }

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch(handoverApiUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const payload = await response.json();
                Swal.close();

                if (!response.ok || !payload.success) {
                    showWarningModal(payload.message || 'Unable to update replacement handover.');
                    return;
                }

                notify('success', payload.message || 'Replacement handover updated successfully.');
                await fetchHandoverStatus({
                    silent: true
                });
            } catch (error) {
                Swal.close();
                notify('error', 'Unable to update replacement handover right now.');
            }
        }

        $('#replacement_vehicle_id').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const defaultDriverId = selectedOption.data('default-driver-id') || null;
            loadReplacementDrivers($(this).val(), defaultDriverId);
        });

        $('#assignReplacementBtn').on('click', function() {
            submitEmergencyHandover('assign_replacement');
        });

        $('#refreshHandoverStatusBtn').on('click', function() {
            fetchHandoverStatus({
                silent: false
            });
        });

        fetchHandoverStatus({
            silent: true
        });
    </script>
@endsection
