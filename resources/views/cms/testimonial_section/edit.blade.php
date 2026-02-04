{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')
@section('content')
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('testimonialSection.index') }}">Testimonial Section</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Testimonial
                            Section
                            Details</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="hero-edit-header">Edit
                    Testimonial Section Details</h4>
            </div>
            <div class="card-body">
                <form id="testimonialSectionForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="name" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="{{ $testimonialSection->name }}" required>
                    </div>

                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" required>{{ $testimonialSection->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Image <span style="color:red;">*</span></label><br>
                        <button type="button" class="btn btn-primary" id="ImageBtn"
                            onclick="document.getElementById('profile_image').click();">Upload Image</button>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display:none;"
                            onchange="previewImage(event)">
                        <br>
                        @php
                            $imagePath = $testimonialSection->profile_image
                                ? public_path('storage/testimonialSection/' . $testimonialSection->profile_image)
                                : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $imageUrl = $imageExists
                                ? asset('storage/testimonialSection/' . $testimonialSection->profile_image)
                                : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName">
                            {{ $imageExists && !$isDefaultImage ? basename($testimonialSection->profile_image) : 'No image' }}
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
                        <label for="designation" style="font-weight: bold;">Designation <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="designation" name="designation"
                            value="{{ $testimonialSection->designation }}" required>
                    </div>
                    <div class="form-group">
                        <label for="tagline" style="font-weight: bold;">Tagline <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="tagline" name="tagline"
                            value="{{ $testimonialSection->tagline }}" required>
                    </div>
                    <div class="form-group">
                        <label for="rating" style="font-weight: bold;">
                            Rating <span style="color: red;">*</span>
                        </label>
                        <input type="number" class="form-control" id="rating" name="rating" min="1"
                            max="5" value="{{ $testimonialSection->rating }}" required autocomplete="off">
                    </div>

                    <div>
                        <button type="button" class="btn btn-primary" id="submitBtn"
                            style="background-color: #2C9DD4; color: white;">Update</button>
                        <a href="{{ route('benefitSection.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        CKEDITOR.replace('description');


        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('testimonialSectionForm'));
            formData.set('description', CKEDITOR.instances.description.getData());

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            // Validate form
            var isValid = true;
            if (!formData.get('name')) {
                document.getElementById('name').nextElementSibling.textContent = 'Name is required.';
                isValid = false;
            }
            if (!formData.get('designation')) {
                document.getElementById('designation').nextElementSibling.textContent = 'Designation is required.';
                isValid = false;
            }
            if (!formData.get('tagline')) {
                document.getElementById('tagline').nextElementSibling.textContent = 'Tagline is required.';
                isValid = false;
            }
            if (!formData.get('rating')) {
                document.getElementById('rating').nextElementSibling.textContent = 'Rating is required.';
                isValid = false;
            }
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }

            var imageInput = document.getElementById('profile_image');
            var imagePreview = document.getElementById('imagePreview');
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');

            // if (!imageInput.files.length && isDefaultImage) {
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "")) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#ImageBtn').after(
                    '<span class="error-message" style="color: red;"> Profile Image is required.</span>');
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            $(document).on('input', '#rating', function() {
                let value = this.value;

                // remove non-numeric
                value = value.replace(/[^0-9]/g, '');

                if (value === '') {
                    this.value = '';
                    return;
                }

                // allow only 1–5
                if (value < 1 || value > 5) {
                    this.value = '';
                } else {
                    this.value = value;
                }
            });
            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            formData.append('_method', 'PUT');
            fetch('{{ route('api.testimonialSection.update', $testimonialSection->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'Accept': 'application/json'
                    }
                })
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) {
                        throw data;
                    }

                    return data;
                })
                .then(data => {
                    Swal.close();

                    notify('success', 'Testimonial Section Details updated Successfully!');
                    setTimeout(() => {
                        window.location.href = '{{ route('testimonialSection.index') }}';
                    }, 1500);
                })
                .catch(error => {
                    Swal.close();
                    if (error.type === 'validation' && error.errors) {
                        Object.values(error.errors).forEach(messages => {
                            notify('error', messages[0]);
                        });
                    } else if (error.message) {
                        notify('error', error.message);
                    } else {
                        notify('error', 'Something went wrong');
                    }
                });
        });

        // Add error message spans for regular inputs
        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) { // Exclude Select2
                var errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.appendChild(errorSpan);
            }
        });

        // document.getElementById('title').addEventListener('input', function() {
        //     this.parentNode.querySelector('.error-message').textContent = '';
        // });

        const titleInput = document.getElementById('name');
        const errorSpan = titleInput.parentNode.querySelector('.error-message');

        titleInput.addEventListener('input', function() {
            let value = this.value;

            if (value.length > 40) {
                this.value = value.slice(0, 40); // stop extra characters
                errorSpan.textContent = 'Name cannot exceed 40 characters.';
            } else if (value.trim() === '') {
                errorSpan.textContent = 'Name is required.';
            } else {
                errorSpan.textContent = '';
            }
        });

        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });

        document.getElementById('profile_image').addEventListener('change', function() {
            $('#ImageBtn').next('.error-message').remove();
        });




        document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#profile_image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });

        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.testimonialSection.testimonialImage', $testimonialSection->id) }}',
                    csrfToken: document.querySelector('input[name="_token"]').value,
                    imagePreviewSelector: '#imagePreview',
                    buttonSelector: '#deleteImageBtn',
                    nameSelector: '#imageName',
                    successMessage: 'Image deleted successfully.'
                });
            });
        }
    </script>
@endsection
