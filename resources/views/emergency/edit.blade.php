@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    @php
        $schoolSlug = request()->route('schoolSlug');
        $dashboardRoute = $schoolSlug ? route('school.dashboard', ['schoolSlug' => $schoolSlug]) : route('admin_layout.index');
        $indexRoute = $schoolSlug ? route('school.emergency.index', ['schoolSlug' => $schoolSlug]) : route('emergency.index');
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
                    <input type="hidden" name="handover_action" id="handover_action" value="">

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

                    <div class="alert alert-info">
                        Use the replacement fields below only when this emergency happened during a running trip and the trip must continue with a new driver.
                    </div>

                    @if (($runningTripState['has_running_trip'] ?? false) === true)
                        <div class="alert alert-warning">
                            <strong>Running Trip Handover Status:</strong>
                            @if (($runningTripState['stage'] ?? '') === 'active')
                                Replacement not assigned yet. First assign a replacement vehicle and driver.
                            @elseif (($runningTripState['stage'] ?? '') === 'assigned')
                                Replacement assigned. After the bus reaches the breakdown point, click <strong>Mark Arrived</strong>.
                            @elseif (($runningTripState['stage'] ?? '') === 'arrived')
                                Replacement bus has reached the breakdown point. Click <strong>Continue Trip</strong> to transfer the running trip.
                            @else
                                Running trip state detected.
                            @endif
                        </div>
                    @endif

                    <div class="form-group">
                        <label>Replacement Vehicle</label>
                        <select class="form-control" id="replacement_vehicle_id" name="replacement_vehicle_id">
                            <option value="">Select Replacement Vehicle</option>
                            @foreach ($replacementVehicles as $replacementVehicle)
                                <option value="{{ $replacementVehicle->id }}"
                                    data-driver-id="{{ (int) ($replacementVehicle->driver_id ?? 0) }}">
                                    {{ $replacementVehicle->vehicle_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Replacement Driver</label>
                        <select class="form-control" id="replacement_driver_id" name="replacement_driver_id">
                            <option value="">Select Replacement Driver</option>
                            @foreach ($replacementDrivers as $replacementDriver)
                                <option value="{{ $replacementDriver->id }}"
                                    data-vehicle-id="{{ (int) ($replacementDriver->vehicle_id ?? 0) }}">
                                    {{ $replacementDriver->driver_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        @if (($runningTripState['has_running_trip'] ?? false) === true && ($runningTripState['stage'] ?? '') === 'assigned')
                            <button type="button" class="btn btn-info" id="markArrivedBtn">Mark Arrived</button>
                        @endif

                        @if (($runningTripState['has_running_trip'] ?? false) === true && ($runningTripState['stage'] ?? '') === 'arrived')
                            <button type="button" class="btn btn-success" id="continueTripBtn">Continue Trip</button>
                        @endif
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ $indexRoute }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        CKEDITOR.replace('description', {
            readOnly: true
        });

        const hasRunningTrip = @json(($runningTripState['has_running_trip'] ?? false) === true);
        const runningTripStage = @json((string) ($runningTripState['stage'] ?? 'none'));

        function submitEmergencyForm(handoverAction) {
            $('.error-message').remove();

            let formData = new FormData(document.getElementById('emergencyForm'));
            formData.set('handover_action', handoverAction || '');
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('status')) {
                showError('#status', 'Emergency Status is required');
            }

            const replacementVehicleId = (formData.get('replacement_vehicle_id') || '').trim();
            const replacementDriverId = (formData.get('replacement_driver_id') || '').trim();
            if (
                handoverAction === 'assign_replacement' &&
                ((replacementVehicleId && !replacementDriverId) || (!replacementVehicleId && replacementDriverId))
            ) {
                if (!replacementVehicleId) showError('#replacement_vehicle_id', 'Replacement vehicle is required');
                if (!replacementDriverId) showError('#replacement_driver_id', 'Replacement driver is required');
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
        }

        $('#updateBtn').on('click', function() {
            const replacementVehicleId = ($('#replacement_vehicle_id').val() || '').toString().trim();
            const replacementDriverId = ($('#replacement_driver_id').val() || '').toString().trim();
            const shouldAssignReplacement = hasRunningTrip &&
                runningTripStage === 'active' &&
                replacementVehicleId !== '' &&
                replacementDriverId !== '';

            submitEmergencyForm(shouldAssignReplacement ? 'assign_replacement' : '');
        });

        $('#markArrivedBtn').on('click', function() {
            submitEmergencyForm('mark_arrived');
        });

        $('#continueTripBtn').on('click', function() {
            submitEmergencyForm('continue_trip');
        });

        $('#status').on('change', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });

        $('#additional_comment').on('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });

        $('#replacement_vehicle_id').on('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const linkedDriverId = selectedOption ? selectedOption.getAttribute('data-driver-id') : '';
            if (linkedDriverId) {
                $('#replacement_driver_id').val(linkedDriverId);
            }
            $(this).closest('.form-group').find('.error-message').remove();
        });

        $('#replacement_driver_id').on('change', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
    </script>
@endsection
