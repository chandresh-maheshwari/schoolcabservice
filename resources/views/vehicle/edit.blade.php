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
                            Edit Vehicle Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Vehicle Details</h4>
            </div>

            <div class="card-body">
                <form id="vehicleForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="vehicle_id" value="{{ $vehicle->id }}">

                    {{-- Vehicle Number --}}
                    <div class="form-group">
                        <label>Vehicle Number <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" name="vehicle_number" id="vehicle_number"
                            value="{{ $vehicle->vehicle_number }}">
                    </div>

                    {{-- Vehicle Type --}}
                    <div class="form-group">
    <label>Vehicle Type <span style="color:red;">*</span></label>
    <select class="form-control" name="vehicle_type_id" id="vehicle_type_id">
        <option value="">Select Vehicle Type</option>
        @foreach ($vehicleTypes as $type)
            <option value="{{ $type->_id }}"
                {{ $vehicle->vehicle_type == $type->vehicle_type ? 'selected' : '' }}>
                {{ $type->vehicle_type }}
            </option>
        @endforeach
    </select>
</div>


                    {{-- Seating Capacity --}}
                    <div class="form-group">
                        <label>Seating Capacity <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" name="seating_capacity" id="seating_capacity"
                            value="{{ $vehicle->seating_capacity }}">
                    </div>

                    {{-- Vehicle Image --}}
                    <div class="form-group">
                        <label>Vehicle Image <span style="color:red;">*</span></label><br>
                        <button type="button" class="btn btn-primary" id="vehicleImageBtn"
                            onclick="document.getElementById('vehicle_image').click();">Upload Vehicle Image</button>
                        <input type="file" id="vehicle_image" name="vehicle_image" accept="image/*" style="display:none;"
                            onchange="previewImage(event)">
                        <br>
                        @php
                            $imagePath = $vehicle->vehicle_image ? public_path('storage/vehicle/' . $vehicle->vehicle_image) : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $imageUrl = $imageExists ? asset('storage/vehicle/' . $vehicle->vehicle_image) : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName">
                            {{ $imageExists && !$isDefaultImage ? basename($vehicle->vehicle_image) : 'No image' }}
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
                    {{-- RC Number --}}
                    <div class="form-group">
                        <label>RC Number <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" name="rc_number" id="rc_number" value="{{ $vehicle->rc_number }}">
                    </div>

                    {{-- RC Expiry --}}
                    <div class="form-group">
                        <label>RC Expiry Date <span style="color:red;">*</span></label>
                        <input type="date" class="form-control" name="rc_expiry_date" id="rc_expiry_date"
                            value="{{ $vehicle->rc_expiry_date }}" min="{{ date('Y-m-d') }}">
                    </div>

                    {{-- RC Image --}}
                    <div class="form-group">
                        <label>RC Image <span style="color:red;">*</span></label><br>
                        <button type="button" class="btn btn-primary" id="rcImageBtn"
                            onclick="document.getElementById('rc_image').click();">Upload Rc Image</button>
                        <input type="file" id="rc_image" name="rc_image" accept="image/*" style="display:none;"
                            onchange="previewImage1(event)">
                        <br>
                        @php
                            $imagePath = $vehicle->rc_image ? public_path('storage/vehicle/' . $vehicle->rc_image) : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $imageUrl = $imageExists ? asset('storage/vehicle/' . $vehicle->rc_image) : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName1">
                            {{ $imageExists && !$isDefaultImage ? basename($vehicle->rc_image) : 'No image' }}
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
                    {{-- Insurance Number --}}
                    <div class="form-group">
                        <label>Insurance Number <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" name="insurance_number" id="insurance_number"
                            value="{{ $vehicle->insurance_number }}">
                    </div>

                    {{-- Insurance Expiry --}}
                    <div class="form-group">
                        <label>Insurance Expiry Date <span style="color:red;">*</span></label>
                        <input type="date" class="form-control" name="insurance_expiry_date"
                            id="insurance_expiry_date" value="{{ $vehicle->insurance_expiry_date }}"
                            min="{{ date('Y-m-d') }}">
                    </div>

                    {{-- Insurance Image --}}
                    <div class="form-group">
                        <label>Insurance Image <span style="color:red;">*</span></label><br>
                        <button type="button" class="btn btn-primary" id="insuranceImageBtn"
                            onclick="document.getElementById('insurance_image').click();">Upload Insurance Image</button>
                        <input type="file" id="insurance_image" name="insurance_image" accept="image/*"
                            style="display:none;" onchange="previewImage2(event)">
                        <br>
                        @php
                            $imagePath = $vehicle->insurance_image ? public_path('storage/vehicle/' . $vehicle->insurance_image) : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $imageUrl = $imageExists ? asset('storage/vehicle/' . $vehicle->insurance_image) : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName2">
                            {{ $imageExists && !$isDefaultImage ? basename($vehicle->insurance_image) : 'No image' }}
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
                    <div>
                    <button type="button" class="btn btn-primary" id="updateBtn">Update</button>
                    <a href="{{ route('vehicle.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        /* UPDATE VALIDATION + API CALL */
        $('#updateBtn').on('click', function() {

            $('.error-message').remove();
            let isValid = true;

            function showError(el, msg) {
                $(el).after(`<span class="error-message" style="color:red;">${msg}</span>`);
                isValid = false;
            }

            // 🔹 TEXT / SELECT VALIDATION
            if (!$('input[name="vehicle_number"]').val().trim()) {
                showError('input[name="vehicle_number"]', 'Vehicle Number is required');
            }

            if (!$('select[name="vehicle_type_id"]').val()) {
                showError('select[name="vehicle_type_id"]', 'Vehicle Type is required');
            }

            if (!$('input[name="seating_capacity"]').val()) {
                showError('input[name="seating_capacity"]', 'Seating Capacity is required');
            }

            if (!$('input[name="rc_number"]').val().trim()) {
                showError('input[name="rc_number"]', 'RC Number is required');
            }

            if (!$('input[name="rc_expiry_date"]').val()) {
                showError('input[name="rc_expiry_date"]', 'RC Expiry Date is required');
            }

            if (!$('input[name="insurance_number"]').val().trim()) {
                showError('input[name="insurance_number"]', 'Insurance Number is required');
            }

            if (!$('input[name="insurance_expiry_date"]').val()) {
                showError('input[name="insurance_expiry_date"]', 'Insurance Expiry Date is required');
            }

            var imageInput = document.getElementById('vehicle_image');
            var imagePreview = document.getElementById('imagePreview');
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "")) {
                $('#vehicleImageBtn').after(
                    '<span class="error-message" style="color: red;">Vehicle Image is required.</span>');
                isValid = false;
            }
            var imageInput1 = document.getElementById('rc_image');
            var imagePreview1 = document.getElementById('imagePreview1');
            var imageError1 = document.getElementById('imageError');
            var currentImageSrc1 = imagePreview1.getAttribute('src');
            var isDefaultImage1 = currentImageSrc1.includes('Default.jpg');
            if (!imageInput1.files.length && isDefaultImage1 || (currentImageSrc1 == "#" || currentImageSrc1 ==
                    "")) {
                $('#rcImageBtn').after(
                    '<span class="error-message" style="color: red;">Rc Image is required.</span>');
                isValid = false;
            }

            var imageInput2 = document.getElementById('insurance_image');
            var imagePreview2 = document.getElementById('imagePreview2');
            var imageError2 = document.getElementById('imageError');
            var currentImageSrc2 = imagePreview2.getAttribute('src');
            var isDefaultImage2 = currentImageSrc2.includes('Default.jpg');
            if (!imageInput2.files.length && isDefaultImage2 || (currentImageSrc2 == "#" || currentImageSrc2 ==
                    "")) {
                $('#insuranceImageBtn').after(
                    '<span class="error-message" style="color: red;">Insurance Image is required.</span>');
                isValid = false;
            }

            function isAlphaNumeric(value) {
                return /^[a-zA-Z0-9]+$/.test(value);
            }

            if (!isAlphaNumeric($('input[name="vehicle_number"]').val())) {
                // showError('input[name="vehicle_number"]', 'Only letters and numbers allowed');
            }

            if (!isAlphaNumeric($('input[name="rc_number"]').val())) {
                // showError('input[name="rc_number"]', 'Only letters and numbers allowed');
            }

            if (!isAlphaNumeric($('input[name="insurance_number"]').val())) {
                // showError('input[name="insurance_number"]', 'Only letters and numbers allowed');
            }
            if (!isAlphaNumeric($('input[name="seating_capacity"]').val())) {
                // showError('input[name="seating_capacity"]', 'Only letters and numbers allowed');
            }
            if (!isValid) return;

            // 🔹 SUBMIT
            let formData = new FormData(document.getElementById('vehicleForm'));
            let vehicleId = $('#vehicle_id').val();

            Swal.fire({
                title: 'Updating...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(`/api/vehicle/${vehicleId}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'X-HTTP-Method-Override': 'PUT',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', 'Vehicle updated successfully!');
                        setTimeout(() => window.location.href = '{{ route('vehicle.index') }}', 1500);
                    } else {
                        notify('error', data.message || 'Update failed');
                    }
                });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });

        document.getElementById('vehicle_image').addEventListener('change', function() {
            $('#vehicleImageBtn').next('.error-message').remove();
        });
        document.getElementById('rc_image').addEventListener('change', function() {
            $('#rcImageBtn').next('.error-message').remove();
        });
        document.getElementById('insurance_image').addEventListener('change', function() {
            $('#insuranceImageBtn').next('.error-message').remove();
        });

        function isPastDate(dateValue) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            return new Date(dateValue) < today;
        }

        $('#rc_expiry_date, #insurance_expiry_date').on('change', function() {
            if (isPastDate(this.value)) {
                this.value = '';
            }
        });

        $('input[name="vehicle_number"], input[name="rc_number"], input[name="insurance_number"],input[name="seating_capacity"]')
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
                    url: '{{ route('api.vehicle.vehicleImage', $vehicle->id) }}',
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
                    url: '{{ route('api.vehicle.rcImage', $vehicle->id) }}',
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
                    url: '{{ route('api.vehicle.insuranceImage', $vehicle->id) }}',
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
                imageInputSelector: '#vehicle_image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });
        document.getElementById('removeImageBtn1').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview1',
                imageNameSelector: '#imageName1',
                imageInputSelector: '#rc_image',
                removeImageBtnSelector: '#removeImageBtn1'
            });
        });
        document.getElementById('removeImageBtn2').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview2',
                imageNameSelector: '#imageName2',
                imageInputSelector: '#insurance_image',
                removeImageBtnSelector: '#removeImageBtn2'
            });
        });
    </script>
@endsection
