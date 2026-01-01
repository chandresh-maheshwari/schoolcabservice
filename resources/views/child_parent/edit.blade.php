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
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Child And Parent
                            Details</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Child And Parent Details</h4>
            </div>
            <div class="card-body">
                <form id="childParentForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="childName" style="font-weight: bold;">Child Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="child_name" name="child_name" value="{{ $child->child_name }}" required>
                    </div>
                    <div class="form-group">
                        <label for="gender" style="font-weight: bold;">Gender <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="gender" name="gender" value="{{ $child->gender }}" required>
                    </div>
                    <div class="form-group">
                        <label>Date Of Birth <span style="color:red;">*</span></label>
                        <input type="date" class="form-control" name="date_of_birth" id="date_of_birth" value="{{ $child->date_of_birth }}">

                    </div>
                    <div class="form-group">
                        <label for="class" style="font-weight: bold;">Class <span style="color: red;">*</span></label>
                        <input type="class" class="form-control" id="class" name="class"value="{{ $child->class }}">
                    </div>
                    <div class="form-group">
                        <label for="section" style="font-weight: bold;">Section <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="section" name="section" value="{{ $child->section }}">
                    </div>
                    <div class="form-group">
                        <label for="father_name" style="font-weight: bold;">Father Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="father_name" name="father_name" value="{{ $child->father_name }}">
                    </div>
                    <div class="form-group">
                        <label for="mother_name" style="font-weight: bold;">Mother Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="mother_name" name="mother_name" value="{{ $child->mother_name }}">
                    </div>
                    <div class="form-group">
                        <label for="contact_number" style="font-weight: bold;">Contact Number <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number"  value="{{ $child->contact_number }}">
                    </div>
                    <div class="form-group">
                        <label for="alternative_contact_number" style="font-weight: bold;">AlterNative Contact Number <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="alternative_contact_number"
                            name="alternative_contact_number" value="{{ $child->alternative_contact_number }}">
                    </div>
                    <div class="form-group">
                        <label for="email" style="font-weight: bold;">Email</label>
                        <input type="text" class="form-control" id="email" name="email" value="{{ $child->email }}" readonly>
                    </div>
                    <div class="form-group">
                        <label for="address_1" style="font-weight: bold;">Address 1 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="address_1" name="address_1" value="{{ $child->address_1 }}">
                    </div>
                    <div class="form-group">
                        <label for="address_2" style="font-weight: bold;">Address 2 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="address_2" name="address_2" value="{{ $child->address_2 }}">
                    </div>
                    <div class="form-group">
                        <label for="state" style="font-weight: bold;">
                            State <span style="color: red;">*</span>
                        </label>

                        <select class="form-control" name="state" id="state">
                            <option value="">Select State</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->name }}"
                                    {{ $child->state == $state->name ? 'selected' : '' }}>
                                    {{ $state->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="city" style="font-weight: bold;">
                            City <span style="color: red;">*</span>
                        </label>
                      <select class="form-control" name="city" id="city">
    <option value="">Select City</option>
</select>
                    </div>
                    <div class="form-group">
                        <label for="pincode" style="font-weight: bold;">Pincode <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="pincode" name="pincode" value="{{ $child->pincode }}">
                    </div>
                    <div class="form-group">
                        <label>School Name <span style="color:red;">*</span></label>
                       <select class="form-control" name="school_id">
                            <option value="">Select School Name</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->_id }}"
                                    {{ $child->school_id == $school->school_name ? 'selected' : '' }}>
                                    {{ $school->school_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pickup Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="pickup_id">
                              <option value="">Select Pickup Name</option>
                            @foreach ($stops as $stop)
                                <option value="{{ $stop->_id }}"
                                    {{ $child->pickup_id == $stop->pickup_name ? 'selected' : '' }}>
                                    {{ $stop->pickup_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Stop Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="stop_id">
                            <option value="">Select Stop Name</option>
                            @foreach ($stops as $stop)
                                <option value="{{ $stop->_id }}"
                                    {{ $child->stop_id == $stop->stop_name ? 'selected' : '' }}>
                                    {{ $stop->stop_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Route Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="route_id">
                              <option value="">Select Route Name</option>
                            @foreach ($routes as $route)
                                <option value="{{ $route->_id }}"
                                    {{ $child->route_id == $route->name ? 'selected' : '' }}>
                                    {{ $route->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Update</button>
                    <a href="{{ route('childParent.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
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
        $(document).ready(function () {

    let selectedState = "{{ $child->state }}";
    let selectedCity  = "{{ $child->city }}";
    if (selectedState) {
        loadCities(selectedState, selectedCity);
    }

    $('#state').on('change', function () {
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
            url: "{{ route('api.childParent.getCities') }}",
            type: "POST",
            data: {
                state: state,
                _token: "{{ csrf_token() }}"
            },
            success: function (cities) {

                $('#city').empty().append('<option value="">Select City</option>');

                cities.forEach(function (city) {

                    let selected = (selectedCity === city) ? 'selected' : '';

                    $('#city').append(
                        `<option value="${city}" ${selected}>${city}</option>`
                    );
                });
            },
            error: function () {
                $('#city').html('<option value="">Error loading cities</option>');
            }
        });
    }
        });

        /* ===============================
           FORM SUBMIT (YOUR EXISTING CODE)
        ================================ */
        document.getElementById('submitBtn').addEventListener('click', function() {

            var formData = new FormData(document.getElementById('childParentForm'));

            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            let isValid = true;

            if (!formData.get('child_name')) {
                document.getElementById('child_name')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Child Name is required.';
                isValid = false;
            }

            if (!formData.get('gender')) {
                document.getElementById('gender')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Gender is required.';
                isValid = false;
            }

            if (!formData.get('date_of_birth')) {
                document.getElementById('date_of_birth')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Date Of Birth is required.';
                isValid = false;
            }
            if (!formData.get('class')) {
                document.getElementById('class')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Class is required.';
                isValid = false;
            }
            if (!formData.get('section')) {
                document.getElementById('section')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Section is required.';
                isValid = false;
            }
            if (!formData.get('father_name')) {
                document.getElementById('father_name')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Father Name is required.';
                isValid = false;
            }
            if (!formData.get('mother_name')) {
                document.getElementById('mother_name')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Mother Name is required.';
                isValid = false;
            }
            if (!formData.get('contact_number')) {
                document.getElementById('contact_number')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Contact Number is required.';
                isValid = false;
            }
            if (!formData.get('alternative_contact_number')) {
                document.getElementById('alternative_contact_number')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'AlterNative Contact Number is required.';
                isValid = false;
            }
            if (!formData.get('address_1')) {
                document.getElementById('address_1')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Address 1 is required.';
                isValid = false;
            }
            if (!formData.get('address_2')) {
                document.getElementById('address_2')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Address 2 is required.';
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
            if (!formData.get('pincode')) {
                document.getElementById('pincode')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Pincode is required.';
                isValid = false;
            }
            if (!formData.get('school_id')) {
                document.getElementById('school_id')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'School Id is required.';
                isValid = false;
            }
            if (!formData.get('pickup_id')) {
                document.getElementById('pickup_id')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Pickup Id is required.';
                isValid = false;
            }
            if (!formData.get('stop_id')) {
                document.getElementById('stop_id')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Stop Id is required.';
                isValid = false;
            }
            if (!formData.get('route_id')) {
                document.getElementById('route_id')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Route Id is required.';
                isValid = false;
            }
            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

               formData.append('_method', 'PUT');
            fetch("{{ route('api.childParent.update', $child->_id) }}", {
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
                        notify('success', 'Child and Parent created Successfully!');
                        setTimeout(() => {
                            window.location.href = '{{ route('childParent.index') }}';
                        }, 1500);
                    } else {
                        notify('error', data.message || 'There was an error creating the Child and Parent.');
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

        $('#contact_number,#alternative_contact_number,#pincode').on('input paste', function() {
            const value = $(this).val();
            if (value && !/^\d*\.?\d*$/.test(value)) {
                $(this).val(value.slice(0, -1));
            }
        });

        /* ===============================
           CLEAR ERROR ON INPUT
        ================================ */
        $('#child_name, #gender, #date_of_birth, #class, #section, #father_name, #mother_name, #contact_number, #email, #state, #city, #pincode, #school_id, #pickup_id,#stop_id,#route_id,#alternative_contact_number,#address_1,#address_2')
            .on(
                'change input',
                function() {
                    $(this).closest('.form-group').find('.error-message').text('');
                });
    </script>
@endsection
