{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    {{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="about-us-create-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Add capability</h2>
        </div> --}}
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a
                                class="breadcrumbLink"href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('capability.index') }}">Capabilities</a>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Capability</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Capability Details</h4>
            </div>
            <div class="card-body">
                <form id="heroSectionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="title" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ $capability->title }}" required>
                    </div>

                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ $capability->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="capability_icon" style="font-weight: bold;">Category Icon</label>
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text bg-white" id="icon-preview"
                                style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                            <input type="text" class="form-control" id="capability_icon" name="capability_icon" required
                                placeholder="Select an icon..." aria-describedby="icon-preview"
                                value="{{ $capability->capability_icon }}" style="height: 40px;">
                            <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                data-iconset="fontawesome5"
                                style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                    class="fas fa-icons"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="progress_indicator" style="font-weight: bold;">Progress Indicator <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="progress_indicator" name="progress_indicator"
                            value="{{ $capability->progress_indicator }}" min="1" 
                             oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/^0+/, '');" required>
                    </div>
                    <div class="form-group">
                        <label for="progress_label" style="font-weight: bold;">Progress Label <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="progress_label" name="progress_label"
                            value="{{ $capability->progress_label }}" required>
                    </div>
                    {{-- <div class="form-group">
                        <label for="image" style="font-weight: bold;">Image <span style="color: red;">*</span></label>
                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            <span id="imageName">{{ $capability->image ? basename($capability->image) : 'No image selected' }}</span>
                            <img id="imagePreview" src="{{ $capability->image ? asset($capability->image) : '' }}" alt="Image Preview"
                                style="display: {{ $capability->image ? 'block' : 'none' }}; width: 100px; height: 100px; margin-top: 10px;">
                            @if ($capability->image)
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
                                $imagePath = $capability->image ? public_path($capability->image) : null;
                                $imageExists = $imagePath && File::exists($imagePath);
                                $imageUrl = $imageExists ? asset($capability->image) : asset('images/Default.jpg');
                                $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                            @endphp
                            <span id="imageName">
                                {{ !$isDefaultImage && $imageExists ? basename($capability->image) : 'No image' }}
                            </span>
                            {{-- <span id="imageName">
                                {{ $imageExists ? basename($capability->image) : 'No image selected' }}
                            </span> --}}
                        </div>
                        <div id="dlt_btn_div" class="dlt_btn_div">
                            @php
                                $imagePath = $capability->image ? public_path($capability->image) : null;
                                $imageExists = $imagePath && File::exists($imagePath);
                                $imageUrl = $imageExists ? asset($capability->image) : asset('images/Default.jpg');
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
                    <a href="{{ route('capability.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Include common icon picker JS -->
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
            if (!formData.get('capability_icon')) {
                $('#capability_icon').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Icon is required.</span>'
                );
                isValid = false;
            }
            if (!formData.get('progress_indicator')) {
                $('#progress_indicator').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Progress Indicator is required.</span>'
                );
                isValid = false;
            }
            if (!formData.get('progress_label')) {
                $('#progress_label').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Progress Label is required.</span>'
                );
                isValid = false;
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
            // if (!formData.get('image') || !formData.get('image').name) {
            //     $('#uploadImageBtn').after(
            //         '<span class="error-message" style="color: red;">Image is required.</span>');
            //     isValid = false;
            // }
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

            fetch('{{ route('api.capability.update', $capability->id) }}', {
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
                        notify('success', 'capability Section Details updated Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('capability.index') }}';
                        }, 1500);

                    } else {
                        notify('error', data.message ||
                            'There was an error updating the capability Section Detail page.');
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

        // document.getElementById('title').addEventListener('input', function() {
        //     this.parentNode.querySelector('.error-message').textContent = '';
        // });
        const titleInput = document.getElementById('title');
        const errorSpan = titleInput.parentNode.querySelector('.error-message');

        titleInput.addEventListener('input', function() {
            let value = this.value

            if (value.length > 25) {
                this.value = value.slice(0, 25); // stop extra characters
                errorSpan.textContent = 'Title cannot exceed 25 characters.';
            } else if (value.trim() === '') {
                errorSpan.textContent = 'Title is required.';
            } else {
                errorSpan.textContent = '';
            }
        });

        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });
        document.getElementById('capability_icon').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('progress_indicator').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('progress_label').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
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

            // Update icon preview on icon select and on input change
            function updateIconPreview(iconClass) {
                var preview = document.getElementById('icon-preview');
                preview.innerHTML = iconClass ? '<i class="' + iconClass + '"></i>' : '';
            }
            // On icon picker select
            $('[role="iconpicker"]').on('iconpickerSelected', function(e) {
                $('#capability_icon').val(e.iconpickerValue);
                $('#capability_icon')[0].dispatchEvent(new Event('input'));
                updateIconPreview(e.iconpickerValue);
            });
            // On manual input change
            $('#capability_icon').on('input', function() {
                updateIconPreview(this.value);
            });
            // Initialize preview if value exists
            updateIconPreview($('#capability_icon').val());
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
                    url: '{{ route('api.capabilities.deleteImage', $capability->id) }}',
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
