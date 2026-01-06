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
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Parent
                            Details</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Parent Details</h4>
            </div>
            <div class="card-body">
                <form id="editParentForm" enctype="multipart/form-data">
                    @csrf

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
                        <label for="contact_number" style="font-weight: bold;">Contact Number <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number"
                            value="{{ $child->contact_number }}">
                    </div>
                    <div class="form-group">
                        <label for="alternative_contact_number" style="font-weight: bold;">AlterNative Contact Number <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="alternative_contact_number"
                            name="alternative_contact_number" value="{{ $child->alternative_contact_number }}">
                    </div>
                    <div class="form-group">
                        <label for="email" style="font-weight: bold;">Email</label>
                        <input type="text" class="form-control" id="email" name="email" value="{{ $child->email }}"
                            readonly>
                    </div>
                    <div class="form-group">
                        <label for="address_1" style="font-weight: bold;">Address 1 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="address_1" name="address_1"
                            value="{{ $child->address_1 }}">
                    </div>
                    <div class="form-group">
                        <label for="address_2" style="font-weight: bold;">Address 2 <span
                                style="color: red;">*</span></label>
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
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pincode" style="font-weight: bold;">Pincode <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="pincode" name="pincode"
                            value="{{ $child->pincode }}">
                    </div>
                    <div class="form-group">
                        <label>Father Adhaar Card Image <span style="color:red;">*</span></label><br>
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
                        <label>Mother Adhaar Card Image <span style="color:red;">*</span></label><br>
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
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Update</button>
                    <a href="{{ route('parent.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                    </div>
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

            let selectedState = "{{ $child->state }}";
            let selectedCity = "{{ $child->city }}";
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
                    url: "{{ route('api.parent.getCities') }}",
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
           FORM SUBMIT (YOUR EXISTING CODE)
        ================================ */
        document.getElementById('submitBtn').addEventListener('click', function() {

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
            var imageInput = document.getElementById('father_adhaar_card_image');
            var imagePreview = document.getElementById('imagePreview');
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "")) {
                $('#fatherImageBtn').after(
                    '<span class="error-message" style="color: red;">Father Adhaar Card Image is required.</span>'
                    );
                isValid = false;
            }
            var imageInput1 = document.getElementById('mother_adhaar_card_image');
            var imagePreview1 = document.getElementById('imagePreview1');
            var imageError1 = document.getElementById('imageError');
            var currentImageSrc1 = imagePreview1.getAttribute('src');
            var isDefaultImage1 = currentImageSrc1.includes('Default.jpg');
            if (!imageInput1.files.length && isDefaultImage1 || (currentImageSrc1 == "#" || currentImageSrc1 ==
                    "")) {
                $('#motherImagBtn').after(
                    '<span class="error-message" style="color: red;">Mother Adhaar Card Image is required.</span>'
                    );
                isValid = false;
            }
            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            formData.append('_method', 'PUT');
            fetch("{{ route('api.parent.update', $child->_id) }}", {
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
                        notify('success', 'Parent Updated Successfully!');
                        setTimeout(() => {
                            window.location.href = '{{ route('parent.index') }}';
                        }, 1500);
                    } else {
                        notify('error', data.message || 'There was an error creating the Parent.');
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

        document.getElementById('father_adhaar_card_image').addEventListener('change', function() {
            $('#fatherImageBtn').next('.error-message').remove();
        })

        document.getElementById('mother_adhaar_card_image').addEventListener('change', function() {
            $('#motherImageBtn').next('.error-message').remove();
        });

        /* ===============================
           CLEAR ERROR ON INPUT
        ================================ */
        $('#father_name, #mother_name, #contact_number, #email, #state, #city, #pincode, #alternative_contact_number,#address_1,#address_2')
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
    </script>
@endsection
