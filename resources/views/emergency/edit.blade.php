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
                <h4 class="about-us-create-header">Edit Emergency</h4>
            </div>

            <div class="card-body">
                <form id="emergencyForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Driver Name --}}
                    <div class="form-group">
                        <label> Driver Name <span style="color:red;">*</span></label>

                        <select class="form-control" name="driver_id" id="driver_id">
                            <option value="">Select Driver</option>

                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}" data-vehicle-id="{{ $driver->vehicle_id }}"
                                    {{ $driver->id == $emergency->driver_id ? 'selected' : '' }}>
                                    {{ $driver->driver_name }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    {{-- Vehicle Number --}}
                    <div class="form-group">
                        <label>Vehicle Number <span style="color:red;">*</span></label>
                        <select class="form-control" name="vehicle_id" id="vehicle_id">
                            <option value="">Select Vehicle</option>

                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" data-driver-id="{{ $vehicle->driver_id }}"
                                    {{ $vehicle->id == $emergency->vehicle_id ? 'selected' : '' }}>
                                    {{ $vehicle->vehicle_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Reported By --}}
                    <div class="form-group">
                        <label>Reported By <span style="color:red;">*</span></label>
                        <select name="reported_by" id="reported_by" class="form-control">
                            <option value="">Select Report By</option>
                            <option value="admin" {{ $emergency->reported_by == 'admin' ? 'selected' : '' }}>
                                Admin
                            </option>
                            <option value="parent" {{ $emergency->reported_by == 'parent' ? 'selected' : '' }}>
                                Parent
                            </option>
                        </select>
                    </div>

                    {{-- Emergency Type --}}
                    <div class="form-group">
                        <label>Emergency Type <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="emergency_type" name="emergency_type"
                            value="{{ $emergency->emergency_type }}" autocomplete="off">
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
                            pattern="[0-9]{10,11}" required autocomplete="off">
                    </div>


                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
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

        const initialEmergencyDriverId = @json((string) ($emergency->driver_id ?? ''));
        const initialEmergencyVehicleId = @json((string) ($emergency->vehicle_id ?? ''));

        function syncEmergencyDriverVehicle(preserveSelection = false) {
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

            const nextVehicleValue = preserveSelection
                ? initialEmergencyVehicleId
                : (selectedDriverId !== '' && driverVehicleId !== '' ? driverVehicleId : '');

            vehicleSelect.innerHTML = '<option value="">Select Vehicle</option>';
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

        syncEmergencyDriverVehicle(true);

        $('#updateBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('emergencyForm'));
            formData.set('description', CKEDITOR.instances.description.getData());
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('driver_id'))
                showError('#driver_id', 'Driver Name is required');

            if (!formData.get('vehicle_id'))
                showError('[name="vehicle_id"]', 'Vehicle Number is required');
            if (!formData.get('reported_by')) showError('#reported_by', 'Reported By is required');
            if (!formData.get('emergency_type')) showError('#emergency_type', 'Emergency Type is required');
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }
            if (!formData.get('contact_number')) showError('#contact_number', 'Contact Number is required');

            if (!isValid) return;

            document.getElementById('contact_number').addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 11);
            });
            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            formData.append('_method', 'PUT');
            fetch('{{ route('api.emergency.update', $emergency->id) }}', {
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
                        notify('success', 'Emergency updated successfully!');
                        setTimeout(() => window.location.href = '{{ $indexRoute }}', 1500);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                });
        });

        // Remove error message on change
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });

       $('#driver_id').on('change', function () {
    $(this).closest('.form-group').find('.error-message').remove();
    syncEmergencyDriverVehicle();
});
       $('#vehicle_id').on('change', function () {
    $(this).closest('.form-group').find('.error-message').remove();
});

        document.getElementById('reported_by').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('emergency_type').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });
        // document.getElementById('contact_number').addEventListener('input', function() {
        //     $(this).closest('.form-group').find('.error-message').remove();
        // });

        $('#contact_number').on('input paste', function() {
            const value = $(this).val();
            if (value && !/^\d*$/.test(value)) {
                $(this).val(value.slice(0, -1));
            }
        });
    </script>
@endsection
