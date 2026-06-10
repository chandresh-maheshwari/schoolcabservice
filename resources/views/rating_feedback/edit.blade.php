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
                            Edit Feedback / Rating
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Feedback / Rating</h4>
            </div>

            <div class="card-body">
                <form id="ratingForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Driver Name --}}
                    <div class="form-group">
                        <label> Driver Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="driver_name" id="driver_name">
                            <option value="">Select Driver</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}" data-vehicle-id="{{ $driver->vehicle_id }}"
                                    {{ $driver->id == $rating->driver_id ? 'selected' : '' }}>
                                    {{ $driver->driver_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Vehicle Number --}}
                    <div class="form-group">
                        <label>Vehicle Number <span style="color:red;">*</span></label>
                        <select class="form-control" name="vehicle_number" id="vehicle_number">
                            <option value="">Select Vehicle Number</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" data-driver-id="{{ $vehicle->driver_id }}"
                                    {{ $vehicle->id == $rating->vehicle_id ? 'selected' : '' }}>
                                    {{ $vehicle->vehicle_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Rating --}}
                    <div class="form-group">
                        <label>Rating <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="rating" name="rating"
                            value="{{ old('rating', $rating->rating) }}" min="1" max="5" step="1"
                            required autocomplete="off"
                            oninput="
            if (this.value < 1) this.value = 1;
            if (this.value > 5) this.value = 5;
        ">
                    </div>


                    {{-- Comment --}}
                    <div class="form-group">
                        <label>Comment <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="comments" name="comments" rows="4" autocomplete="off">{{ $rating->comments }}</textarea>
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ $indexRoute }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        function syncRatingDriverVehicle(changedField) {
            const driverSelect = document.getElementById('driver_name');
            const vehicleSelect = document.getElementById('vehicle_number');
            const selectedDriverOption = driverSelect.options[driverSelect.selectedIndex];
            const selectedDriverId = driverSelect.value;
            const driverVehicleId = selectedDriverOption ? selectedDriverOption.dataset.vehicleId || '' : '';

            vehicleSelect.disabled = selectedDriverId === '';

            Array.from(vehicleSelect.options).forEach(function(option, index) {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                const optionDriverId = option.dataset.driverId || '';
                option.hidden = selectedDriverId === '' || optionDriverId !== selectedDriverId;
            });

            if (selectedDriverId === '') {
                vehicleSelect.value = '';
                return;
            }

            if (driverVehicleId === '') {
                vehicleSelect.value = '';
                return;
            }

            if (changedField === 'driver' || vehicleSelect.value !== driverVehicleId) {
                vehicleSelect.value = driverVehicleId;
            }
        }

        syncRatingDriverVehicle();

        $('#updateBtn').on('click', function() {

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

            if (!isValid) return;

            document.getElementById('rating').addEventListener('input', function() {
                let val = this.value.replace(/\D/g, '');
                if (val === '') {
                    this.value = '';
                    return;
                }
                let num = parseInt(val, 10);
                if (num < 1) num = 1;
                if (num > 5) num = 5;
                this.value = num;
            });

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });


            fetch('{{ route('api.rating.update', $rating->id) }}', {
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
                        notify('success', 'Rating updated successfully!');
                        setTimeout(() => window.location.href = '{{ $indexRoute }}', 1500);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                });
        });

        // Remove error message on change
        $(document).on('input change', 'input, select, textarea', function() {
            $(this).next('.error-message').remove();
        });

        document.getElementById('driver_name').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('driver_name').addEventListener('change', function() {
            syncRatingDriverVehicle('driver');
        });
        document.getElementById('vehicle_number').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('vehicle_number').addEventListener('change', function() {
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
