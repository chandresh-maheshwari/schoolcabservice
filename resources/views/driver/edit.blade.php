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
                            Edit Driver Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Driver Details</h4>
            </div>

            <div class="card-body">
                <style>
                    #editDriverForm .password-input-group {
                        position: relative;
                    }

                    #editDriverForm .password-input-group .form-control {
                        padding-right: 42px;
                    }

                    #editDriverForm .password-input-group .input-group-append {
                        position: absolute;
                        right: 14px;
                        top: 50%;
                        transform: translateY(-50%);
                        display: flex;
                        align-items: center;
                        z-index: 3;
                    }

                    #editDriverForm .password-input-group .input-group-text {
                        border: 0;
                        background: transparent;
                        padding: 0;
                        min-height: auto;
                        color: #2d336b;
                        cursor: pointer;
                    }
                </style>
                <form id="editDriverForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="driver_id" value="{{ $driver->id }}">

                    <div class="form-group">
                        <label>School <span style="color:red;">*</span></label>
                        @if (!empty($isSchoolUser) && !empty($defaultSchoolId))
                            <input type="hidden" name="school_id" id="school_id" value="{{ $defaultSchoolId }}">
                            <input type="text" class="form-control" value="{{ $defaultSchoolName ?? 'School' }}" disabled>
                        @else
                            <select class="form-control" name="school_id" id="school_id">
                                <option value="">Select School</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}" {{ (int) old('school_id', $driver->school_id ?? $defaultSchoolId ?? 0) === (int) $school->id ? 'selected' : '' }}>
                                        {{ $school->school_name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    {{-- Vehicle  --}}
                    <div class="form-group">
                        <label>Vehicle <span style="color:red;">*</span></label>

                        <select class="form-control" name="vehicle_id" id="vehicle_id">
                            <option value="">Select Vehicle</option>

                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" data-school-id="{{ (int) ($vehicle->effective_school_id ?? 0) }}"
                                    {{ old('vehicle_id', $driver->vehicle_id) == $vehicle->id ? 'selected' : '' }}>

                                    {{ $vehicle->vehicle_number }}
                                    @if ($vehicle->vehicleType)
                                        / {{ $vehicle->vehicleType->vehicle_type }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Login Email <span style="color:red;">*</span></label>
                        <input type="email" class="form-control" name="login_email" id="login_email"
                            value="{{ old('login_email', $loginUser->email ?? '') }}" autocomplete="off" readonly>
                    </div>

                    <div class="form-group">
                        <label>Login Username <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" name="login_username" id="login_username"
                            value="{{ old('login_username', $loginUser->username ?? '') }}" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Password <small style="color:#6c757d;">(Leave blank to keep current password)</small></label>
                        <div class="input-group password-input-group">
                            <input type="password" class="form-control" name="password" id="password" autocomplete="new-password">
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePassword('password')">
                                    <i class="fa fa-eye" id="togglePasswordIcon"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="input-group password-input-group">
                            <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" autocomplete="new-password">
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePassword('password_confirmation')">
                                    <i class="fa fa-eye" id="toggleConfirmPasswordIcon"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Driver Name --}}
                    <div class="form-group">
                        <label>Driver Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" name="driver_name" id="driver_name"
                            value="{{ $driver->driver_name }}">
                    </div>
                    <div class="form-group">
                        <label>Driver Phone <span style="color:red;">*</span></label>
                        <input type="tel" class="form-control" name="driver_phone" id="driver_phone"
                            value="{{ old('driver_phone', $driver->driver_phone) }}" minlength="10" maxlength="11"
                            pattern="[0-9]{10,11}" required autocomplete="off">
                    </div>
                    {{-- Driver Image --}}
                    <div class="form-group">
                        <label>Driver Image <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 636 × 424 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="driverImageBtn"
                            onclick="document.getElementById('driver_image').click();">Upload Driver Image</button>
                        <input type="file" id="driver_image" name="driver_image" accept="image/*" style="display:none;"
                            onchange="previewImage(event)">
                        <br>
                        @php
                            $imagePath = $driver->driver_image
                                ? public_path('storage/drivers/' . $driver->driver_image)
                                : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $imageUrl = $imageExists
                                ? asset('storage/drivers/' . $driver->driver_image)
                                : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName">
                            {{ $imageExists && !$isDefaultImage ? basename($driver->driver_image) : 'No image' }}
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
                    {{-- Emergency Number --}}
                    <div class="form-group">
                        <label>Emergency Number (optional)</label>
                        <input type="tel" class="form-control" name="emergency_phone" id="emergency_phone"
                            value="{{ old('emergency_phone', $driver->emergency_phone) }}" minlength="10" maxlength="11"
                            pattern="[0-9]{10,11}" autocomplete="off">
                    </div>

                    {{-- RC Expiry --}}
                    <div class="form-group">
                        <label>License Number <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" name="license_no" id="license_no"
                            value="{{ $driver->license_no }}">
                    </div>

                    {{-- License Expiry --}}
                    <div class="form-group">
                        <label>License Expiry Date <span style="color:red;">*</span></label>
                        <input type="text" class="form-control app-date-picker" name="license_expiry_date" id="license_expiry_date" data-not-past="true" data-field-label="License Expiry Date"
                            value="{{ $driver->license_expiry_date ? \App\Support\DateFormat::formatDate($driver->license_expiry_date, '') : '' }}" placeholder="DD/MM/YYYY" inputmode="numeric" autocomplete="off">
                    </div>

                    {{-- License Image --}}
                    <div class="form-group">
                        <label>License Image / PDF <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="licenseImageBtn"
                            onclick="document.getElementById('license_image').click();">Upload License File</button>
                        <input type="file" id="license_image" name="license_image" accept="image/*,application/pdf"
                            style="display:none;" onchange="previewImage1(event)">
                        <br>
                        @php
                            $imagePath = $driver->license_image
                                ? public_path('storage/drivers/' . $driver->license_image)
                                : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $isPdfFile = $imageExists
                                && strtolower(pathinfo($driver->license_image, PATHINFO_EXTENSION)) === 'pdf';
                            $imageUrl = $imageExists
                                ? ($isPdfFile
                                    ? asset('images/pdf-placeholder.svg')
                                    : asset('storage/drivers/' . $driver->license_image))
                                : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName1">
                            {{ $imageExists && !$isDefaultImage ? basename($driver->license_image) : 'No image' }}
                        </span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div">
                        <img id="imagePreview1" src="{{ $imageUrl }}" alt="Image Preview"
                            style="display: block; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" id="removeImageBtn1" class="btn btn-sm"
                            style="display: none; margin-top: 10px; margin-left: 10px;">
                            <i class="fas fa-trash"></i> </button>
                        @if (!$isDefaultImage)
                            <button type="button" id="deleteImageBtn1" class="btn btn-sm"
                                style="margin-top: 10px; margin-left: 10px;">
                                <i class="fas fa-trash"></i> </button>
                        @endif
                    </div>
                    {{-- Adher No --}}
                    <div class="form-group">
                        <label>Aadhar No <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" name="adher_no" id="adher_no" data-aadhaar-input="true"
                            value="{{ \App\Support\AadhaarFormat::format($driver->adher_no, '') }}">
                    </div>
                    {{-- Adher Image --}}
                    <div class="form-group">
                        <label>Aadhar Image / PDF <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="adherImageBtn"
                            onclick="document.getElementById('adher_card_iamge').click();">Upload Aadhar File</button>
                        <input type="file" id="adher_card_iamge" name="adher_card_iamge"
                            accept="image/*,application/pdf"
                            style="display:none;" onchange="previewImage2(event)">
                        <br>
                        @php
                            $imagePath = $driver->adher_card_iamge
                                ? public_path('storage/drivers/' . $driver->adher_card_iamge)
                                : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $isPdfFile = $imageExists
                                && strtolower(pathinfo($driver->adher_card_iamge, PATHINFO_EXTENSION)) === 'pdf';
                            $imageUrl = $imageExists
                                ? ($isPdfFile
                                    ? asset('images/pdf-placeholder.svg')
                                    : asset('storage/drivers/' . $driver->adher_card_iamge))
                                : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName2">
                            {{ $imageExists && !$isDefaultImage ? basename($driver->adher_card_iamge) : 'No image' }}
                        </span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div">
                        <img id="imagePreview2" src="{{ $imageUrl }}" alt="Image Preview"
                            style="display: block; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" id="removeImageBtn2" class="btn btn-sm"
                            style="display: none; margin-top: 10px; margin-left: 10px;">
                            <i class="fas fa-trash"></i> </button>
                        @if (!$isDefaultImage)
                            <button type="button" id="deleteImageBtn2" class="btn btn-sm"
                                style="margin-top: 10px; margin-left: 10px;">
                                <i class="fas fa-trash"></i> </button>
                        @endif
                    </div>

                    {{-- Exper No --}}
                    <div class="form-group">
                        <label>Experience Years <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" name="experience_years" id="experience_years"
                            value="{{ $driver->experience_years }}">
                    </div>

                    {{-- Joining Date --}}
                    <div class="form-group">
                        <label>Joining Date <span style="color:red;">*</span></label>
                        <input type="text" class="form-control app-date-picker" name="joining_date" id="joining_date"
                            value="{{ $driver->joining_date ? \App\Support\DateFormat::formatDate($driver->joining_date, '') : '' }}" placeholder="DD/MM/YYYY" inputmode="numeric" autocomplete="off">
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ route('driver.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
    <script>
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

        /* UPDATE VALIDATION + API CALL */
        $('#updateBtn').on('click', function() {
            // alert('hello');

            $('.error-message').remove();
            let isValid = true;

            function showError(el, msg) {
                const $field = $(el);
                const $target = $field.closest('.input-group').length ? $field.closest('.input-group') : $field;
                $target.after(`<span class="error-message" style="color:red;">${msg}</span>`);
                isValid = false;
            }

            // 🔹 TEXT / SELECT VALIDATION
            if (!$('input[name="driver_name"]').val().trim()) {
                showError('input[name="driver_name"]', 'Driver Name is required');
            }

            if (!$('input[name="login_email"]').val().trim()) {
                showError('input[name="login_email"]', 'Login Email is required');
            }

            if (!$('input[name="login_username"]').val().trim()) {
                showError('input[name="login_username"]', 'Login Username is required');
            }

            if ($('input[name="password"]').val() && !$('input[name="password_confirmation"]').val()) {
                showError('input[name="password_confirmation"]', 'Confirm Password is required');
            }

            if (
                $('input[name="password"]').val() &&
                $('input[name="password_confirmation"]').val() &&
                $('input[name="password"]').val() !== $('input[name="password_confirmation"]').val()
            ) {
                showError('input[name="password_confirmation"]', 'Password and Confirm Password must match');
            }

            if (!$('input[name="driver_phone"]').val()) {
                showError('input[name="driver_phone"]', 'Driver Phone is required');
            }

            if (!$('input[name="license_no"]').val()) {
                showError('input[name="license_no"]', 'License Number is required');
            }

            if (!$('input[name="license_expiry_date"]').val()) {
                showError('input[name="license_expiry_date"]', 'License Expiry Date is required');
            } else if (!window.parseDisplayDate($('input[name="license_expiry_date"]').val())) {
                showError('input[name="license_expiry_date"]', 'Use date format DD/MM/YYYY');
            } else if (window.isDisplayDateBeforeToday($('input[name="license_expiry_date"]').val())) {
                showError('input[name="license_expiry_date"]', 'License Expiry Date cannot be before ' + window.getTodayDisplayDate());
            }

            if (!$('input[name="adher_no"]').val()) {
                showError('input[name="adher_no"]', 'Adher Number is required');
            } else if (!window.isValidAadhaarNumber($('input[name="adher_no"]').val())) {
                showError('input[name="adher_no"]', 'Aadhar Number must be 12 digits in format 1122 3364 9658');
            }

            if (!$('input[name="experience_years"]').val()) {
                showError('input[name="experience_years"]', 'Experience Years is required');
            }
            if (!$('input[name="joining_date"]').val()) {
                showError('input[name="joining_date"]', 'Joining Date is required');
            } else if (!window.parseDisplayDate($('input[name="joining_date"]').val())) {
                showError('input[name="joining_date"]', 'Use date format DD/MM/YYYY');
            }

            function enforcePhoneLength(input) {
                input.value = input.value.replace(/\D/g, '').slice(0, 11);
            }

            document.getElementById('driver_phone').addEventListener('input', function() {
                enforcePhoneLength(this);
            });

            document.getElementById('emergency_phone').addEventListener('input', function() {
                enforcePhoneLength(this);
            });
            var imageInput = document.getElementById('driver_image');
            var imagePreview = document.getElementById('imagePreview');
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "")) {
                $('#driverImageBtn').after(
                    '<span class="error-message" style="color: red;">Driver Image is required.</span>');
                isValid = false;
            }
            var imageInput1 = document.getElementById('license_image');
            var imagePreview1 = document.getElementById('imagePreview1');
            var imageError1 = document.getElementById('imageError');
            var currentImageSrc1 = imagePreview1.getAttribute('src');
            var isDefaultImage1 = currentImageSrc1.includes('Default.jpg');
            if (!imageInput1.files.length && isDefaultImage1 || (currentImageSrc1 == "#" || currentImageSrc1 ==
                    "")) {
                $('#licenseImagBtn').after(
                    '<span class="error-message" style="color: red;">License Image is required.</span>');
                isValid = false;
            }

            var imageInput2 = document.getElementById('adher_card_iamge');
            var imagePreview2 = document.getElementById('imagePreview2');
            var imageError2 = document.getElementById('imageError');
            var currentImageSrc2 = imagePreview2.getAttribute('src');
            var isDefaultImage2 = currentImageSrc2.includes('Default.jpg');
            if (!imageInput2.files.length && isDefaultImage2 || (currentImageSrc2 == "#" || currentImageSrc2 ==
                    "")) {
                $('#adherImageBtn').after(
                    '<span class="error-message" style="color: red;">Adher Card Image is required.</span>');
                isValid = false;
            }

            function isAlphaNumeric(value) {
                return /^[a-zA-Z0-9]+$/.test(value);
            }

            if (!isAlphaNumeric($('input[name="driver_phone"]').val())) {
                // showError('input[name="vehicle_number"]', 'Only letters and numbers allowed');
            }

            if (!isAlphaNumeric($('input[name="emergency_phone"]').val())) {
                // showError('input[name="rc_number"]', 'Only letters and numbers allowed');
            }

            if (!isAlphaNumeric($('input[name="license_no"]').val())) {
                // showError('input[name="license_no"]', 'Only letters and numbers allowed');
            }
            if (!isAlphaNumeric($('input[name="adher_no"]').val())) {
                // showError('input[name="seating_capacity"]', 'Only letters and numbers allowed');
            }
            if (!isAlphaNumeric($('input[name="experience_years"]').val())) {
                // showError('input[name="seating_capacity"]', 'Only letters and numbers allowed');
            }
            // if (!isValid) return;

            // 🔹 SUBMIT
            let formData = new FormData(document.getElementById('editDriverForm'));

            let driverId = $('#driver_id').val();
            Swal.fire({
                title: 'Updating...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(`/api/driver/${driverId}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'X-HTTP-Method-Override': 'PUT',
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {

                    let data;
                    try {
                        data = await res.json();
                    } catch (e) {
                        throw 'Invalid server response';
                    }

                    if (!res.ok || data.success === false) {
                        throw data.message || 'Update failed';
                    }

                    return data;
                })
                .then(() => {
                    Swal.close();
                    notify('success', 'Driver updated successfully!');
                    setTimeout(() => {
                        window.location.href = '{{ route('driver.index') }}';
                    }, 1500);
                })
                .catch(error => {
                    Swal.close();
                    notify(
                        'error',
                        typeof error === 'string' ?
                        error :
                        (error.message || 'Unexpected error')
                    );
                });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });

        document.getElementById('driver_image').addEventListener('change', function() {
            $('#driverImageBtn').next('.error-message').remove();
        })

        document.getElementById('license_image').addEventListener('change', function() {
            $('#licenseImageBtn').next('.error-message').remove();
        });
        document.getElementById('adher_card_iamge').addEventListener('change', function() {
            $('#adherImageBtn').next('.error-message').remove();
        });

        function isPastDate(dateValue) {
            if (!dateValue) return false;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const selectedDate = new Date(dateValue);

            if (isNaN(selectedDate.getTime())) return false;

            return selectedDate < today;
        }

        $(document).ready(function() {
            var $schoolSelect = $('#school_id');

            function schoolHasRealOptions() {
                if (!$schoolSelect.length || !$schoolSelect.is('select')) {
                    return true;
                }

                return Array.from($schoolSelect[0].options || []).some(function(option) {
                    return String(option.value || '').trim() !== '';
                });
            }

            function shouldShowSchoolEmptyAlert() {
                return !schoolHasRealOptions() && @json(empty($hasAnySchools));
            }

            function showSchoolEmptyAlert() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Alert',
                    text: 'No schools are currently available. Please add a school first to continue.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            }

            if ($schoolSelect.length && $schoolSelect.is('select')) {
                if ($schoolSelect.hasClass("select2-hidden-accessible")) {
                    $schoolSelect.on('select2:opening', function(e) {
                        if (shouldShowSchoolEmptyAlert()) {
                            e.preventDefault();
                            showSchoolEmptyAlert();
                        }
                    });
                } else {
                    $schoolSelect.on('mousedown', function(e) {
                        if (shouldShowSchoolEmptyAlert()) {
                            e.preventDefault();
                            $(this).blur();
                            showSchoolEmptyAlert();
                            return false;
                        }
                    });
                }
            }

            $(document).on('mousedown click', '.common-select2, .nice-select, .select2-container', function(e) {
                if (!shouldShowSchoolEmptyAlert()) {
                    return;
                }

                var $wrapper = $(this);
                var isSchoolWrapper = $wrapper.is($schoolSelect.prev('.common-select2'))
                    || $wrapper.is($schoolSelect.next('.nice-select'))
                    || $wrapper.is($schoolSelect.next('.select2'))
                    || $wrapper.closest('.nice-select, .common-select2, .select2').is($schoolSelect.prev('.common-select2'))
                    || $wrapper.closest('.nice-select, .common-select2, .select2').is($schoolSelect.next('.nice-select'))
                    || $wrapper.closest('.nice-select, .common-select2, .select2').is($schoolSelect.next('.select2'));

                if (!isSchoolWrapper) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();
                showSchoolEmptyAlert();
                return false;
            });

        });

        $('input[name="driver_phone"], input[name="emergency_phone"], input[name="license_no"], input[name="adher_no"],input[name="experience_years"]')
            .on('input', function() {

                let value = this.value;

                // remove everything except A-Z a-z 0-9
                let cleanedValue = value.replace(/[^a-zA-Z0-9]/g, '');

                if (value !== cleanedValue) {
                    this.value = cleanedValue;
                }
            });

        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.driver.driverImage', $driver->id) }}',
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
                    url: '{{ route('api.driver.licenseImage', $driver->id) }}',
                    csrfToken: document.querySelector('input[name="_token"]').value,
                    imagePreviewSelector: '#imagePreview1',
                    buttonSelector: '#deleteImageBtn1',
                    nameSelector: '#imageName1',
                    successMessage: 'Image deleted successfully.'
                });
            });
        }
        const deleteImageBtn2 = document.getElementById('deleteImageBtn2');
        if (deleteImageBtn2) {
            deleteImageBtn2.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.driver.adharCardImage', $driver->id) }}',
                    csrfToken: document.querySelector('input[name="_token"]').value,
                    imagePreviewSelector: '#imagePreview2',
                    buttonSelector: '#deleteImageBtn2',
                    nameSelector: '#imageName2',
                    successMessage: 'Image deleted successfully.'
                });
            });
        }

        document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#vechicle_image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });
        document.getElementById('removeImageBtn1').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview1',
                imageNameSelector: '#imageName1',
                imageInputSelector: '#license_image',
                removeImageBtnSelector: '#removeImageBtn1'
            });
        });
        document.getElementById('removeImageBtn2').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview2',
                imageNameSelector: '#imageName2',
                imageInputSelector: '#adher_card_iamge',
                removeImageBtnSelector: '#removeImageBtn2'
            });
        });
    </script>
@endsection
