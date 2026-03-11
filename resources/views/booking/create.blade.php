@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

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
        'entityIds' => [],
    ])

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Booking </h4>
            </div>

            <div class="card-body">
                <form id="bookingForm" enctype="multipart/form-data">
                    @csrf

                    {{-- Package Name --}}
                    <div class="form-group">
                        <label>Package Type <span style="color:red;">*</span></label>
                        <select class="form-control" name="package_type" id="package_type">
                            <option value="">Select Package Type</option>
                            @foreach ($packages as $package)
                                <option value="{{ $package->id }}">{{ $package->package_type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Booking Type <span style="color:red;">*</span></label>
                        <select class="form-control" name="booking_type" id="booking_type">
                            <option value="">Select Booking Type</option>
                            @foreach ($packages as $package)
                                <option value="{{ $package->id }}">{{ $package->booking_type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>School <span style="color:red;">*</span></label>
                        @if (!empty($isSchoolUser) && !empty($defaultSchoolId))
                            <input type="hidden" name="school_id" id="school_id" value="{{ $defaultSchoolId }}">
                            <input type="text" class="form-control" value="{{ $defaultSchoolName ?? 'School' }}" disabled>
                        @else
                            <select class="form-control" name="school_id" id="school_id">
                                <option value="">Select School</option>
                                @foreach ($schoolData as $school)
                                    <option value="{{ $school->id }}">{{ $school->school_name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Route <span style="color:red;">*</span></label>
                        <select class="form-control" name="route_id" id="route_id">
                            <option value="">Select Route</option>
                            @foreach ($routeData as $route)
                                <option value="{{ $route->id }}">{{ $route->name }}</option>
                            @endforeach
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
                            placeholder="Enter 10 or 11 digit contact number" minlength="10" maxlength="11"
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
        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('bookingForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('package_type')) showError('#package_type', 'Package Type is required');
            if (!formData.get('booking_type')) showError('#booking_type', 'Booking Type is required');
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
                        setTimeout(() => window.location.href = '{{ route('booking.index') }}', 1500);
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
