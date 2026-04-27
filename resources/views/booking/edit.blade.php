@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    @php
        $bookingEditIsSchoolUser = !empty($isSchoolUser);
        $bookingEditPackageOptions = collect($packages ?? [])->map(function ($package) {
            return [
                'id' => (int) $package->id,
                'package_type' => (string) ($package->package_type ?? ''),
                'booking_type' => (string) ($package->booking_type ?? ''),
                'school_id' => (int) ($package->effective_school_id ?? 0),
            ];
        })->values();
        $bookingEditSelectedPackageTypeId = (int) old('package_type_id', $booking->package_type_id ?? 0);
        $bookingEditSelectedBookingTypeId = (int) old('booking_type_id', $booking->booking_type_id ?? 0);
        $bookingEditRouteOptions = collect($routeData ?? [])->map(function ($route) {
            return [
                'id' => (int) $route->id,
                'name' => (string) ($route->display_name ?? $route->name ?? ''),
                'school_id' => (int) ($route->effective_school_id ?? $route->school_id ?? 0),
            ];
        })->values();
        $bookingEditSelectedRouteId = (int) old('route_id', $booking->route_id ?? 0);
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
                            Edit Booking
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @include('child.partials.module_tabs', [
        'activeTab' => 'booking',
        'entityIds' => [
            'booking' => $booking->id,
            'child' => request('child_id') ?: ($booking->child_id ?? null),
            'parent' => request('parent_id') ?: optional($booking->child)->parent_id,
        ],
    ])

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Booking</h4>
            </div>

            <div class="card-body">
                <form id="bookingForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="child_id" value="{{ request('child_id') ?: ($booking->child_id ?? '') }}">

                    {{-- School --}}
                    <div class="form-group">
                        <label>School <span style="color:red;">*</span></label>
                        @if (!empty($isSchoolUser) && !empty($defaultSchoolId))
                            <input type="hidden" name="school_id" id="school_id" value="{{ $defaultSchoolId }}">
                            <input type="text" class="form-control" value="{{ $defaultSchoolName ?? 'School' }}" disabled>
                        @else
                            <select class="form-control" name="school_id" id="school_id">
                                <option value="">Select School</option>
                                @foreach ($schoolData as $school)
                                    <option value="{{ $school->id }}"
                                        {{ $booking->school_id == $school->id ? 'selected' : '' }}>
                                        {{ $school->school_name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                    </div>

                    {{-- Package Type --}}
                    <div class="form-group">
                        <label>Package Type <span style="color:red;">*</span></label>
                        <select class="form-control" name="package_type_id" id="package_type" disabled>
                            <option value="">Select Package Type</option>

                        </select>

                    </div>

                    {{-- Booking Type --}}
                    <div class="form-group">
                        <label>Booking Type <span style="color:red;">*</span></label>
                        <select class="form-control" name="booking_type_id" id="booking_type" disabled>
                            <option value="">Select Booking Type</option>

                        </select>

                    </div>

                    {{-- Route --}}
                    <div class="form-group">
                        <label>Route <span style="color:red;">*</span></label>
                        <select class="form-control" name="route_id" id="route_id" disabled>
                            <option value="">Select Route</option>
                        </select>

                    </div>

                    {{-- Latitude --}}
                    <div class="form-group">
                        <label>Latitude <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="latitude" name="latitude"
                            value="{{ $booking->latitude }}" step="any" min="-90" max="90" required
                            autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Longitude <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="longitude" name="longitude"
                            value="{{ $booking->longitude }}" step="any" min="-180" max="180" required
                            autocomplete="off">
                    </div>


                    <div class="form-group">
                        <label>Short Description</label>
                        <input type="text" class="form-control" id="short_description" name="short_description"
                            value="{{ $booking->short_description ?? '' }}">
                    </div>
                    {{-- Payment Status --}}
                    <div class="form-group">
                        <label>Payment Status <span style="color:red;">*</span></label>
                        <select name="payment_status" id="payment_status" class="form-control">
                            <option value="">Select Payment Status</option>
                            <option value="pending" {{ $booking->payment_status == 'pending' ? 'selected' : '' }}>Pending
                            </option>
                            <option value="received" {{ $booking->payment_status == 'received' ? 'selected' : '' }}>
                                Received</option>
                            <option value="in_progress" {{ $booking->payment_status == 'in_progress' ? 'selected' : '' }}>
                                In Progress</option>
                        </select>
                    </div>

                    {{-- Payment Mode --}}
                    <div class="form-group">
                        <label>Payment Mode <span style="color:red;">*</span></label>
                        <select name="payment_mode" id="payment_mode" class="form-control">
                            <option value="">Select Payment Mode</option>
                            <option value="cash" {{ $booking->payment_mode == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="B2B" {{ $booking->payment_mode == 'B2B' ? 'selected' : '' }}>B2B</option>
                            <option value="upi" {{ $booking->payment_mode == 'upi' ? 'selected' : '' }}>UPI</option>
                        </select>
                    </div>

                    {{-- Contact Number --}}
                    <div class="form-group">
                        <label>Contact Number <span style="color:red;">*</span></label>

                        <input type="tel" class="form-control" id="contact_number" name="contact_number"
                            value="{{ old('contact_number', $booking->contact_number) }}" minlength="10" maxlength="11"
                            pattern="[0-9]{10,11}" required autocomplete="off">
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ route('booking.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        const bookingEditIsSchoolUser = @json($bookingEditIsSchoolUser);
        const bookingEditPackageOptions = @json($bookingEditPackageOptions);
        const bookingEditSelectedPackageTypeId = @json($bookingEditSelectedPackageTypeId);
        const bookingEditSelectedBookingTypeId = @json($bookingEditSelectedBookingTypeId);
        const bookingEditRouteOptions = @json($bookingEditRouteOptions);
        const bookingEditSelectedRouteId = @json($bookingEditSelectedRouteId);

        function bookingEditRenderPackageOptions(selectedSchoolId, selectId, optionLabel) {
            const targetSelect = document.getElementById(selectId);
            const normalizedSchoolId = parseInt(selectedSchoolId, 10) || 0;
            const scopedOptions = bookingEditIsSchoolUser
                ? (normalizedSchoolId > 0
                    ? bookingEditPackageOptions.filter(option => parseInt(option.school_id, 10) === normalizedSchoolId)
                    : [])
                : bookingEditPackageOptions;
            const selectedOptionId = parseInt(targetSelect.value, 10) || (
                selectId === 'package_type' ? bookingEditSelectedPackageTypeId : bookingEditSelectedBookingTypeId
            ) || 0;

            targetSelect.innerHTML = (!bookingEditIsSchoolUser || normalizedSchoolId > 0)
                ? `<option value="">Select ${optionLabel}</option>`
                : '<option value="">Select School First</option>';
            targetSelect.disabled = bookingEditIsSchoolUser && normalizedSchoolId <= 0;

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

        function bookingEditRenderPackages(selectedSchoolId) {
            bookingEditRenderPackageOptions(selectedSchoolId, 'package_type', 'Package Type');
            bookingEditRenderPackageOptions(selectedSchoolId, 'booking_type', 'Booking Type');
        }

        function bookingEditRenderRoutes(selectedSchoolId) {
            const routeSelect = document.getElementById('route_id');
            const normalizedSchoolId = parseInt(selectedSchoolId, 10) || 0;
            const scopedOptions = normalizedSchoolId > 0
                ? bookingEditRouteOptions.filter(option => parseInt(option.school_id, 10) === normalizedSchoolId)
                : [];
            const selectedRouteId = parseInt(routeSelect.value, 10) || bookingEditSelectedRouteId || 0;

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

        const bookingEditSchoolField = document.getElementById('school_id');
        bookingEditRenderPackages(bookingEditSchoolField ? bookingEditSchoolField.value : '');
        bookingEditRenderRoutes(bookingEditSchoolField ? bookingEditSchoolField.value : '');

        if (bookingEditSchoolField) {
            ['change', 'input'].forEach(function(eventName) {
                bookingEditSchoolField.addEventListener(eventName, function() {
                    bookingEditRenderPackages(this.value);
                    bookingEditRenderRoutes(this.value);
                    $('#package_type, #booking_type, #route_id').closest('.form-group').find('.error-message').remove();
                });
            });
        }

        $('#updateBtn').on('click', function() {

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
            if (!formData.get('payment_status')) showError('#payment_status', 'Payment Status is required');
            if (!formData.get('payment_mode')) showError('#payment_mode', 'Payment Mode is required');
            if (!formData.get('contact_number')) showError('#contact_number', 'Contact Number is required');

            if (!isValid) return;

            document.getElementById('contact_number').addEventListener('input', function() {
                // allow only digits & max 11
                this.value = this.value.replace(/\D/g, '').slice(0, 11);
            });

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            formData.append('_method', 'PUT');

            fetch('{{ route('api.booking.update', $booking->id) }}', {
                    method: 'POST', // method spoofing
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
                        notify('success', 'Booking updated successfully!');
                        setTimeout(() => window.location.href = '{{ route('booking.index') }}', 1500);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document)
            .off('input.bookingEdit change.bookingEdit', 'input, select')
            .on('input.bookingEdit change.bookingEdit', 'input, select', function() {
                $(this).closest('.form-group').find('.error-message').remove();
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

        // real-time typing + paste validation
        $('#contact_number').on('input paste', function() {
            const value = $(this).val();
            if (value && !/^\d*\.?\d*$/.test(value)) {
                $(this).val(value.slice(0, -1));
            }
        });
    </script>
@endsection
