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
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add Testimonial Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Testimonial Section Details</h4>
            </div>
            <div class="card-body">
                <form id="testimonialSectionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="name" style="font-weight: bold;">Name <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                     <div class="form-group">
                        <label> Profile Image <span style="color:red;">*</span></label><br>
                        <button type="button" class="btn btn-primary" id="ImageBtn"
                            onclick="document.getElementById('profile_image').click();">Upload Image</button>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display:none;"
                            onchange="previewImage(event)">
                        <span id="imageName"></span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn"><i
                                class="fas fa-trash"></i></button>
                    </div>
                    <div class="form-group">
                        <label for="designation" style="font-weight: bold;">Designation <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="designation" name="designation" required>
                    </div>
                    <div class="form-group">
                        <label for="tagline" style="font-weight: bold;">Tagline <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="tagline" name="tagline" required>
                    </div>
                    <div class="form-group">
                        <label for="rating" style="font-weight: bold;">Rating <span style="color: red;">*</span></label>
                        <input type="number" class="form-control" id="rating" name="rating" min="1" max="5" required>
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('testimonialSection.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
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

            // Validate form (only required fields in this form)
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
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#ImageBtn').after(
                    '<span class="error-message" style="color: red;">Profile Image is required.</span>');
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('{{ route('api.testimonialSection.store') }}', {
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
                        notify('success', 'Testimonial Section details created Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('testimonialSection.index') }}';
                        }, 1500);
                    } else {
                        notify('error', data.message || 'There was an error creating the testimonial section details.');
                    }
                })
                .catch(error => {
                    Swal.close();
                    notify('error', 'An unexpected error occurred.');
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
        })

        document.getElementById('designation').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });
        document.getElementById('tagline').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });
        document.getElementById('rating').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });

       document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#profile_image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });

    </script>
@endsection
