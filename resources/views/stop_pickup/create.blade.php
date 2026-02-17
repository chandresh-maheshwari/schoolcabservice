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
                            Add Stop Or Pickup Point
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Stop Or Pickup Point </h4>
            </div>

            <div class="card-body">
                <form id="stopPickupForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Route Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="route_id" id="route_id">
                            <option value="">Select Route</option>
                            @foreach ($routeData as $route)
                                <option value="{{ $route->id }}">{{ $route->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pickup Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="pickup_name" name="pickup_name" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Stop Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="stop_name" name="stop_name" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Latitude <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="latitude" name="latitude" step="any"
                            min="-90" max="90" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Longitude <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="longitude" name="longitude" step="any"
                            min="-180" max="180" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Squence Order <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="sequence_order" name="sequence_order" required
                            autocomplete="off" oninput="this.value = this.value < 1 ? '' : this.value">
                    </div>



                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                    <a href="{{ route('stopPickup.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('stopPickupForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('route_id')) showError('#route_id', 'Route Name is required');
            if (!formData.get('pickup_name')) showError('#pickup_name', 'Pickup Name is required');
            if (!formData.get('stop_name')) showError('#stop_name', 'Stop Name is required');
            if (!formData.get('latitude')) showError('#latitude', 'Latitude is required');
            if (!formData.get('longitude')) showError('#longitude', 'Longitude is required');
            if (!formData.get('sequence_order')) showError('#sequence_order', 'Squence Order is required');


            function isValidPositive(value) {
                return /^[a-zA-Z0-9]+$/.test(value);
            }
            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.stopPickup.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {
                    const data = await res.json();

                    Swal.close();

                    if (!res.ok) {
                        notify('error', data.message || 'Validation error');
                        return;
                    }

                    if (data.success) {
                        notify('success', data.message);
                        setTimeout(() => window.location.href = '{{ route('stopPickup.index') }}', 1500);
                    }
                })
                .catch(() => {
                    Swal.close();
                    notify('error', 'Something went wrong');
                });

        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });

        document.getElementById('route_id').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('pickup_name').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('stop_name').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('latitude').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('longitude').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('sequence_order').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });

        // real-time typing + paste validation
        $('#sequence_order').on('input paste', function() {
            const value = $(this).val();
            if (value && !/^\d*\.?\d*$/.test(value)) {
                $(this).val(value.slice(0, -1));
            }
        });
    </script>
@endsection
