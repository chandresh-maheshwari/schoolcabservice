{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    @php
        $isSchoolPanel = request()->route('schoolSlug') !== null && \Illuminate\Support\Facades\Route::currentRouteNamed('school.school.*');
        $schoolSlug = request()->route('schoolSlug');
        $dashboardRoute = $isSchoolPanel ? route('school.dashboard', ['schoolSlug' => $schoolSlug]) : route('admin_layout.index');
        $schoolIndexRoute = $isSchoolPanel ? route('school.school.index', ['schoolSlug' => $schoolSlug]) : route('school.index');
        $schoolEditRoute = $isSchoolPanel
            ? route('school.school.edit', ['schoolSlug' => $schoolSlug, 'school' => $school->id])
            : route('school.edit', $school->id);
        $updateUrl = $isSchoolPanel
            ? route('school.school.update', ['schoolSlug' => $schoolSlug, 'school' => $school->id])
            : route('school.update', $school->id);
        $getCitiesUrl = $isSchoolPanel
            ? route('school.school.getCities', ['schoolSlug' => $schoolSlug])
            : route('school.getCities');
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

                    <hr>
                    <h5 class="mb-3">School Branding</h5>

                    <div class="form-group">
                        <label>Header Title</label>
                        <input type="text" class="form-control" id="header_title" name="header_title"
                            value="{{ old('header_title', $school->header_title) }}" placeholder="Optional header title">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Primary Color</label>
                                <input type="color" class="form-control" id="primary_color" name="primary_color"
                                    value="{{ old('primary_color', $school->primary_color ?: '#2D336B') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Secondary Color</label>
                                <input type="color" class="form-control" id="secondary_color" name="secondary_color"
                                    value="{{ old('secondary_color', $school->secondary_color ?: '#7886c7') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Header Logo</label>
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        @if (!empty($school->logo_path))
                            <div class="mt-2">
                                <img src="{{ \Illuminate\Support\Str::startsWith($school->logo_path, 'storage/') ? asset($school->logo_path) : asset('storage/'.$school->logo_path) }}"
                                    alt="Current logo" style="max-height: 60px;">
                            </div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Mini Logo</label>
                        <input type="file" class="form-control" id="logo_mini" name="logo_mini" accept="image/*">
                        @if (!empty($school->logo_mini_path))
                            <div class="mt-2">
                                <img src="{{ \Illuminate\Support\Str::startsWith($school->logo_mini_path, 'storage/') ? asset($school->logo_mini_path) : asset('storage/'.$school->logo_mini_path) }}"
                                    alt="Current mini logo" style="max-height: 40px;">
                            </div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Favicon</label>
                        <input type="file" class="form-control" id="favicon" name="favicon" accept="image/*">
                        @if (!empty($school->favicon_path))
                            <div class="mt-2">
                                <img src="{{ \Illuminate\Support\Str::startsWith($school->favicon_path, 'storage/') ? asset($school->favicon_path) : asset('storage/'.$school->favicon_path) }}"
                                    alt="Current favicon" style="max-height: 32px;">
                            </div>
                        @endif
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">
                        Update
                    </button>
                    <a href="{{ $schoolIndexRoute }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        document.getElementById('phone').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 11);
        });

        function showSchoolUpdateError(message, fieldId = null) {
            const safeMessage = message || 'Update failed';

            if (fieldId) {
                const field = document.getElementById(fieldId);
                const errorSpan = field?.closest('.form-group')?.querySelector('.error-message');
                if (errorSpan) {
                    errorSpan.textContent = safeMessage;
                }
            }

            setTimeout(function() {
                notify('error', safeMessage);
            }, 150);
        }
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
                    url: @json($getCitiesUrl),
                    type: "POST",
                    timeout: 15000,
                    data: {
                        state: state,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        let cities = [];
                        if (Array.isArray(response)) {
                            cities = response;
                        } else if (response && Array.isArray(response.cities)) {
                            cities = response.cities;
                        } else if (response && Array.isArray(response.data)) {
                            cities = response.data;
                        }

                        $('#city').empty().append('<option value="">Select City</option>');

                        cities.forEach(function(city) {

                            let selected = (selectedCity === city) ? 'selected' : '';

                            $('#city').append(
                                `<option value="${city}" ${selected}>${city}</option>`
                            );
                        });

                        if (!cities.length) {
                            $('#city').html('<option value="">No cities found</option>');
                        }
                    },
                    error: function(xhr, status) {
                        console.error('City load failed:', status, xhr && xhr.responseText ? xhr.responseText : '');
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
            Swal.fire({
                title: 'Updating...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            formData.append('_method', 'PUT');

            fetch(@json($updateUrl), {
                    method: "POST",
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {
                    const data = await res.json();

                    if (!res.ok) {
                        const firstError = data?.errors ? Object.values(data.errors).flat()[0] : null;
                        throw {
                            message: firstError || data.message || 'Update failed',
                            fieldId: data?.errors?.school_code?.[0] ? 'school_code' : null,
                        };
                    }

                    return data;
                })
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', data.message);
                        setTimeout(() => {
                            window.location.href = @json($isSchoolPanel ? $schoolEditRoute : $schoolIndexRoute);
                        }, 1200);
                    } else {
                        showSchoolUpdateError(data.message || 'Update failed');
                    }
                })
                .catch((error) => {
                    Swal.close();

                    if (typeof error === 'string') {
                        showSchoolUpdateError(error);
                        return;
                    }

                    if (error && typeof error === 'object') {
                        showSchoolUpdateError(error.message, error.fieldId || null);
                        return;
                    }

                    showSchoolUpdateError('Update failed');
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
        $('#school_name, #school_code, #phone, #email, #address, #state, #city, #pincode, #latitude, #longitude, #header_title, #primary_color, #secondary_color, #logo, #logo_mini, #favicon')
            .on('change input', function() {
                $(this).closest('.form-group').find('.error-message').text('');
            });
    </script>
@endsection
