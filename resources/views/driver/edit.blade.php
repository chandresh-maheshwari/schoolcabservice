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
                <form id="editDriverForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="driver_id" value="{{ $driver->id }}">

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
                    {{-- Vehicle  --}}
                    <div class="form-group">
                        <label>Vehicle <span style="color:red;">*</span></label>

                        <select class="form-control" name="vehicle_id" id="vehicle_id">
                            <option value="">Select Vehicle</option>

                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}"
                                    {{ old('vehicle_id', $driver->vehicle_id) == $vehicle->id ? 'selected' : '' }}>

                                    {{ $vehicle->vehicle_number }}
                                    @if ($vehicle->vehicleType)
                                        / {{ $vehicle->vehicleType->vehicle_type }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
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
                        <label>Emergency Number <span style="color:red;">*</span></label>
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
                        <input type="date" class="form-control" name="license_expiry_date" id="license_expiry_date"
                            value="{{ $driver->license_expiry_date }}" min="{{ date('Y-m-d') }}">
                    </div>

                    {{-- License Image --}}
                    <div class="form-group">
                        <label>License Image <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="licenseImageBtn"
                            onclick="document.getElementById('license_image').click();">Upload License Image</button>
                        <input type="file" id="license_image" name="license_image" accept="image/*"
                            style="display:none;" onchange="previewImage1(event)">
                        <br>
                        @php
                            $imagePath = $driver->license_image
                                ? public_path('storage/drivers/' . $driver->license_image)
                                : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $imageUrl = $imageExists
                                ? asset('storage/drivers/' . $driver->license_image)
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
                        <input type="text" class="form-control" name="adher_no" id="adher_no"
                            value="{{ $driver->adher_no }}">
                    </div>
                    {{-- Adher Image --}}
                    <div class="form-group">
                        <label>Aadhar Image <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="adherImageBtn"
                            onclick="document.getElementById('adher_card_iamge').click();">Upload Adher Card Image</button>
                        <input type="file" id="adher_card_iamge" name="adher_card_iamge" accept="image/*"
                            style="display:none;" onchange="previewImage2(event)">
                        <br>
                        @php
                            $imagePath = $driver->adher_card_iamge
                                ? public_path('storage/drivers/' . $driver->adher_card_iamge)
                                : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $imageUrl = $imageExists
                                ? asset('storage/drivers/' . $driver->adher_card_iamge)
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
                        <input type="date" class="form-control" name="joining_date" id="joining_date"
                            value="{{ $driver->joining_date }}">
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ route('driver.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
    <script>
        /* UPDATE VALIDATION + API CALL */
        $('#updateBtn').on('click', function() {
            // alert('hello');

            $('.error-message').remove();
            let isValid = true;

            function showError(el, msg) {
                $(el).after(`<span class="error-message" style="color:red;">${msg}</span>`);
                isValid = false;
            }

            // 🔹 TEXT / SELECT VALIDATION
            if (!$('input[name="driver_name"]').val().trim()) {
                showError('input[name="driver_name"]', 'Driver Name is required');
            }

            if (!$('input[name="driver_phone"]').val()) {
                showError('input[name="driver_phone"]', 'Driver Phone is required');
            }

            if (!$('input[name="emergency_phone"]').val()) {
                showError('input[name="emergency_phone"]', 'Emergency Phone is required');
            }

            if (!$('input[name="license_no "]').val()) {
                showError('input[name="license_no "]', 'License Number is required');
            }

            if (!$('input[name="license_expiry_date"]').val()) {
                showError('input[name="license_expiry_date"]', 'License Expiry Date is required');
            }

            if (!$('input[name="adher_no"]').val()) {
                showError('input[name="adher_no"]', 'Adher Number is required');
            }

            if (!$('input[name="experience_years"]').val()) {
                showError('input[name="experience_years"]', 'Experience Years is required');
            }
            if (!$('input[name="joining_date"]').val()) {
                showError('input[name="joining_date"]', 'Joining Date is required');
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
                // showError('input[name="insurance_number"]', 'Only letters and numbers allowed');
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

        function isPastDate(dateValue) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            return new Date(dateValue) < today;
        }

        $('#license_expiry_date').on('change', function() {
            if (isPastDate(this.value)) {
                this.value = '';
            }
        });

        $('input[name="driver_phone"], input[name="emergency_phone"], input[name="license_no"],input[name="adher_no"],input[name="experience_years"]')
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
