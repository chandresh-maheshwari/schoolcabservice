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
                            Edit Booking
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Booking</h4>
            </div>

            <div class="card-body">
                <form id="bookingForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Package Type --}}
                    <div class="form-group">
                        <label>Package Type <span style="color:red;">*</span></label>
                        <select class="form-control" name="package_type_id" id="package_type">
                            <option value="">Select Package Type</option>
                            @foreach ($packages as $package)
                                <option value="{{ $package->id }}"
                                    {{ $booking->package_type_id == $package->id ? 'selected' : '' }}>
                                    {{ $package->package_type }}
                                </option>
                            @endforeach

                        </select>

                    </div>

                    {{-- Booking Type --}}
                    <div class="form-group">
                        <label>Booking Type <span style="color:red;">*</span></label>
                        <select class="form-control" name="booking_type_id" id="booking_type">
                            <option value="">Select Booking Type</option>
                            @foreach ($packages as $type)
                                <option value="{{ $type->id }}"
                                    {{ $booking->booking_type_id == $type->id ? 'selected' : '' }}>
                                    {{ $type->booking_type }}
                                </option>
                            @endforeach

                        </select>

                    </div>

                    {{-- School --}}
                    <div class="form-group">
                        <label>School <span style="color:red;">*</span></label>
                        <select class="form-control" name="school_id" id="school_id">
                            <option value="">Select School</option>
                            @foreach ($schoolData as $school)
                                <option value="{{ $school->id }}"
                                    {{ $booking->school_id == $school->id ? 'selected' : '' }}>
                                    {{ $school->school_name }}
                                </option>
                            @endforeach
                        </select>

                    </div>

                    {{-- Route --}}
                    <div class="form-group">
                        <label>Route <span style="color:red;">*</span></label>
                        <select class="form-control" name="route_id" id="route_id">
                            <option value="">Select Route</option>
                            @foreach ($routeData as $route)
                                <option value="{{ $route->id }}"
                                    {{ $booking->route_id == $route->id ? 'selected' : '' }}>
                                    {{ $route->name }}
                                </option>
                            @endforeach
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
        $(document).on('input change', 'input, select', function() {
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
