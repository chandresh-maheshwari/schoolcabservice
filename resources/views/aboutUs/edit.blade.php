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
                        <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('aboutUs.index') }}">About Us</a>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit About Us Details
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit About Us </h4>
            </div>
            <div class="card-body">
                <form id="aboutUsSectionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="title" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ $aboutUs->title }}" required>
                    </div>

                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ $aboutUs->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="image" style="font-weight: bold;">Image <span style="color: red;">*</span></label>
                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            @php
                                $imagePath = $aboutUs->image ? public_path($aboutUs->image) : null;
                                $imageExists = $imagePath && File::exists($imagePath);
                                $imageUrl = $imageExists ? asset($aboutUs->image) : asset('images/Default.jpg');
                                $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                            @endphp
                            <span id="imageName">
                                {{ !$isDefaultImage && $imageExists ? basename($aboutUs->image) : 'No image' }}
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
                    </div>
                    <div class="form-group">
                        <label for="feature_1" style="font-weight: bold;">Feature 1 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="feature_1" name="feature_1"
                            value="{{ $aboutUs->feature_1 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="feature_2" style="font-weight: bold;">Feature 2 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="feature_2" name="feature_2"
                            value="{{ $aboutUs->feature_2 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="feature_3" style="font-weight: bold;">Feature 3 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="feature_3" name="feature_3"
                            value="{{ $aboutUs->feature_3 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="feature_4" style="font-weight: bold;">Feature 4 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="feature_4" name="feature_4"
                            value="{{ $aboutUs->feature_4 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="feature_5" style="font-weight: bold;">Feature 5 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="feature_5" name="feature_5"
                            value="{{ $aboutUs->feature_5 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="feature_6" style="font-weight: bold;">Feature 6 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="feature_6" name="feature_6"
                            value="{{ $aboutUs->feature_6 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="profile_name" style="font-weight: bold;">Profile Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="profile_name" name="profile_name"
                            value="{{ $aboutUs->profile_name }}" required>
                    </div>
                    <div class="form-group">
                        <label for="profile_position" style="font-weight: bold;">Profile Position <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="profile_position" name="profile_position"
                            value="{{ $aboutUs->profile_position }}" required>
                    </div>

                    {{-- <div class="form-group">
                        <label for="profile_image" style="font-weight: bold;">Profile Image <span style="color: red;">*</span></label>
                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="profile_image" name="profile_image" accept="image/*"
                                style="display: none;" onchange="previewImage1(event)">
                            <button type="button" class="btn btn-primary" id="uploadProfileImageBtn"
                                onclick="document.getElementById('profile_image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            <span
                                id="imageName1">{{ $aboutUs->profile_image ? basename($aboutUs->profile_image) : 'No image selected' }}</span>
                            <img id="imagePreview1" src="{{ $aboutUs->profile_image ? asset($aboutUs->profile_image) : '' }}" alt="Image Preview"
                                style="display: {{ $aboutUs->profile_image ? 'block' : 'none' }}; width: 100px; height: 100px; margin-top: 10px;">
                            @if ($aboutUs->profile_image)
                                <button type="button" id="deleteProfileImageBtn" class="btn btn-danger btn-sm" style="margin-top: 10px; margin-left: 10px;">
                                    <i class="fas fa-trash"></i> Delete Profile Image
                                </button>
                            @endif
                        </div>
                    </div> --}}
                    <div class="form-group">
                        <label for="profile_image" style="font-weight: bold;">Profile Image <span style="color: red;">*</span></label>
                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="profile_image" name="profile_image" accept="image/*"
                                style="display: none;" onchange="previewImage1(event)">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn1"
                                onclick="document.getElementById('profile_image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            @php
                                $imagePath = $aboutUs->profile_image ? public_path($aboutUs->profile_image) : null;
                                $imageExists = $imagePath && File::exists($imagePath);
                                $imageUrl = $imageExists ? asset($aboutUs->profile_image) : asset('images/Default.jpg');
                                $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                            @endphp
                            <span id="imageName1">
                                {{ !$isDefaultImage && $imageExists ? basename($aboutUs->image) : 'No image' }}
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
                    </div>
                    <div class="form-group">
                        <label for="contact_number" style="font-weight: bold;">Contact Number <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number"
                            value="{{ $aboutUs->contact_number }}" required>
                    </div>
                    <div class="form-group">
                        <label for="experience_badge" style="font-weight: bold;">Experience Badge <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="experience_badge" name="experience_badge" rows="4" required>{{ $aboutUs->experience_badge }}</textarea>
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('aboutUs.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/common.js') }}"></script>
    <script src="{{ asset('js/common_js.js') }}"></script>

    <script>
        CKEDITOR.replace('description');
        CKEDITOR.replace('experience_badge');

        document.getElementById('submitBtn').addEventListener('click', function() {

            var formData = new FormData(document.getElementById('aboutUsSectionForm'));
            formData.append('_method', 'PUT'); // Spoof PUT request for Laravel
            formData.set('description', CKEDITOR.instances.description.getData());
            formData.set('experience_badge', CKEDITOR.instances.experience_badge.getData());

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            let isValid = true;

            // Validation: Title
            if (!formData.get('title')) {
                $('#title').after('<span class="error-message" style="color: red;">Title is required.</span>');
                isValid = false;
            }

            // Validation: Description
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }

            if (!CKEDITOR.instances.experience_badge.getData().trim()) {
                $('#experience_badge').next('.cke').after(
                    '<span class="error-message" style="color: red;">Experience Badge is required.</span>');
                isValid = false;
            }
            // if (!formData.get('image') || !formData.get('image').name) {
            //     $('#uploadImageBtn').after(
            //         '<span class="error-message" style="color: red;">Image is required.</span>');
            //     isValid = false;
            // }
            // Validation: Features 1-6
            for (let i = 1; i <= 6; i++) {
                if (!formData.get(`feature_${i}`)) {
                    $(`#feature_${i}`).after(
                        `<span class="error-message" style="color: red;">Feature ${i} is required.</span>`);
                    isValid = false;
                }
            }

            // Validation: Profile Name
            if (!formData.get('profile_name')) {
                $('#profile_name').after(
                    '<span class="error-message" style="color: red;">Profile Name is required.</span>');
                isValid = false;
            }

            // Validation: Profile Position
            if (!formData.get('profile_position')) {
                $('#profile_position').after(
                    '<span class="error-message" style="color: red;">Profile Position is required.</span>');
                isValid = false;
            }
            // if (!formData.get('profile_image') || !formData.get('profile_image').name) {
            //     $('#uploadProfileImageBtn').after(
            //         '<span class="error-message" style="color: red;">Profile Image is required.</span>');
            //     isValid = false;
            // }
            // Validation: Contact Number
            // if (!formData.get('contact_number')) {
            //     $('#contact_number').after(
            //         '<span class="error-message" style="color: red;">Contact Number is required.</span>');
            //     isValid = false;
            // }
            if (formData.get('contact_number')) {
                // Regular expression to check for 10-12 digits only
                const phoneRegex = /^\d{10,12}$/;

                if (!phoneRegex.test(formData.get('contact_number'))) {
                    document.getElementById('contact_number').nextElementSibling.textContent =
                        'Phone Number must contain only digits and be between 10 and 12 characters long.';
                    isValid = false;
                }
                // If validation passes, clear any previous error message
                else {
                    document.getElementById('contact_number').nextElementSibling.textContent = '';
                }
            }

            var imageInput = document.getElementById('image');
            var imagePreview = document.getElementById('imagePreview');
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#uploadImageBtn').after(
                    '<span class="error-message" style="color: red;">Image is required.</span>');
                isValid = false;
            }

            var imageInput1 = document.getElementById('profile_image');
            var imagePreview1 = document.getElementById('imagePreview1');
            var imageError1 = document.getElementById('imageError');
            var currentImageSrc1 = imagePreview1.getAttribute('src');
            var isDefaultImage1 = currentImageSrc1.includes('Default.jpg');
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput1.files.length && isDefaultImage1 || (currentImageSrc1 == "#" || currentImageSrc1 == "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#uploadImageBtn1').after(
                    '<span class="error-message" style="color: red;">Profile Image is required.</span>');
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            // Show loading spinner
            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Submit via fetch API
            fetch('{{ route('api.aboutUs.update', $aboutUs->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })

                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', 'About Us updated Successfully!');
                        setTimeout(() => {
                            window.location.href = '{{ route('aboutUs.index') }}';
                        }, 1500);
                    } else {
                        notify('error', data.message || 'There was an error updating the About Us details.');
                    }
                })
                .catch(error => {
                    Swal.close();
                    notify('error', 'An unexpected error occurred.');
                });
        });

        // Error spans for all inputs
        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) {
                var errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.appendChild(errorSpan);
            }
        });

        // Clear error messages on input
        // document.getElementById('title').addEventListener('input', function() {
        //     this.parentNode.querySelector('.error-message').textContent = '';
        // });
        const titleInput = document.getElementById('title');
        const errorSpan = titleInput.parentNode.querySelector('.error-message');

        titleInput.addEventListener('input', function() {
            let value = this.value;

            if (value.length > 35) {
                this.value = value.slice(0, 35); // stop extra characters
                errorSpan.textContent = 'Title cannot exceed 35 characters.';
            } else if (value.trim() === '') {
                errorSpan.textContent = 'Title is required.';
            } else {
                errorSpan.textContent = '';
            }
        });

        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });

        CKEDITOR.instances.experience_badge.on('change', function() {
            $('#experience_badge').next('.cke').next('.error-message').remove();
        });

        document.getElementById('contact_number').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });

        document.getElementById('profile_name').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });

        document.getElementById('profile_position').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });
    
        for (let i = 1; i <= 6; i++) {
            document.getElementById(`feature_${i}`).addEventListener('input', function() {
                this.parentNode.querySelector('.error-message').textContent = '';
            });
        }
        document.getElementById('image').addEventListener('change', function() {
            $('#uploadImageBtn').next('.error-message').remove();
        });
        document.getElementById('profile_image').addEventListener('change', function() {
            $('#uploadImageBtn1').next('.error-message').remove();
        });
        // Use common delete for main image
        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.aboutUs.deleteImage', $aboutUs->id) }}',
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
                    url: '{{ route('api.aboutUs.deleteProfileImage', $aboutUs->id) }}',
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
                imageInputSelector: '#image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });
         document.getElementById('removeImageBtn1').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview1',
                imageNameSelector: '#imageName1',
                imageInputSelector: '#profile_image',
                removeImageBtnSelector: '#removeImageBtn1'
            });
        });
    </script>
@endsection
