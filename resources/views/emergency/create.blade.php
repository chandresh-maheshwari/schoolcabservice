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
                            Add Emergency
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Emergency </h4>
            </div>

            <div class="card-body">
                <form id="emergencyForm" enctype="multipart/form-data">
                    @csrf

                    {{-- Package Name --}}
                    <div class="form-group">
                        <label> Driver Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="driver_id" id="driver_id">
                            <option value="">Select Driver</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}" data-vehicle-id="{{ $driver->vehicle_id }}">
                                    {{ $driver->driver_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Vehicle Number <span style="color:red;">*</span></label>
                        <select class="form-control" name="vehicle_id" id="vehicle_id">
                            <option value="">Select Vehicle Number</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" data-driver-id="{{ $vehicle->driver_id }}">{{ $vehicle->vehicle_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reported By<span style="color:red;">*</span></label>
                        <select name="reported_by" id="reported_by" class="form-control">
                            <option value="">Select Report By</option>
                            <option value="admin">Admin</option>
                            <option value="parent">Parent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Emergency Type <span style="color:red;">*</span></label>
                        <select class="form-control" id="emergency_type" name="emergency_type">
                            <option value="">Select Emergency Type</option>
                            @foreach ($emergencyTypes as $emergencyType)
                                <option value="{{ $emergencyType->emergency_type }}">
                                    {{ $emergencyType->emergency_type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Contact Number <span style="color:red;">*</span></label>
                        <input type="tel" class="form-control" id="contact_number" name="contact_number"
                            placeholder="Enter 10 or 11 digit contact number" minlength="10" maxlength="11"
                            pattern="[0-9]{10,11}" required autocomplete="off">
                    </div>

                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                    <a href="{{ $indexRoute }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        CKEDITOR.replace('description');
        const emergencyVehicleOptions = Array.from(document.querySelectorAll('#vehicle_id option')).map(function(option) {
            return {
                value: option.value,
                text: option.textContent,
                driverId: option.dataset.driverId || ''
            };
        });

        function refreshEnhancedSelect(selectElement) {
            const $select = $(selectElement);

            if ($select.hasClass('select2-hidden-accessible') && $.fn.select2) {
                try {
                    $select.select2('destroy');
                } catch (e) {}
            }

            const $commonWrapper = $select.prev('.common-select2');
            if ($commonWrapper.length) {
                const closeDropdown = $select.data('commonSelectClose');
                if (typeof closeDropdown === 'function') {
                    closeDropdown();
                }
                $commonWrapper.remove();
                $select.removeClass('common-select2-source');
                $select.removeData('commonSelectBound');
                $select.removeData('commonSelectClose');
            }

            const $niceWrapper = $select.next('.nice-select');
            if ($niceWrapper.length) {
                $niceWrapper.remove();
            }

            if (typeof window.initializeSelect2Dropdowns === 'function') {
                window.initializeSelect2Dropdowns(selectElement);
            } else if ($.fn.niceSelect) {
                $select.niceSelect();
            }
        }

        function syncEmergencyDriverVehicle() {
            const driverSelect = document.getElementById('driver_id');
            const vehicleSelect = document.getElementById('vehicle_id');
            const selectedDriverOption = driverSelect.options[driverSelect.selectedIndex];
            const selectedDriverId = driverSelect.value;
            const driverVehicleId = selectedDriverOption ? selectedDriverOption.dataset.vehicleId || '' : '';

            const matchingVehicles = selectedDriverId === ''
                ? []
                : emergencyVehicleOptions.filter(function(option) {
                    if (option.value === '') {
                        return false;
                    }

                    return option.driverId === selectedDriverId || option.value === driverVehicleId;
                });

            const nextVehicleValue = selectedDriverId !== '' && driverVehicleId !== '' ? driverVehicleId : '';

            vehicleSelect.innerHTML = '<option value="">Select Vehicle Number</option>';
            matchingVehicles.forEach(function(option) {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;
                optionElement.textContent = option.text;
                optionElement.dataset.driverId = option.driverId;
                if (option.value === nextVehicleValue) {
                    optionElement.selected = true;
                }
                vehicleSelect.appendChild(optionElement);
            });

            vehicleSelect.disabled = selectedDriverId === '' || matchingVehicles.length === 0;
            vehicleSelect.value = nextVehicleValue;
            refreshEnhancedSelect(vehicleSelect);
        }

        syncEmergencyDriverVehicle();

        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('emergencyForm'));
               formData.set('description', CKEDITOR.instances.description.getData());
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('driver_id')) showError('#driver_id', 'Driver Name is required');
            if (!formData.get('vehicle_id')) showError('#vehicle_id', 'Vehicle Number is required');
            if (!formData.get('reported_by')) showError('#reported_by', 'Reported By is required');
            if (!formData.get('emergency_type')) showError('#emergency_type', 'Emergency Type is required');
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }
            if (!CKEDITOR.instances.description.getData().trim()) {
            if ($('#description').next('.cke').next('.error-message').length === 0) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color:red;">Description is required.</span>'
                );
            }
            isValid = false;
        }
            if (!formData.get('contact_number')) showError('#contact_number', 'Contact Number is required');

            function isValidPositive(value) {
                return /^[a-zA-Z0-9]+$/.test(value);
            }
            if (!isValid) return;

            document.getElementById('contact_number').addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 11);
            });

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.emergency.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', 'Emergency created successfully!');
                        setTimeout(() => window.location.href = '{{ $indexRoute }}', 1500);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });



        document.getElementById('driver_id').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        $('#driver_id').on('change', function() {
            syncEmergencyDriverVehicle();
        });
        document.getElementById('vehicle_id').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('reported_by').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('emergency_type').addEventListener('change', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
         CKEDITOR.instances.description.on('change', function () {
        $('#description').next('.cke').next('.error-message').remove();
    });

        document.getElementById('contact_number').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });

        // real-time typing + paste validation
        $('#contact_number').on('input paste', function() {
            const value = $(this).val();
            if (value && !/^\d*\.?\d*$/.test(value)) {
                $(this).val(value.slice(0, -1));
            }
        });
    </script>
@endsection
