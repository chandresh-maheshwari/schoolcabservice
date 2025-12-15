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
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('alternative.index') }}">Alternative</a>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Alternative</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Alternative Details</h4>
            </div>
            <div class="card-body">
                <form id="heroSectionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="title" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ $alternative->title }}" required>
                    </div>

                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ $alternative->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="alternative_icon" style="font-weight: bold;">Category Icon</label>
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text bg-white" id="icon-preview"
                                style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                            <input type="text" class="form-control" id="alternative_icon" name="alternative_icon"
                                value="{{ $alternative->alternative_icon }}" required placeholder="Select an icon..."
                                aria-describedby="icon-preview" style="height: 40px;">
                            <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                data-iconset="fontawesome5"
                                style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                    class="fas fa-icons"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="button_title" style="font-weight: bold;">Button Title <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="button_title" name="button_title"
                            value="{{ $alternative->button_title }}" required>
                    </div>
                    {{-- <div class="form-group">
                        <label for="image" style="font-weight: bold;">Image <span style="color: red;">*</span></label>
                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            <span id="imageName">{{ $alternative->image ? basename($alternative->image) : 'No image selected' }}</span>
                            <img id="imagePreview" src="{{ $alternative->image ? asset($alternative->image) : '' }}" alt="Image Preview"
                                style="display: {{ $alternative->image ? 'block' : 'none' }}; width: 100px; height: 100px; margin-top: 10px;">
                            @if ($alternative->image)
                                <button type="button" id="deleteImageBtn" class="btn btn-danger btn-sm" style="margin-top: 10px; margin-left: 10px;">
                                    <i class="fas fa-trash"></i> Delete Image
                                </button>
                            @endif
                        </div>
                    </div> --}}
                    <div class="form-group">
                        <label for="image" style="font-weight: bold;">Image <span style="color: red;">*</span></label>
                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            @php
                                $imagePath = $alternative->image ? public_path($alternative->image) : null;
                                $imageExists = $imagePath && File::exists($imagePath);
                                $imageUrl = $imageExists ? asset($alternative->image) : asset('images/Default.jpg');
                                $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                            @endphp
                            <span id="imageName">
                                {{ !$isDefaultImage && $imageExists ? basename($alternative->image) : 'No image' }}
                            </span>
                            {{-- <span id="imageName">
                                {{ $imageExists ? basename($alternative->image) : 'No image selected' }}
                            </span> --}}
                        </div>
                        <div id="dlt_btn_div" class="dlt_btn_div">
                            @php
                                $imagePath = $alternative->image ? public_path($alternative->image) : null;
                                $imageExists = $imagePath && File::exists($imagePath);
                                $imageUrl = $imageExists ? asset($alternative->image) : asset('images/Default.jpg');
                                $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                            @endphp
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
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('alternative.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- <script src="{{ asset('js/common-iconpicker.js') }}"></script> --}}
    <script src="{{ asset('js/common.js') }}"></script>

    <script>
        CKEDITOR.replace('description');

        // function previewImage(event) {
        //     var reader = new FileReader();
        //     reader.onload = function() {
        //         var output = document.getElementById('imagePreview');
        //         output.src = reader.result;
        //         output.style.display = 'block';
        //     }
        //     reader.readAsDataURL(event.target.files[0]);

        //     var imageName = document.getElementById('imageName');
        //     imageName.textContent = event.target.files[0].name;
        // }

        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('heroSectionForm'));
            formData.set('description', CKEDITOR.instances.description.getData());

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            // Validate form (only required fields in this form)
            var isValid = true;
            if (!formData.get('title')) {
                document.getElementById('title').nextElementSibling.textContent = 'Title is required.';
                isValid = false;
            }
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }
            if (!formData.get('alternative_icon')) {
                $('#alternative_icon').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Icon is required.</span>'
                );
                isValid = false;
            }
            if (!formData.get('button_title')) {
                $('#button_title').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Button Title is required.</span>'
                );
                isValid = false;
            }
            // if (!formData.get('image') || !formData.get('image').name) {
            //     $('#uploadImageBtn').after(
            //         '<span class="error-message" style="color: red;">Image is required.</span>');
            //     isValid = false;
            // }
            var imageInput = document.getElementById('image');
            var imagePreview = document.getElementById('imagePreview');
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');

            // if (!imageInput.files.length && isDefaultImage) {
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "") ) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#uploadImageBtn').after(
                    '<span class="error-message" style="color: red;">Image is required.</span>');
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

            fetch('{{ route('api.alternative.update', $alternative->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'X-HTTP-Method-Override': 'PUT'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        notify('success', 'Alternative Updated Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('alternative.index') }}';
                        }, 1500);

                    } else {
                        notify('error', data.message ||
                            'There was an error updating the alternative Section Detail.');
                    }
                })
                .catch(error => {
                    Swal.close();
                    notify('error', 'An unexpected error occurred.');

                });
        });

        // Edit error message spans for regular inputs
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
        const titleInput = document.getElementById('title');
        const errorSpan = titleInput.parentNode.querySelector('.error-message');

        titleInput.addEventListener('input', function() {
            let value = this.value;

            if (value.length > 30) {
                this.value = value.slice(0, 30); // stop extra characters
                errorSpan.textContent = 'Title cannot exceed 30 characters.';
            } else if (value.trim() === '') {
                errorSpan.textContent = 'Title is required.';
            } else {
                errorSpan.textContent = '';
            }
        });

        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });
        document.getElementById('alternative_icon').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('button_title').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').remove();
        });
        document.getElementById('image').addEventListener('change', function() {
            $('#uploadImageBtn').next('.error-message').remove();
        });

        $(document).ready(function() {
            // Initialize icon picker
            $('[role="iconpicker"]').iconpicker({
                iconset: 'fontawesome5',
                input: '#category_icon',
            });

            // Edit icon preview on icon select and on input change
            function updateIconPreview(iconClass) {
                var preview = document.getElementById('icon-preview');
                preview.innerHTML = iconClass ? '<i class="' + iconClass + '"></i>' : '';
            }
            // On icon picker select
            $('[role="iconpicker"]').on('iconpickerSelected', function(e) {
                $('#alternative_icon').val(e.iconpickerValue);
                $('#alternative_icon')[0].dispatchEvent(new Event('input'));
                updateIconPreview(e.iconpickerValue);
            });
            // On manual input change
            $('#alternative_icon').on('input', function() {
                updateIconPreview(this.value);
            });
            // Initialize preview if value exists
            updateIconPreview($('#alternative_icon').val());
            $('[role="iconpicker"]').on('click', function() {
                setTimeout(function() {
                    var $popover = $('.iconpicker-popover.popover.fade.bottom');
                    if ($popover.is(':visible')) {
                        $popover.css('display', 'none');
                    } else {
                        $popover.css('display', 'block');
                    }
                }, 10);
            });
        });

        /* Previous inline delete handler replaced by common helper */
        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.alternative.deleteImage', $alternative->id) }}',
                    csrfToken: document.querySelector('input[name="_token"]').value,
                    imagePreviewSelector: '#imagePreview',
                    buttonSelector: '#deleteImageBtn',
                    nameSelector: '#imageName',
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
        // Removed select2 initializations for non-existent fields
    </script>
@endsection
