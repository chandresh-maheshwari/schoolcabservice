{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')
@section('content')
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('vehicleType.index') }}">Vehicle Type</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Vehicle Type
                            Details</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="hero-edit-header">Edit
                    Vehicle Type Details</h4>
            </div>
            <div class="card-body">
                <form id="editVehicleTypeForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="vehicle_type" style="font-weight: bold;">Vehicle Type <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="vehicle_type" name="vehicle_type"
                            value="{{ $vehicleType->vehicle_type }}" required>
                    </div>


                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Update</button>
                    <a href="{{ route('vehicleType.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
</div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- Include common icon picker JS -->
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>
    <script src="{{ asset('js/common_js.js') }}"></script>

    <script>
        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('editVehicleTypeForm'));

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            // Validate form
            var isValid = true;
            if (!formData.get('vehicle_type')) {
                document.getElementById('vehicle_type').nextElementSibling.textContent = 'Vehicle Type is required.';
                isValid = false;
            }
            if (!isValid) {
                return;
            }

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('{{ route('api.vehicleType.update', $vehicleType->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'X-HTTP-Method-Override': 'PUT'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', 'Vehicle Type Details updated Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('vehicleType.index') }}';
                        }, 1500);

                    } else {
                        notify('error', data.message ||
                            'There was an error updating the Vehicle Type Detail page.');
                    }
                })
                .catch(error => {
                    Swal.close();
                    notify('error', 'An unexpected error occurred.');

                });
        });

        // Add error message spans for regular inputs
        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) { // Exclude Select2
                var errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.appendChild(errorSpan);
            }
        });
    </script>
@endsection
