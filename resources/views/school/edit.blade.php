{{-- @extends('dashboard.index') --}}
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
                            Edit School Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit School Details</h4>
            </div>

            <div class="card-body">
                <form id="editSchoolForm">
                    @csrf
                    <input type="hidden" id="school_id" value="{{ $school->id }}">

                    <div class="form-group">
                        <label>School Name <span style="color:red">*</span></label>
                        <input type="text" class="form-control" id="school_name" name="school_name"
                            value="{{ $school->school_name }}">
                    </div>

                    <div class="form-group">
                        <label>School Code <span style="color:red">*</span></label>
                        <input type="text" class="form-control" id="school_code" name="school_code"
                            value="{{ $school->school_code }}">
                    </div>

                    <div class="form-group">
                        <label>Phone <span style="color:red;">*</span></label>

                        <input type="tel" class="form-control" id="phone" name="phone"
                            value="{{ old('phone', $school->phone) }}" minlength="10" maxlength="11" pattern="[0-9]{10,11}"
                            required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Email <span style="color:red">*</span></label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="{{ $school->email }}">
                    </div>

                    <div class="form-group">
                        <label>Address <span style="color:red">*</span></label>
                        <input type="text" class="form-control" id="address" name="address"
                            value="{{ $school->address }}">
                    </div>

                    <div class="form-group">
                        <label>State <span style="color:red">*</span></label>
                        <select class="form-control" id="state" name="state">
                            <option value="">Select State</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->name }}" {{ $school->state == $state->name ? 'selected' : '' }}>
                                    {{ $state->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>City <span style="color:red">*</span></label>
                        <select class="form-control" id="city" name="city">
                            <option value="">Select City</option>
                        </select>
                    </div>


                    <div class="form-group">
                        <label>Pincode <span style="color:red">*</span></label>
                        <input type="text" class="form-control" id="pincode" name="pincode"
                            value="{{ $school->pincode }}">
                    </div>

                    <div class="form-group">
                        <label>Latitude <span style="color:red">*</span></label>
                        <input type="text" class="form-control" id="latitude" name="latitude"
                            value="{{ $school->latitude }}">
                    </div>

                    <div class="form-group">
                        <label>Longitude <span style="color:red">*</span></label>
                        <input type="text" class="form-control" id="longitude" name="longitude"
                            value="{{ $school->longitude }}">
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">
                        Update
                    </button>
                    <a href="{{ route('school.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        /* ===============================
                   STATE → CITY (SAME AS CREATE)
                ================================ */
        $(document).ready(function() {

            let selectedState = "{{ $school->state ?? '' }}";
            let selectedCity = "{{ $school->city ?? '' }}";
            if (selectedState) {
                loadCities(selectedState, selectedCity);
            }

            $('#state').on('change', function() {
                let state = $(this).val();
                loadCities(state, null);
            });

            function loadCities(state, selectedCity = null) {

                if (!state) {
                    $('#city').html('<option value="">Select City</option>');
                    return;
                }

                $('#city').html('<option>Loading...</option>');

                $.ajax({
                    url: "{{ route('api.school.getCities') }}",
                    type: "POST",
                    data: {
                        state: state,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(cities) {

                        $('#city').empty().append('<option value="">Select City</option>');

                        cities.forEach(function(city) {

                            let selected = (selectedCity === city) ? 'selected' : '';

                            $('#city').append(
                                `<option value="${city}" ${selected}>${city}</option>`
                            );
                        });
                    },
                    error: function() {
                        $('#city').html('<option value="">Error loading cities</option>');
                    }
                });
            }
        });

        /* ===============================
           UPDATE SUBMIT + VALIDATION
        ================================ */
        document.getElementById('updateBtn').addEventListener('click', function() {

            let formData = new FormData(document.getElementById('editSchoolForm'));

            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

            let isValid = true;

            if (!formData.get('school_name')) {
                $('#school_name').closest('.form-group').find('.error-message').text('School Name is required.');
                isValid = false;
            }
            if (!formData.get('school_code')) {
                $('#school_code').closest('.form-group').find('.error-message').text('School Code is required.');
                isValid = false;
            }
            if (!formData.get('phone')) {
                $('#phone').closest('.form-group').find('.error-message').text('Phone is required.');
                isValid = false;
            }
            if (!formData.get('email')) {
                $('#email').closest('.form-group').find('.error-message').text('Email is required.');
                isValid = false;
            }
            if (!formData.get('address')) {
                $('#address').closest('.form-group').find('.error-message').text('Address is required.');
                isValid = false;
            }
            if (!formData.get('state')) {
                $('#state').closest('.form-group').find('.error-message').text('State is required.');
                isValid = false;
            }
            if (!formData.get('city')) {
                $('#city').closest('.form-group').find('.error-message').text('City is required.');
                isValid = false;
            }
            if (!formData.get('pincode')) {
                $('#pincode').closest('.form-group').find('.error-message').text('Pincode is required.');
                isValid = false;
            }
            if (!formData.get('latitude')) {
                $('#latitude').closest('.form-group').find('.error-message').text('Latitude is required.');
                isValid = false;
            }
            if (!formData.get('longitude')) {
                $('#longitude').closest('.form-group').find('.error-message').text('Longitude is required.');
                isValid = false;
            }

            if (!isValid) return;


            document.getElementById('phone').addEventListener('input', function() {
                // allow only digits & max 11
                this.value = this.value.replace(/\D/g, '').slice(0, 11);
            });
            Swal.fire({
                title: 'Updating...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            formData.append('_method', 'PUT');

            fetch('{{ route('api.school.update', $school->id) }}', {
                    method: "POST",
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', data.message);
                        setTimeout(() => {
                            window.location.href = "{{ route('school.index') }}";
                        }, 1200);
                    } else {
                        notify('error', 'Update failed');
                    }
                });

        });

        /* ===============================
           ERROR SPANS AUTO ADD (SAME AS CREATE)
        ================================ */
        document.querySelectorAll('.form-control').forEach(function(input) {
            let errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.style.color = 'red';
            input.parentNode.appendChild(errorSpan);
        });

        /* ===============================
           CLEAR ERROR ON INPUT
        ================================ */
        $('#school_name, #school_code, #phone, #email, #address, #state, #city, #pincode, #latitude, #longitude')
            .on('change input', function() {
                $(this).closest('.form-group').find('.error-message').text('');
            });
    </script>
@endsection
