{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a
                                class="breadcrumbLink"href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add Vehicle Type Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Vehicle Type Details</h4>
            </div>
            <div class="card-body">
                <form id="vehicleTypeForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="vechileType" style="font-weight: bold;">Vehicle Type <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="vehicle_type" name="vehicle_type" required>
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('vehicleType.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Include common icon picker JS -->
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>

    <script>

        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('vehicleTypeForm'));

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            // Validate form (only required fields in this form)
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

            fetch('{{ route('api.vehicleType.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', 'Vehicle Type details created Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('vehicleType.index') }}';
                        }, 1500);
                    } else {
                        notify('error', data.message || 'There was an error creating the Vehicle Type details.');
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


        // const titleInput = document.getElementById('vehicle_type');
        // const errorSpan = titleInput.parentNode.querySelector('.error-message');

        // titleInput.addEventListener('input', function() {
        //     let value = this.value;

        //     if (value.length > 40) {
        //         this.value = value.slice(0, 40); // stop extra characters
        //         errorSpan.textContent = 'Title cannot exceed 40 characters.';
        //     } else if (value.trim() === '') {
        //         errorSpan.textContent = 'Title is required.';
        //     } else {
        //         errorSpan.textContent = '';
        //     }
        // });

        document.getElementById('vehicle_type').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });

    </script>
@endsection
