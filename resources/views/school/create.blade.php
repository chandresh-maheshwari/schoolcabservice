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
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add School Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add School Details</h4>
            </div>
            <div class="card-body">
                <form id="schoolForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="schoolName" style="font-weight: bold;">School Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="school_name" name="school_name" required>
                    </div>
                    <div class="form-group">
                        <label for="schoolCode" style="font-weight: bold;">School Code <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="school_code" name="school_code" required>
                    </div>
                  <div class="form-group">
    <label for="phone" style="font-weight: bold;">
        Phone <span style="color:red;">*</span>
    </label>

    <input
        type="tel"
        class="form-control"
        id="phone"
        name="phone"
        placeholder="Enter 10 or 11 digit phone number"
        minlength="10"
        maxlength="11"
        pattern="[0-9]{10,11}"
        required
        autocomplete="off"
    >
</div>
                    <div class="form-group">
                        <label for="email" style="font-weight: bold;">Email <span style="color: red;">*</span></label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="address" style="font-weight: bold;">Address <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="address" name="address">
                    </div>
                    <div class="form-group">
                        <label for="state" style="font-weight: bold;">
                            State <span style="color: red;">*</span>
                        </label>

                        <select class="form-control" id="state" name="state" required>
                            <option value="">Select State</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->name }}">
                                    {{ $state->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="city" style="font-weight: bold;">
                            City <span style="color: red;">*</span>
                        </label>

                        <select class="form-control" id="city" name="city" required>
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pincode" style="font-weight: bold;">Pincode <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="pincode" name="pincode">
                    </div>
                    <div class="form-group">
                        <label for="latitude" style="font-weight: bold;">Latitude <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="latitude" name="latitude">
                    </div>
                    <div class="form-group">
                        <label for="longitude" style="font-weight: bold;">Longitude <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="longitude" name="longitude">
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('school.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Include common icon picker JS -->
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        /* ===============================
       STATE → CITY DROPDOWN (API)
    ================================ */
        $(document).ready(function() {

            $('#state').on('change', function() {
                let state = $(this).val();

                $('#city').html('<option>Loading...</option>');

                if (!state) {
                    $('#city').html('<option value="">Select City</option>');
                    return;
                }

                $.ajax({
                    url: "{{ route('api.school.getCities') }}",
                    type: "POST",
                    data: {
                        state: state,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(cities) {
                        $('#city').empty().append('<option value="">Select City</option>');
                        cities.forEach(city => {
                            $('#city').append(
                                `<option value="${city}">${city}</option>`
                            );
                        });
                    },
                    error: function() {
                        $('#city').html('<option value="">Error loading cities</option>');
                    }
                });
            });

        });


        /* ===============================
           FORM SUBMIT (YOUR EXISTING CODE)
        ================================ */
        document.getElementById('submitBtn').addEventListener('click', function() {

            var formData = new FormData(document.getElementById('schoolForm'));

            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            let isValid = true;

            if (!formData.get('school_name')) {
                document.getElementById('school_name')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'School Name is required.';
                isValid = false;
            }

            if (!formData.get('state')) {
                document.getElementById('state')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'State is required.';
                isValid = false;
            }

            if (!formData.get('city')) {
                document.getElementById('city')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'City is required.';
                isValid = false;
            }
            if (!formData.get('school_code')) {
                document.getElementById('school_code')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'School Code is required.';
                isValid = false;
            }
            if (!formData.get('phone')) {
                document.getElementById('phone')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Phone is required.';
                isValid = false;
            }
            if (!formData.get('email')) {
                document.getElementById('email')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Email is required.';
                isValid = false;
            }
            if (!formData.get('address')) {
                document.getElementById('address')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Address is required.';
                isValid = false;
            }
            if (!formData.get('pincode')) {
                document.getElementById('pincode')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Pincode is required.';
                isValid = false;
            }
            if (!formData.get('latitude')) {
                document.getElementById('latitude')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Latitude is required.';
                isValid = false;
            }
            if (!formData.get('longitude')) {
                document.getElementById('longitude')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Longitude is required.';
                isValid = false;
            }
            if (!isValid) return;


            document.getElementById('phone').addEventListener('input', function () {
    // allow only digits & max 11
    this.value = this.value.replace(/\D/g, '').slice(0, 11);
});
            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.school.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', 'School details created Successfully!');
                        setTimeout(() => {
                            window.location.href = '{{ route('school.index') }}';
                        }, 1500);
                    } else {
                        notify('error', data.message || 'There was an error creating the School details.');
                    }
                })
                .catch(() => {
                    Swal.close();
                    notify('error', 'An unexpected error occurred.');
                });
        });

        /* ===============================
           ERROR SPANS AUTO ADD
        ================================ */
        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) {
                let errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.appendChild(errorSpan);
            }
        });

        /* ===============================
           CLEAR ERROR ON INPUT
        ================================ */
        $('#school_name, #state, #city, #school_code, #phone, #email, #address, #pincode, #latitude, #longitude').on(
            'change input',
            function() {
                $(this).closest('.form-group').find('.error-message').text('');
            });
    </script>
@endsection
