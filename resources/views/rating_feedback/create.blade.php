@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    @php
        $schoolSlug = request()->route('schoolSlug');
        $dashboardRoute = $schoolSlug ? route('school.dashboard', ['schoolSlug' => $schoolSlug]) : route('admin_layout.index');
        $indexRoute = $schoolSlug ? route('school.rating.index', ['schoolSlug' => $schoolSlug]) : route('rating.index');
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
                            Add Feedback/Rating
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Feeback/Rating </h4>
            </div>

            <div class="card-body">
                <form id="ratingForm" enctype="multipart/form-data">
                    @csrf

                    {{-- Package Name --}}
                    <div class="form-group">
                        <label> Driver Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="driver_name" id="driver_name">
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
                        <select class="form-control" name="vehicle_number" id="vehicle_number">
                            <option value="">Select Vehicle Number</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" data-driver-id="{{ $vehicle->driver_id }}">{{ $vehicle->vehicle_number }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Rating <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="rating" name="rating" min="1"
                            max="5" step="1" required autocomplete="off"
                            oninput="
            if (this.value < 1) this.value = '';
            if (this.value > 5) this.value = '';
        ">
                    </div>

                    <div class="form-group">
                        <label>Comment <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="comments" name="comments" rows="4" placeholder="Enter your comment"
                            autocomplete="off"></textarea>
                    </div>

                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                    <a href="{{ $indexRoute }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        const ratingVehicleOptions = Array.from(document.querySelectorAll('#vehicle_number option')).map(function(option) {
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

        function syncRatingDriverVehicle(changedField) {
            const driverSelect = document.getElementById('driver_name');
            const vehicleSelect = document.getElementById('vehicle_number');
            const selectedDriverOption = driverSelect.options[driverSelect.selectedIndex];
            const selectedDriverId = driverSelect.value;
            const driverVehicleId = selectedDriverOption ? selectedDriverOption.dataset.vehicleId || '' : '';

            const matchingVehicles = selectedDriverId === ''
                ? []
                : ratingVehicleOptions.filter(function(option) {
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

        syncRatingDriverVehicle();

        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('ratingForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('driver_name')) showError('#driver_name', 'Driver Name is required');
            if (!formData.get('vehicle_number')) showError('#vehicle_number', 'Vehicle Number is required');
            if (!formData.get('rating')) showError('#rating', 'Rating is required');
            if (!formData.get('comments')) showError('#comments', 'Comments are required');

            function isValidPositive(value) {
                return /^[a-zA-Z0-9]+$/.test(value);
            }
            if (!isValid) return;
            document.getElementById('rating').addEventListener('input', function() {
                // sirf number aur 1–5 ke beech
                let val = this.value.replace(/\D/g, '');

                if (val === '') {
                    this.value = '';
                    return;
                }

                let num = parseInt(val, 10);

                if (num < 1 || num > 5) {
                    this.value = '';
                } else {
                    this.value = num;
                }
            });
            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.rating.store') }}', {
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
                        notify('success', 'Rating created successfully!');
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

        document.getElementById('driver_name').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        $('#driver_name').on('change', function() {
            syncRatingDriverVehicle('driver');
        });
        document.getElementById('vehicle_number').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('rating').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('comments').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
    </script>
@endsection
