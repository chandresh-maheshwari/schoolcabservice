@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    @php
        $bookingCreateIsSchoolUser = !empty($isSchoolUser);
        $bookingCreatePackageOptions = collect($packages ?? [])->map(function ($package) {
            return [
                'id' => (int) $package->id,
                'package_type' => (string) ($package->package_type ?? ''),
                'booking_type' => (string) ($package->booking_type ?? ''),
                'school_id' => (int) ($package->effective_school_id ?? 0),
            ];
        })->values();
        $bookingCreateSelectedPackageTypeId = (int) old('package_type_id', 0);
        $bookingCreateSelectedBookingTypeId = (int) old('booking_type_id', 0);
        $bookingCreateRouteOptions = collect($routeData ?? [])->map(function ($route) {
            return [
                'id' => (int) $route->id,
                'name' => (string) ($route->display_name ?? $route->name ?? ''),
                'school_id' => (int) ($route->effective_school_id ?? $route->school_id ?? 0),
            ];
        })->values();
        $bookingCreateSelectedRouteId = (int) old('route_id', $prefillBooking['route_id'] ?? 0);
    @endphp

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item">
                            <a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Add Booking
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @include('child.partials.module_tabs', [
        'activeTab' => 'booking',
        'entityIds' => [
            'child' => request('child_id'),
            'parent' => request('parent_id'),
        ],
    ])

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Booking </h4>
            </div>

            <div class="card-body">
                <form id="bookingForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="child_id" value="{{ request('child_id') }}">

                    <div class="form-group">
                        <label>School <span style="color:red;">*</span></label>
                        @if (!empty($isSchoolUser) && !empty($defaultSchoolId))
                            <input type="hidden" name="school_id" id="school_id" value="{{ $defaultSchoolId }}">
                            <input type="text" class="form-control" value="{{ $defaultSchoolName ?? 'School' }}" disabled>
                        @else
                            <select class="form-control" name="school_id" id="school_id">
                                <option value="">Select School</option>
                                @foreach ($schoolData as $school)
                                    <option value="{{ $school->id }}" {{ (int) old('school_id', $prefillBooking['school_id'] ?? 0) === (int) $school->id ? 'selected' : '' }}>
                                        {{ $school->school_name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    {{-- Package Name --}}
                    <div class="form-group">
                        <label>Package Type <span style="color:red;">*</span></label>
                        <select class="form-control" name="package_type_id" id="package_type" disabled>
                            <option value="">Select Package Type</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Booking Type <span style="color:red;">*</span></label>
                        <select class="form-control" name="booking_type_id" id="booking_type" disabled>
                            <option value="">Select Booking Type</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Route <span style="color:red;">*</span></label>
                        <select class="form-control" name="route_id" id="route_id" disabled>
                            <option value="">Select Route</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Latitude <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="latitude" name="latitude" step="any"
                            min="-90" max="90" required>
                    </div>

                    <div class="form-group">
                        <label>Longitude <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="longitude" name="longitude" step="any"
                            min="-180" max="180" required>
                    </div>

                    <div class="form-group">
                        <label>Short Description <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="short_description" name="short_description"
                            autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Payment Status <span style="color:red;">*</span></label>
                        <select name="payment_status" id="payment_status" class="form-control">
                            <option value="">Select Payment Status</option>
                            <option value="pending">Pending</option>
                            <option value="received">Received</option>
                            <option value="in_progress">In Progress</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Mode <span style="color:red;">*</span></label>
                        <select name="payment_mode" id="payment_mode" class="form-control">
                            <option value="">Select Payment Mode</option>
                            <option value="cash">Cash</option>
                            <option value="B2B">B2B</option>
                            <option value="upi">UPI</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contact Number <span style="color:red;">*</span></label>
                        <input type="tel" class="form-control" id="contact_number" name="contact_number"
                            placeholder="Enter 10 or 11 digit contact number" value="{{ old('contact_number', $prefillBooking['contact_number'] ?? '') }}" minlength="10" maxlength="11"
                            pattern="[0-9]{10,11}" required autocomplete="off">
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                    <a href="{{ route('booking.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        const bookingCreateIsSchoolUser = @json($bookingCreateIsSchoolUser);
        const bookingCreatePackageOptions = @json($bookingCreatePackageOptions);
        const bookingCreateSelectedPackageTypeId = @json($bookingCreateSelectedPackageTypeId);
        const bookingCreateSelectedBookingTypeId = @json($bookingCreateSelectedBookingTypeId);
        const bookingCreateRouteOptions = @json($bookingCreateRouteOptions);
        const bookingCreateSelectedRouteId = @json($bookingCreateSelectedRouteId);

        function bookingCreateRenderPackageOptions(selectedSchoolId, selectId, optionLabel) {
            const targetSelect = document.getElementById(selectId);
            const normalizedSchoolId = parseInt(selectedSchoolId, 10) || 0;
            const scopedOptions = bookingCreateIsSchoolUser
                ? (normalizedSchoolId > 0
                    ? bookingCreatePackageOptions.filter(option => parseInt(option.school_id, 10) === normalizedSchoolId)
                    : [])
                : bookingCreatePackageOptions;
            const selectedOptionId = parseInt(targetSelect.value, 10) || (
                selectId === 'package_type' ? bookingCreateSelectedPackageTypeId : bookingCreateSelectedBookingTypeId
            ) || 0;

            targetSelect.innerHTML = (!bookingCreateIsSchoolUser || normalizedSchoolId > 0)
                ? `<option value="">Select ${optionLabel}</option>`
                : '<option value="">Select School First</option>';
            targetSelect.disabled = bookingCreateIsSchoolUser && normalizedSchoolId <= 0;

            scopedOptions.forEach(option => {
                const label = selectId === 'package_type' ? option.package_type : option.booking_type;
                if (!label) {
                    return;
                }

                targetSelect.insertAdjacentHTML(
                    'beforeend',
                    `<option value="${option.id}">${label}</option>`
                );
            });

            if (selectedOptionId > 0 && scopedOptions.some(option => parseInt(option.id, 10) === selectedOptionId)) {
                targetSelect.value = String(selectedOptionId);
            }
        }

        function bookingCreateRenderPackages(selectedSchoolId) {
            bookingCreateRenderPackageOptions(selectedSchoolId, 'package_type', 'Package Type');
            bookingCreateRenderPackageOptions(selectedSchoolId, 'booking_type', 'Booking Type');
        }

        function bookingCreateRenderRoutes(selectedSchoolId) {
            const routeSelect = document.getElementById('route_id');
            const normalizedSchoolId = parseInt(selectedSchoolId, 10) || 0;
            const scopedOptions = normalizedSchoolId > 0
                ? bookingCreateRouteOptions.filter(option => parseInt(option.school_id, 10) === normalizedSchoolId)
                : [];
            const selectedRouteId = parseInt(routeSelect.value, 10) || bookingCreateSelectedRouteId || 0;

            routeSelect.innerHTML = normalizedSchoolId > 0
                ? '<option value="">Select Route</option>'
                : '<option value="">Select School First</option>';
            routeSelect.disabled = normalizedSchoolId <= 0;

            scopedOptions.forEach(option => {
                routeSelect.insertAdjacentHTML(
                    'beforeend',
                    `<option value="${option.id}">${option.name}</option>`
                );
            });

            if (selectedRouteId > 0 && scopedOptions.some(option => parseInt(option.id, 10) === selectedRouteId)) {
                routeSelect.value = String(selectedRouteId);
            }
        }

        const bookingCreateSchoolField = document.getElementById('school_id');
        bookingCreateRenderPackages(bookingCreateSchoolField ? bookingCreateSchoolField.value : '');
        bookingCreateRenderRoutes(bookingCreateSchoolField ? bookingCreateSchoolField.value : '');

        if (bookingCreateSchoolField) {
            ['change', 'input'].forEach(function(eventName) {
                bookingCreateSchoolField.addEventListener(eventName, function() {
                    bookingCreateRenderPackages(this.value);
                    bookingCreateRenderRoutes(this.value);
                    $('#package_type, #booking_type, #route_id').closest('.form-group').find('.error-message').remove();
                });
            });
        }

        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('bookingForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('package_type_id')) showError('#package_type', 'Package Type is required');
            if (!formData.get('booking_type_id')) showError('#booking_type', 'Booking Type is required');
            if (!formData.get('school_id')) showError('#school_id', 'School is required');
            if (!formData.get('route_id')) showError('#route_id', 'Route is required');
            if (!formData.get('latitude')) showError('#latitude', 'Latitude is required');
            if (!formData.get('longitude')) showError('#longitude', 'Longitude is required');
            if (!formData.get('short_description')) showError('#short_description',
                'Short Description is required');
            if (!formData.get('payment_status')) showError('#payment_status', 'Payment Status is required');
            if (!formData.get('payment_mode')) showError('#payment_mode', 'Payment Mode is required');
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

            fetch('{{ route('api.booking.store') }}', {
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
                        notify('success', 'Booking created successfully!');
                        const bookingId = data.id || '';
                        const childId = formData.get('child_id') || '';
                        const parentId = new URLSearchParams(window.location.search).get('parent_id') || '';
                        let nextUrl = '{{ route('booking.index') }}';

                        if (bookingId) {
                            nextUrl = '{{ route('booking.edit', ['booking' => '__BOOKING_ID__']) }}'.replace('__BOOKING_ID__', bookingId);
                            const query = new URLSearchParams();
                            if (childId) query.set('child_id', childId);
                            if (parentId) query.set('parent_id', parentId);
                            const queryString = query.toString();
                            if (queryString) {
                                nextUrl += '?' + queryString;
                            }
                        }

                        setTimeout(() => window.location.href = nextUrl, 1500);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document)
            .off('input.bookingCreate change.bookingCreate', 'input, select')
            .on('input.bookingCreate change.bookingCreate', 'input, select', function() {
                $(this).next('.error-message').remove();
            });



        document.getElementById('package_type').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('booking_type').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('school_id').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('route_id').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('payment_status').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('payment_mode').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('contact_number').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('short_description').addEventListener('input', function() {
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
