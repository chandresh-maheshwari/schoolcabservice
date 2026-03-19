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
                            Add Driver Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Driver Details</h4>
            </div>

            <div class="card-body">
                <form id="driverForm" enctype="multipart/form-data">
                    @csrf

                    {{-- Driver Name --}}
                    <div class="form-group">
                        <label>Driver Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="driver_name" name="driver_name" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Login Email <span style="color:red;">*</span></label>
                        <input type="email" class="form-control" id="login_email" name="login_email" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Login Username <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="login_username" name="login_username" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Password <span style="color:red;">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label>Confirm Password <span style="color:red;">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                    </div>

                    {{-- Vehicle  --}}
                    <div class="form-group">
                        <label>Vehicle <span style="color:red;">*</span></label>
                        <select class="form-control" name="vehicle_id" id="vehicle_id">
                            <option value="">Select Vehicle</option>

                            @foreach ($vehicle as $type)
                                <option value="{{ $type->id }}">
                                    {{ $type->vehicle_number }}
                                    @if (!empty($type->vehicle_type_name))
                                        / {{ $type->vehicle_type_name }}
                                    @endif
                                </option>
                            @endforeach

                        </select>
                    </div>


                    {{-- Driver Phone --}}
                    <div class="form-group">
                        <label>Driver Phone <span style="color:red;">*</span></label>
                        <input type="tel" class="form-control" id="driver_phone" name="driver_phone" minlength="10"
                            maxlength="11" pattern="[0-9]{10,11}" placeholder="Enter 10 or 11 digit number" required
                            autocomplete="off">
                    </div>

                    {{-- Driver Image --}}
                    <div class="form-group">
                        <label>Driver Image <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 636 × 424 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="driverImageBtn"
                            onclick="document.getElementById('driver_image').click();">Driver Upload Image</button>
                        <input type="file" id="driver_image" name="driver_image" accept="image/*" style="display:none;"
                            onchange="previewImage(event)">
                        <span id="imageName"></span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn"><i
                                class="fas fa-trash"></i></button>
                    </div>

                    {{-- Emergency Number --}}
                    <div class="form-group">
                        <label>Emergency Number <span style="color:red;">*</span></label>
                        <input type="tel" class="form-control" id="emergency_phone" name="emergency_phone"
                            minlength="10" maxlength="11" pattern="[0-9]{10,11}" placeholder="Enter 10 or 11 digit number"
                            autocomplete="off">
                    </div>
                    {{-- License Number --}}
                    <div class="form-group">
                        <label>License Number <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="license_no" name="license_no" autocomplete="off">
                    </div>

                    {{-- License Expiry --}}
                    <div class="form-group">
                        <label>License Expiry Date <span style="color:red;">*</span></label>
                        <input type="date" class="form-control" name="license_expiry_date" id="license_expiry_date"
                            min="{{ date('Y-m-d') }}">

                    </div>

                    {{-- License Image --}}
                    <div class="form-group">
                        <label>License Image / PDF <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="licenseImageBtn"
                            onclick="document.getElementById('license_image').click();">Upload File</button>
                        <input type="file" id="license_image" name="license_image" accept="image/*,application/pdf"
                            style="display:none;" onchange="previewImage1(event)">
                        <span id="imageName1"></span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview1" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn1"><i
                                class="fas fa-trash"></i></button>
                    </div>
                    {{-- Adhger Number --}}
                    <div class="form-group">
                        <label>Aadhar No <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="adher_no" name="adher_no" autocomplete="off">
                    </div>

                    {{-- Insurance Expiry --}}
                    <div class="form-group">
                        <label>Insurance Expiry Date <span style="color:red;">*</span></label>
                        <input type="date" class="form-control" name="insurance_expiry_date"
                            id="insurance_expiry_date" min="{{ date('Y-m-d') }}">

                    </div>

                    {{-- Adher Card Image --}}
                    <div class="form-group">
                        <label>Aadhar Card Image / PDF <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="adherImageBtn"
                            onclick="document.getElementById('adher_card_iamge').click();">Upload File</button>
                        <input type="file" id="adher_card_iamge" name="adher_card_iamge"
                            accept="image/*,application/pdf"
                            style="display:none;" onchange="previewImage2(event)">
                        <span id="imageName2"></span>

                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview2" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn2"><i
                                class="fas fa-trash"></i></button>
                    </div>

                    <div class="form-group">
                        <label>Experience Year <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="experience_years" name="experience_years"
                            autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label>Joining Date <span style="color:red;">*</span></label>
                        <input type="date" class="form-control" name="joining_date" id="joining_date">

                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                    <a href="{{ route('driver.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('driverForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('driver_name')) showError('#driver_name', 'Driver Name is required');
            if (!formData.get('login_email')) showError('#login_email', 'Login Email is required');
            if (!formData.get('login_username')) showError('#login_username', 'Login Username is required');
            if (!formData.get('password')) showError('#password', 'Password is required');
            if (!formData.get('password_confirmation')) showError('#password_confirmation', 'Confirm Password is required');
            if (formData.get('password') && formData.get('password_confirmation') && formData.get('password') !== formData.get('password_confirmation')) {
                showError('#password_confirmation', 'Password and Confirm Password must match');
            }
            let vehicleSelect = document.getElementById('vehicle_id');
            let vehicleValue = vehicleSelect.value;

            if (vehicleValue === "") {
                $('#vehicle_id').after(
                    '<span class="error-message" style="color:red;">Vehicle is required</span>'
                );
                isValid = false;
            }
            if (!formData.get('driver_phone')) showError('#driver_phone', 'Driver Phone is required');
            if (!formData.get('emergency_phone')) showError('#emergency_phone', 'Emergency Phone is required');
            if (!formData.get('license_no')) showError('#license_no', 'License Number is required');
            if (!formData.get('license_expiry_date')) showError('#license_expiry_date',
                'License Expiry Date is required');
            if (!formData.get('adher_no')) showError('#adher_no', ' Adher Card is required');
            if (!formData.get('experience_years')) showError('#experience_years',
                'Experience Year is required');
            if (!formData.get('joining_date')) showError('#joining_date',
                'Joining Date is required');


            function isValidPositive(value) {
                return /^[a-zA-Z0-9]+$/.test(value);
            }

            if (!isValidPositive($('input[name="driver_phone"]').val())) {}

            if (!isValidPositive($('input[name="emergency_phone"]').val())) {}

            if (!isValidPositive($('input[name="license_no"]').val())) {}
            if (!isValidPositive($('input[name="adher_no"]').val())) {}
            if (!isValidPositive($('input[name="experience_years"]').val())) {}


            function onlyPhoneDigits(el) {
                el.value = el.value.replace(/\D/g, '').slice(0, 11);
            }

            document.getElementById('driver_phone').addEventListener('input', function() {
                onlyPhoneDigits(this);
            });

            document.getElementById('emergency_phone').addEventListener('input', function() {
                onlyPhoneDigits(this);
            });
            var imageInput = document.getElementById('driver_image');
            var imagePreview = document.getElementById('imagePreview');
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#driverImageBtn').after(
                    '<span class="error-message" style="color: red;">Driver Image is required.</span>');
                isValid = false;
            }
            var imageInput1 = document.getElementById('license_image');
            var imagePreview1 = document.getElementById('imagePreview1');
            var imageError1 = document.getElementById('imageError');
            var currentImageSrc1 = imagePreview1.getAttribute('src');
            var isDefaultImage1 = currentImageSrc1.includes('Default.jpg');
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput1.files.length && isDefaultImage1 || (currentImageSrc1 == "#" || currentImageSrc1 ==
                    "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#licenseImageBtn').after(
                    '<span class="error-message" style="color: red;">License Image is required.</span>');
                isValid = false;
            }
            var imageInput2 = document.getElementById('adher_card_iamge');
            var imagePreview2 = document.getElementById('imagePreview2');
            var imageError2 = document.getElementById('imageError');
            var currentImageSrc2 = imagePreview2.getAttribute('src');
            var isDefaultImage2 = currentImageSrc2.includes('Default.jpg');
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput2.files.length && isDefaultImage2 || (currentImageSrc2 == "#" || currentImageSrc2 ==
                    "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#adherImageBtn').after(
                    '<span class="error-message" style="color: red;"> Adher Card is required.</span>');
                isValid = false;
            }
            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.driver.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
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

                        let errorMsg = data.message || 'Something went wrong';

                        if (data.errors) {
                            errorMsg = Object.values(data.errors)[0][0];
                        }

                        throw errorMsg;
                    }

                    return data;
                })
                .then(data => {
                    Swal.close();

                    notify('success', 'Driver created successfully!');
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
            $(this).next('.error-message').remove();
        });

        $(document).on('change', '#vehicle_id', function() {
            $(this).next('.error-message').remove();
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

        // function isPastDate(selectedDate) {
        //     const today = new Date();
        //     today.setHours(0, 0, 0, 0); // remove time

        //     const inputDate = new Date(selectedDate);
        //     return inputDate < today;
        // }

        // // RC Expiry
        // $('#license_expiry_date').on('change', function() {
        //     if (isPastDate(this.value)) {
        //         alert('License Expiry Date cannot be in the past');
        //         this.value = '';
        //     }
        // });

        function isPastDate(selectedDate) {
            const today = new Date();
            today.setHours(0, 0, 0, 0); // remove time

            const inputDate = new Date(selectedDate);
            return inputDate < today;
        }

        $('#license_expiry_date').on('blur', function() {

            if (!this.value) return;

            const selectedDate = new Date(this.value);

            // invalid or incomplete date ignore
            if (isNaN(selectedDate.getTime())) return;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                alert('License Expiry Date cannot be in the past');
                this.value = '';
            }
        });
        $('#insurance_expiry_date').on('blur', function() {

            if (!this.value) return;

            const selectedDate = new Date(this.value);

            // invalid or incomplete date ignore
            if (isNaN(selectedDate.getTime())) return;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                alert('Insurance Expiry Date cannot be in the past');
                this.value = '';
            }
        });

        const allowedRegex = /^[a-zA-Z0-9]+$/;

        // real-time typing + paste validation
        $('input[name="driver_phone"], input[name="emergency_phone"], input[name="license_no "],input[name="adher_no"],input[name="experience_years "]')
            .on('input', function() {

                let value = this.value;

                // remove all non-alphanumeric characters
                let cleanedValue = value.replace(/[^a-zA-Z0-9]/g, '');

                if (value !== cleanedValue) {
                    this.value = cleanedValue;
                }
            });

        document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#driver_image',
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

        $(document).ready(function() {
            var $vehicleSelect = $('#vehicle_id');

            function showEmptyAlert() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Alert',
                    text: 'No vehicles available. Please add a vehicle first.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            }

            // Check if Select2 is initialized
            if ($vehicleSelect.hasClass("select2-hidden-accessible")) {
                $vehicleSelect.on('select2:opening', function(e) {
                    if (this.options.length <= 1) {
                        e.preventDefault();
                        showEmptyAlert();
                    }
                });
            } else {
                // Native select fallback
                $vehicleSelect.on('mousedown', function(e) {
                    if (this.options.length <= 1) {
                        e.preventDefault();
                        $(this).blur();
                        showEmptyAlert();
                        return false;
                    }
                });
            }
        });
    </script>
@endsection
