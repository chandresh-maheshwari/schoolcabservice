{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    @php
        $parentIndexRoute = !empty($isSchoolUser)
            ? route('school.parent.index', ['schoolSlug' => $currentSchoolSlug])
            : route('parent.index');
        $parentUpdateRoute = !empty($isSchoolUser)
            ? route('school.parent.update', ['schoolSlug' => $currentSchoolSlug, 'parent' => $child->id])
            : route('parent.update', $child->id);
    @endphp

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a
                                class="breadcrumbLink"href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Parent
                            Details</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @include('child.partials.module_tabs', [
        'activeTab' => 'parent',
        'entityIds' => $moduleEntityIds ?? ['parent' => $child->id, 'child' => $linkedChildId ?? null],
    ])

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Parent Details</h4>
            </div>
            <div class="card-body">
                <style>
                    #editParentForm .password-input-group {
                        position: relative;
                    }

                    #editParentForm .password-input-group .form-control {
                        padding-right: 42px;
                    }

                    #editParentForm .password-input-group .input-group-append {
                        position: absolute;
                        right: 14px;
                        top: 50%;
                        transform: translateY(-50%);
                        display: flex;
                        align-items: center;
                        z-index: 3;
                    }

                    #editParentForm .password-input-group .input-group-text {
                        border: 0;
                        background: transparent;
                        padding: 0;
                        min-height: auto;
                        color: #2d336b;
                        cursor: pointer;
                    }
                </style>
                <form id="editParentForm" enctype="multipart/form-data" method="POST"
                    action="{{ $parentUpdateRoute }}" onsubmit="return window.submitParentUpdate(event);">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="father_name" style="font-weight: bold;">Father Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="father_name" name="father_name"
                            value="{{ $child->father_name }}">
                    </div>
                    <div class="form-group">
                        <label for="mother_name" style="font-weight: bold;">Mother Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="mother_name" name="mother_name"
                            value="{{ $child->mother_name }}">
                    </div>
                    <div class="form-group">
                        <label for="contact_number" style="font-weight: bold;">
                            Contact Number <span style="color:red;">*</span>
                        </label>

                        <input type="tel" class="form-control" id="contact_number" name="contact_number"
                            value="{{ old('contact_number', $child->contact_number) }}" minlength="10" maxlength="11"
                            pattern="[0-9]{10,11}" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="alternative_contact_number" style="font-weight: bold;">
                            Alternative Contact Number
                        </label>

                        <input type="tel" class="form-control" id="alternative_contact_number"
                            name="alternative_contact_number"
                            value="{{ old('alternative_contact_number', $child->alternative_contact_number) }}"
                            minlength="10" maxlength="11" pattern="[0-9]{10,11}" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="email" style="font-weight: bold;">Login Email <span style="color: red;">*</span></label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="{{ old('email', $loginUser->email ?? $child->email) }}" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="login_username" style="font-weight: bold;">Login Username <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="login_username" name="login_username"
                            value="{{ old('login_username', $loginUser->username ?? '') }}" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="password" style="font-weight: bold;">Password
                            <small style="color:#6c757d;">(Leave blank to keep current password)</small>
                        </label>
                        <div class="input-group password-input-group">
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePassword('password')">
                                    <i class="fa fa-eye" id="togglePasswordIcon"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation" style="font-weight: bold;">Confirm Password</label>
                        <div class="input-group password-input-group">
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePassword('password_confirmation')">
                                    <i class="fa fa-eye" id="toggleConfirmPasswordIcon"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address_1" style="font-weight: bold;">Address 1 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="address_1" name="address_1"
                            value="{{ $child->address_1 }}">
                    </div>
                    <div class="form-group">
                        <label for="address_2" style="font-weight: bold;">Address 2</label>
                        <input type="text" class="form-control" id="address_2" name="address_2"
                            value="{{ $child->address_2 }}">
                    </div>
                    <div class="form-group">
                        <label for="state" style="font-weight: bold;">
                            State <span style="color: red;">*</span>
                        </label>

                        <select class="form-control" name="state" id="state">
                            <option value="">Select State</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->name }}" {{ $child->state == $state->name ? 'selected' : '' }}>
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
                            @if (!empty($child->city))
                                <option value="{{ $child->city }}" selected>{{ $child->city }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pincode" style="font-weight: bold;">Pincode <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="pincode" name="pincode"
                            value="{{ $child->pincode }}">
                    </div>
                    <div class="form-group">
                        <label>Father Aadhar Card Image
                            <small style="color:#6c757d;">
                                (Image must be at least 636 × 424 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="fatherImageBtn"
                            onclick="document.getElementById('father_adhaar_card_image').click();">Upload Image</button>
                        <input type="file" id="father_adhaar_card_image" name="father_adhaar_card_image"
                            accept="image/*" style="display:none;" onchange="previewImage(event)">
                        <br>
                        @php
                            $imagePath = $child->father_adhaar_card_image
                                ? public_path('storage/parent/' . $child->father_adhaar_card_image)
                                : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $imageUrl = $imageExists
                                ? asset('storage/parent/' . $child->father_adhaar_card_image)
                                : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName">
                            {{ $imageExists && !$isDefaultImage ? basename($child->father_adhaar_card_image) : 'No image' }}
                        </span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div">
                        <img id="imagePreview" src="{{ $imageUrl }}" alt="Image Preview"
                            style="display: block; width: 100px; height: 100px; margin-top: 10px;">
                        {{-- {{basename($imageUrl) !== 'Default.jpg'}} --}}
                        <button type="button" id="removeImageBtn" class="btn btn-sm"
                            style="display: none; margin-top: 10px; margin-left: 10px;">
                            <i class="fas fa-trash"></i> </button>
                        @if (!$isDefaultImage)
                            <button type="button" id="deleteImageBtn" class="btn btn-sm"
                                style="margin-top: 10px; margin-left: 10px;">
                                <i class="fas fa-trash"></i> </button>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Mother Aadhar Card Image <small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="motherImageBtn"
                            onclick="document.getElementById('mother_adhaar_card_image').click();">Upload Image</button>
                        <input type="file" id="mother_adhaar_card_image" name="mother_adhaar_card_image"
                            accept="image/*" style="display:none;" onchange="previewImage1(event)">
                        <br>
                        @php
                            $imagePath = $child->mother_adhaar_card_image
                                ? public_path('storage/parent/' . $child->mother_adhaar_card_image)
                                : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $imageUrl = $imageExists
                                ? asset('storage/parent/' . $child->mother_adhaar_card_image)
                                : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName1">
                            {{ $imageExists && !$isDefaultImage ? basename($child->mother_adhaar_card_image) : 'No image' }}
                        </span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div">
                        <img id="imagePreview1" src="{{ $imageUrl }}" alt="Image Preview"
                            style="display: block; width: 100px; height: 100px; margin-top: 10px;">
                        {{-- {{basename($imageUrl) !== 'Default.jpg'}} --}}
                        <button type="button" id="removeImageBtn1" class="btn btn-sm"
                            style="display: none; margin-top: 10px; margin-left: 10px;">
                            <i class="fas fa-trash"></i> </button>
                        @if (!$isDefaultImage)
                            <button type="button" id="deleteImageBtn1" class="btn btn-sm"
                                style="margin-top: 10px; margin-left: 10px;">
                                <i class="fas fa-trash"></i> </button>
                        @endif
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" id="submitBtn"
                            style="background-color: #2C9DD4; color: white;">Update</button>
                        <a href="{{ $parentIndexRoute }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Include common icon picker JS -->
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        let isParentCityLoading = false;
        let isParentUpdateSubmitting = false;

        window.togglePassword = function(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.closest('.password-input-group').querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        };

        function setParentSubmitState() {
            const submitBtn = document.getElementById('submitBtn');
            if (!submitBtn) {
                return;
            }

            submitBtn.disabled = isParentCityLoading || isParentUpdateSubmitting;
            submitBtn.style.opacity = submitBtn.disabled ? '0.7' : '';
            submitBtn.style.cursor = submitBtn.disabled ? 'not-allowed' : '';
        }
        /* ===============================
                                   STATE → CITY DROPDOWN (API)
                                ================================ */
        $(document).ready(function() {

            function normalizeCityValue(value) {
                return String(value || '').trim().toLowerCase();
            }

            $('#state').on('change', function() {
                let state = $(this).val();
                loadCities(state, null);
            });

            function loadCities(state, selectedCity = null) {

                if (!state) {
                    $('#city').html('<option value="">Select City</option>');
                    $('#city').prop('disabled', false);
                    isParentCityLoading = false;
                    setParentSubmitState();
                    return;
                }

                isParentCityLoading = true;
                setParentSubmitState();
                $('#city').prop('disabled', true);

                $.ajax({
                    url: "{{ route('api.parent.getCities') }}",
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
                        const normalizedSelectedCity = normalizeCityValue(selectedCity);
                        let cityMatched = false;

                        cities.forEach(function(city) {
                            let cityValue = String(city || '').trim();
                            let selected = normalizedSelectedCity !== '' &&
                                normalizeCityValue(cityValue) === normalizedSelectedCity ? 'selected' : '';
                            if (selected) {
                                cityMatched = true;
                            }

                            $('#city').append(
                                `<option value="${cityValue}" ${selected}>${cityValue}</option>`
                            );
                        });

                        if (!cityMatched && normalizedSelectedCity !== '') {
                            let fallbackCity = String(selectedCity || '').trim();
                            $('#city').append(
                                `<option value="${fallbackCity}" selected>${fallbackCity}</option>`
                            );
                        }

                        if (!cities.length) {
                            $('#city').html('<option value="">No cities found</option>');
                            if (normalizedSelectedCity !== '') {
                                let fallbackCity = String(selectedCity || '').trim();
                                $('#city').append(
                                    `<option value="${fallbackCity}" selected>${fallbackCity}</option>`
                                );
                            }
                        }
                    },
                    error: function(xhr, status) {
                        console.error('City load failed:', status, xhr && xhr.responseText ? xhr.responseText : '');
                        $('#city').html('<option value="">Error loading cities</option>');
                        if (selectedCity) {
                            let fallbackCity = String(selectedCity || '').trim();
                            $('#city').append(
                                `<option value="${fallbackCity}" selected>${fallbackCity}</option>`
                            );
                        }
                    },
                    complete: function() {
                        isParentCityLoading = false;
                        $('#city').prop('disabled', false);
                        setParentSubmitState();
                    }
                });
            }
        });

        /* ===============================
           FORM SUBMIT (YOUR EXISTING CODE)
        ================================ */
        window.submitParentUpdate = function(event) {
            if (event) {
                event.preventDefault();
            }

            if (isParentUpdateSubmitting) {
                return false;
            }

            if (isParentCityLoading) {
                notify('error', 'Please wait, city list is still loading.');
                return false;
            }

            var formData = new FormData(document.getElementById('editParentForm'));

            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            let isValid = true;
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
            if (!formData.get('email')) {
                document.getElementById('email')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Login Email is required.';
                isValid = false;
            }
            if (!formData.get('login_username')) {
                document.getElementById('login_username')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Login Username is required.';
                isValid = false;
            }
            if (formData.get('password') && !formData.get('password_confirmation')) {
                document.getElementById('password_confirmation')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Confirm Password is required.';
                isValid = false;
            }
            if (
                formData.get('password') &&
                formData.get('password_confirmation') &&
                formData.get('password') !== formData.get('password_confirmation')
            ) {
                document.getElementById('password_confirmation')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Password and Confirm Password must match.';
                isValid = false;
            }
            if (!formData.get('address_1')) {
                document.getElementById('address_1')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Address 1 is required.';
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

            if (!isValid) {
                notify('error', 'Please fix the highlighted fields.');
                return false;
            }

            isParentUpdateSubmitting = true;
            setParentSubmitState();

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch("{{ $parentUpdateRoute }}", {
                    method: 'POST', // agar PUT/PATCH use karte ho to wo bhi chalega
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {

                    let data;

                    // 🔹 Safe JSON parse
                    try {
                        data = await res.json();
                    } catch (e) {
                        throw 'Invalid server response';
                    }

                    // 🔹 Backend / HTTP error
                    if (!res.ok || data.success === false) {

                        let errorMsg = data.message || 'Something went wrong';

                        // Laravel validation errors support (future-proof)
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join('<br>');
                        }

                        throw errorMsg; // 👈 REAL MESSAGE THROW
                    }

                    return data;
                })
                .then(data => {
                    Swal.close();

                    notify('success', 'Parent Updated Successfully!');
                    const redirectUrl = data.redirect_url || @json($parentIndexRoute);
                    window.location.replace(redirectUrl);
                })
                .catch(error => {
                    Swal.close();

                    // 🔥 EXACT MESSAGE (backend / JS / network)
                    notify(
                        'error',
                        typeof error === 'string' ?
                        error :
                        (error.message || 'An unexpected error occurred.')
                    );
                })
                .finally(() => {
                    isParentUpdateSubmitting = false;
                    setParentSubmitState();
                });

            return false;
        };

        /* ===============================
           ERROR SPANS AUTO ADD
        ================================ */
        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) {
                let errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.closest('.form-group').appendChild(errorSpan);
            }
        });

        $('#contact_number,#alternative_contact_number,#pincode').on('input paste', function() {
            const value = $(this).val();
            if (value && !/^\d*\.?\d*$/.test(value)) {
                $(this).val(value.slice(0, -1));
            }
        });

        function enforcePhoneDigits(el) {
            el.value = el.value.replace(/\D/g, '').slice(0, 11);
        }

        document.getElementById('contact_number').addEventListener('input', function() {
            enforcePhoneDigits(this);
        });

        document.getElementById('alternative_contact_number').addEventListener('input', function() {
            enforcePhoneDigits(this);
        });

        document.getElementById('father_adhaar_card_image').addEventListener('change', function() {
            $('#fatherImageBtn').next('.error-message').remove();
        })

        document.getElementById('mother_adhaar_card_image').addEventListener('change', function() {
            $('#motherImageBtn').next('.error-message').remove();
        });

        /* ===============================
           CLEAR ERROR ON INPUT
        ================================ */
        $('#father_name, #mother_name, #contact_number, #email, #login_username, #password, #password_confirmation, #state, #city, #pincode, #alternative_contact_number,#address_1,#address_2')
            .on(
                'change input',
                function() {
                    $(this).closest('.form-group').find('.error-message').text('');
                });

        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.parent.parentAdhaarImage', $child->id) }}',
                    csrfToken: document.querySelector('input[name="_token"]').value,
                    imagePreviewSelector: '#imagePreview',
                    buttonSelector: '#deleteImageBtn',
                    nameSelector: '#imageName',
                    successMessage: 'Image deleted successfully.'
                });
            });
        }
        const deleteImageBtn1 = document.getElementById('deleteImageBtn1');
        if (deleteImageBtn1) {
            deleteImageBtn1.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.parent.motherAdhaarImage', $child->id) }}',
                    csrfToken: document.querySelector('input[name="_token"]').value,
                    imagePreviewSelector: '#imagePreview1',
                    buttonSelector: '#deleteImageBtn1',
                    nameSelector: '#imageName1',
                    successMessage: 'Image deleted successfully.'
                });
            });
        }

        document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#father_adhaar_card_image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });
        document.getElementById('removeImageBtn1').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview1',
                imageNameSelector: '#imageName1',
                imageInputSelector: '#mother_adhaar_card_image',
                removeImageBtnSelector: '#removeImageBtn1'
            });
        });

        setParentSubmitState();
    </script>
@endsection
