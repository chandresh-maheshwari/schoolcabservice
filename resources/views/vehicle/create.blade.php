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
                            Add Vehicle Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Vehicle Details</h4>
            </div>

            <div class="card-body">
                <form id="vehicleForm" enctype="multipart/form-data">
                    @csrf

                    {{-- Vehicle Number --}}
                    <div class="form-group">
                        <label>Vehicle Number <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="vehicle_number" name="vehicle_number"
                            autocomplete="off">
                    </div>

                    {{-- Vehicle Type --}}
                    <div class="form-group">
                        <label>Vehicle Type <span style="color:red;">*</span></label>

                        <select class="form-control" name="vehicle_type_id" id="vehicle_type_id">
                            <option value="">Select Vehicle Type</option>

                            @foreach ($vehicleTypes as $type)
                                <option value="{{ $type->id }}"
                                    {{ old('vehicle_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->vehicle_type }}
                                </option>
                            @endforeach
                        </select>

                        @error('vehicle_type_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>


                    {{-- Seating Capacity --}}
                    <div class="form-group">
                        <label>
                            Seating Capacity <span style="color:red;">*</span>
                        </label>
                        <input type="number" class="form-control" id="seating_capacity" name="seating_capacity"
                            min="1" step="1" required oninput="this.value = this.value < 1 ? '' : this.value"
                            autocomplete="off">
                    </div>


                    {{-- Vehicle Image --}}
                    <div class="form-group">
                        <label>Vehicle Image <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 636 × 424 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="vehicleImageBtn"
                            onclick="document.getElementById('vehicle_image').click();">Upload Image</button>
                        <input type="file" id="vehicle_image" name="vehicle_image" accept="image/*" style="display:none;"
                            onchange="previewImage(event)">
                        <span id="imageName"></span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn"><i
                                class="fas fa-trash"></i></button>
                    </div>

                    {{-- RC Number --}}
                    <div class="form-group">
                        <label>RC Number <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="rc_number" name="rc_number" autocomplete="off">
                    </div>

                    {{-- RC Expiry --}}
                    <div class="form-group">
                        <label>RC Expiry Date <span style="color:red;">*</span></label>
                        <input type="text" class="form-control app-date-picker" name="rc_expiry_date" id="rc_expiry_date" data-not-past="true" data-field-label="RC Expiry Date"
                            placeholder="DD/MM/YYYY" inputmode="numeric" autocomplete="off">

                    </div>

                    {{-- RC Image --}}
                    <div class="form-group">
                        <label>RC Image / PDF <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="rcImageBtn"
                            onclick="document.getElementById('rc_image').click();">Upload File</button>
                        <input type="file" id="rc_image" name="rc_image" accept="image/*,application/pdf" style="display:none;"
                            onchange="previewImage1(event)">
                        <span id="imageName1"></span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview1" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn1"><i
                                class="fas fa-trash"></i></button>
                    </div>
                    {{-- Insurance Number --}}
                    <div class="form-group">
                        <label>Insurance Number <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="insurance_number" name="insurance_number"
                            autocomplete="off">
                    </div>

                    {{-- Insurance Expiry --}}
                    <div class="form-group">
                        <label>Insurance Expiry Date <span style="color:red;">*</span></label>
                        <input type="text" class="form-control app-date-picker" name="insurance_expiry_date" data-not-past="true" data-field-label="Insurance Expiry Date"
                            id="insurance_expiry_date" placeholder="DD/MM/YYYY" inputmode="numeric" autocomplete="off">

                    </div>

                    {{-- Insurance Image --}}
                    <div class="form-group">
                        <label>Insurance Image / PDF <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="insuranceImageBtn"
                            onclick="document.getElementById('insurance_image').click();">Upload File</button>
                        <input type="file" id="insurance_image" name="insurance_image"
                            accept="image/*,application/pdf" style="display:none;" onchange="previewImage2(event)">
                        <span id="imageName2"></span>

                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display:none;">

                        <!-- Image Preview -->
                        <img id="imagePreview2" src="#" alt="Document Preview"
                            style="display:none; width:100px; height:100px; margin-top:10px;">

                        <!-- PDF Preview -->
                        <div id="pdfPreview2" style="display:none; margin-top:10px;">
                            📄 <a id="pdfLink2" href="#" target="_blank">View Insurance PDF</a>
                        </div>

                        <button type="button" class="btn" id="removeImageBtn2" style="display:none;">
                            <i class="fas fa-trash"></i>
                        </button>

                    </div>



                    <div>
                        <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                        <a href="{{ route('vehicle.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('vehicleForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('vehicle_number')) showError('#vehicle_number', 'Vehicle Number is required');
            let vehicleTypeSelect = document.getElementById('vehicle_type_id');
            let vehicleTypeValue = vehicleTypeSelect.value;

            if (vehicleTypeValue === "") {
                $('#vehicle_type_id').after(
                    '<span class="error-message" style="color:red;">Vehicle Type is required</span>'
                );
                isValid = false;
            }
            if (!formData.get('seating_capacity')) showError('#seating_capacity', 'Seating Capacity is required');
            if (!formData.get('rc_number')) showError('#rc_number', 'RC Number is required');
            if (!formData.get('rc_expiry_date')) showError('#rc_expiry_date', 'RC Expiry Date is required');
            else if (!window.parseDisplayDate(formData.get('rc_expiry_date'))) showError('#rc_expiry_date', 'Use date format DD/MM/YYYY');
            else if (window.isDisplayDateBeforeToday(formData.get('rc_expiry_date'))) showError('#rc_expiry_date', 'RC Expiry Date cannot be before 17/08/2026');
            if (!formData.get('insurance_number')) showError('#insurance_number', 'Insurance Number is required');
            if (!formData.get('insurance_expiry_date')) showError('#insurance_expiry_date',
                'Insurance Expiry Date is required');
            else if (!window.parseDisplayDate(formData.get('insurance_expiry_date'))) showError('#insurance_expiry_date', 'Use date format DD/MM/YYYY');
            else if (window.isDisplayDateBeforeToday(formData.get('insurance_expiry_date'))) showError('#insurance_expiry_date', 'Insurance Expiry Date cannot be before 17/08/2026');

            // if (!formData.get('vehicle_image')?.name) showError('#vehicle_image', 'Vehicle Image is required');
            // if (!formData.get('rc_image')?.name) showError('#rc_image', 'RC Image is required');
            // if (!formData.get('insurance_image')?.name) showError('#insurance_image',
            //     'Insurance Image is required');

            function isValidPositive(value) {
                return /^[a-zA-Z0-9]+$/.test(value);
            }

            if (!isValidPositive($('input[name="vehicle_number"]').val())) {
                // showError('input[name="vehicle_number"]');
            }

            if (!isValidPositive($('input[name="rc_number"]').val())) {
                // showError('input[name="rc_number"]');
            }

            if (!isValidPositive($('input[name="insurance_number"]').val())) {
                // showError('input[name="insurance_number"]');
            }
            if (!isValidPositive($('input[name="seating_capacity"]').val())) {
                // showError('input[name="seating_capacity"]');
            }

            document.getElementById('seating_capacity').addEventListener('input', function() {
                if (this.value < 1) {
                    this.value = '';
                }
            });

            var imageInput = document.getElementById('vehicle_image');
            var imagePreview = document.getElementById('imagePreview');
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#vehicleImageBtn').after(
                    '<span class="error-message" style="color: red;">Vehicle Image is required.</span>');
                isValid = false;
            }
            var imageInput1 = document.getElementById('rc_image');
            var imagePreview1 = document.getElementById('imagePreview1');
            var imageError1 = document.getElementById('imageError');
            var currentImageSrc1 = imagePreview1.getAttribute('src');
            var isDefaultImage1 = currentImageSrc1.includes('Default.jpg');
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput1.files.length && isDefaultImage1 || (currentImageSrc1 == "#" || currentImageSrc1 ==
                    "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#rcImageBtn').after(
                    '<span class="error-message" style="color: red;">Rc Image is required.</span>');
                isValid = false;
            }
            var imageInput2 = document.getElementById('insurance_image');
            var imagePreview2 = document.getElementById('imagePreview2');
            var imageError2 = document.getElementById('imageError');
            var currentImageSrc2 = imagePreview2.getAttribute('src');
            var isDefaultImage2 = currentImageSrc2.includes('Default.jpg');
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput2.files.length && isDefaultImage2 || (currentImageSrc2 == "#" || currentImageSrc2 ==
                    "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#insuranceImageBtn').after(
                    '<span class="error-message" style="color: red;">Insurance Image is required.</span>');
                isValid = false;
            }
            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.vehicle.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', 'Vehicle created successfully!');
                        setTimeout(() => window.location.href = '{{ route('vehicle.index') }}', 1500);
                    } else {
                        notify('error', data.message || 'Something went wrong');
                    }
                });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });

        $(document).on('change', '#vehicle_type_id', function() {
            $(this).next('.error-message').remove();
        });

        document.getElementById('vehicle_image').addEventListener('change', function() {
            $('#vehicleImageBtn').next('.error-message').remove();
        })

        document.getElementById('rc_image').addEventListener('change', function() {
            $('#rcImageBtn').next('.error-message').remove();
        });
        document.getElementById('insurance_image').addEventListener('change', function() {
            $('#insuranceImageBtn').next('.error-message').remove();
        });

        function isPastDate(selectedDate) {
            const today = new Date();
            today.setHours(0, 0, 0, 0); // remove time

            const inputDate = new Date(selectedDate);
            return inputDate < today;
        }

        const allowedRegex = /^[a-zA-Z0-9]+$/;

        // real-time typing + paste validation
        $('input[name="vehicle_number"], input[name="rc_number"], input[name="insurance_number"],input[name="seating_capacity"]')
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

        function previewImage2(event) {

            const file = event.target.files[0];
            if (!file) return;

            const img = document.getElementById('imagePreview2');
            const name = document.getElementById('imageName2');
            const container = document.getElementById('dlt_btn_div');

            name.innerHTML = file.name;
            container.style.display = "block";

            if (file.type === "application/pdf" || file.name.toLowerCase().endsWith('.pdf')) {
                img.src = window.pdfPreviewPlaceholder || '/images/pdf-placeholder.svg';
                img.style.display = "block";
            } else {
                img.src = URL.createObjectURL(file);
                img.style.display = "block";
            }

            document.getElementById('removeImageBtn2').style.display = "inline-block";
        }



        $(document).ready(function() {
            var $vehicleTypeSelect = $('#vehicle_type_id');

            function showEmptyAlert() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Alert',
                    text: 'No vehicle types available. Please add a vehicle type first.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            }

            // Check if Select2 is initialized
            if ($vehicleTypeSelect.hasClass("select2-hidden-accessible")) {
                $vehicleTypeSelect.on('select2:opening', function(e) {
                    if (this.options.length <= 1) {
                        e.preventDefault();
                        showEmptyAlert();
                    }
                });
            } else {
                // Native select fallback
                $vehicleTypeSelect.on('mousedown', function(e) {
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
