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
                        <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('hero.index') }}">Hero</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Hero Section
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
                    Hero Section Details</h4>
            </div>
            <div class="card-body">
                <form id="heroSectionForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="title" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ $hero->title }}"
                            required>
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
                                $imagePath = $hero->image ? public_path($hero->image) : null;
                                $imageExists = $imagePath && File::exists($imagePath);
                                $imageUrl = $imageExists ? asset($hero->image) : asset('images/Default.jpg');
                                $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                            @endphp
                                <span id="imageName">
                                {{ !$isDefaultImage && $imageExists ? basename($hero->image) : 'No image' }}
                            </span>
                            {{-- @if (!$isDefaultImage)
                            <span id="imageName">{{ $hero->image ? basename($hero->image) : 'No image selected' }}</span>
                            @else
                            <span id="imageName">No image</span>
                            @endif --}}
                            {{-- <img id="imagePreview" src="{{ asset($hero->image) }}" alt="Image Preview"
                                style="display: block; width: 100px; height: 100px; margin-top: 10px;"> --}}
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
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ $hero->description }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="button_title_1" style="font-weight: bold;">Button Title 1 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="button_title_1" name="button_title_1"
                            value="{{ $hero->button_title_1 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="button_title_2" style="font-weight: bold;">Button Title 2 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="button_title_2" name="button_title_2"
                            value="{{ $hero->button_title_2 }}" required>
                    </div>

                    <!-- Stat Items Section -->
                    <div style="border: #2C9DD4 1px solid; padding: 10px; border-radius: 5px;">
                        <div class="form-group">
                            <h5 style="font-weight: bold; margin-top: 20px; margin-bottom: 15px; color: #2d336b;">Stat Items
                            </h5>
                        </div>

                        <div class="form-group">
                            <label for="stat_counter_1" style="font-weight: bold;">Stat Counter 1 <span
                                    style="color: red;">*</span></label> 
                            <input type="number" class="form-control" id="stat_counter_1" name="stat_counter_1" min="1"  oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/^0+/, '');"
                                value="{{ $hero->stat_counter_1 }}" required>
                        </div>
                        <div class="form-group">
                            <label for="stat_title_1" style="font-weight: bold;">Stat Title 1 <span
                                    style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="stat_title_1" name="stat_title_1"
                                value="{{ $hero->stat_title_1 }}" required>
                        </div>
                        <div class="form-group">
                            <label for="stat_icon_1" style="font-weight: bold;">Stat Icon 1 <span
                                    style="color: red;">*</span></label>
                            <div class="input-group" style="max-width: 400px;">
                                <span class="input-group-text bg-white" id="stat-icon-preview-1"
                                    style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                                <input type="text" class="form-control" id="stat_icon_1" name="stat_icon_1"
                                    value="{{ $hero->stat_icon_1 }}" required placeholder="Select an icon..."
                                    aria-describedby="stat-icon-preview-1" style="height: 40px;">
                                <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                    data-iconset="fontawesome5" data-input="stat_icon_1"
                                    data-preview="stat-icon-preview-1"
                                    style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                        class="fas fa-icons"></i></button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="stat_counter_2" style="font-weight: bold;">Stat Counter 2 <span
                                    style="color: red;">*</span></label>
                            <input type="number" class="form-control" id="stat_counter_2" name="stat_counter_2" min="1"  oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/^0+/, '');"
                                value="{{ $hero->stat_counter_2 }}" required>
                        </div>
                        <div class="form-group">
                            <label for="stat_title_2" style="font-weight: bold;">Stat Title 2 <span
                                    style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="stat_title_2" name="stat_title_2"
                                value="{{ $hero->stat_title_2 }}" required>
                        </div>
                        <div class="form-group">
                            <label for="stat_icon_2" style="font-weight: bold;">Stat Icon 2 <span
                                    style="color: red;">*</span></label>
                            <div class="input-group" style="max-width: 400px;">
                                <span class="input-group-text bg-white" id="stat-icon-preview-2"
                                    style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                                <input type="text" class="form-control" id="stat_icon_2" name="stat_icon_2"
                                    value="{{ $hero->stat_icon_2 }}" required placeholder="Select an icon..."
                                    aria-describedby="stat-icon-preview-2" style="height: 40px;">
                                <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                    data-iconset="fontawesome5" data-input="stat_icon_2"
                                    data-preview="stat-icon-preview-2"
                                    style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                        class="fas fa-icons"></i></button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="stat_counter_3" style="font-weight: bold;">Stat Counter 3 <span
                                    style="color: red;">*</span></label>
                            <input type="number" class="form-control" id="stat_counter_3" name="stat_counter_3" min="1"  oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/^0+/, '');"
                                value="{{ $hero->stat_counter_3 }}" required>
                        </div>
                        <div class="form-group">
                            <label for="stat_title_3" style="font-weight: bold;">Stat Title 3 <span
                                    style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="stat_title_3" name="stat_title_3"
                                value="{{ $hero->stat_title_3 }}" required>
                        </div>
                        <div class="form-group">
                            <label for="stat_icon_3" style="font-weight: bold;">Stat Icon 3 <span
                                    style="color: red;">*</span></label>
                            <div class="input-group" style="max-width: 400px;">
                                <span class="input-group-text bg-white" id="stat-icon-preview-3"
                                    style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                                <input type="text" class="form-control" id="stat_icon_3" name="stat_icon_3"
                                    value="{{ $hero->stat_icon_3 }}" required placeholder="Select an icon..."
                                    aria-describedby="stat-icon-preview-3" style="height: 40px;">
                                <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                    data-iconset="fontawesome5" data-input="stat_icon_3"
                                    data-preview="stat-icon-preview-3"
                                    style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                        class="fas fa-icons"></i></button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Update</button>
                    <a href="{{ route('hero.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- Include common icon picker JS -->
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>
    <script src="{{ asset('js/common_js.js') }}"></script>

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

            // Validate form
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
            // Image optional on edit
            if (!formData.get('button_title_1')) {
                $('#button_title_1').next('.error-message').text('Button Title 1 is required.');
                isValid = false;
            }
            if (!formData.get('button_title_2')) {
                $('#button_title_2').next('.error-message').text('Button Title 2 is required.');
                isValid = false;
            }

            // Validate stat counters
            if (!formData.get('stat_counter_1')) {
                $('#stat_counter_1').next('.error-message').text('Stat Counter 1 is required.');
                isValid = false;
            }
            if (!formData.get('stat_counter_2')) {
                $('#stat_counter_2').next('.error-message').text('Stat Counter 2 is required.');
                isValid = false;
            }
            if (!formData.get('stat_counter_3')) {
                $('#stat_counter_3').next('.error-message').text('Stat Counter 3 is required.');
                isValid = false;
            }

            // Validate stat titles
            if (!formData.get('stat_title_1')) {
                $('#stat_title_1').next('.error-message').text('Stat Title 1 is required.');
                isValid = false;
            }
            if (!formData.get('stat_title_2')) {
                $('#stat_title_2').next('.error-message').text('Stat Title 2 is required.');
                isValid = false;
            }
            if (!formData.get('stat_title_3')) {
                $('#stat_title_3').next('.error-message').text('Stat Title 3 is required.');
                isValid = false;
            }

            // Validate stat icons
            if (!formData.get('stat_icon_1')) {
                $('#stat_icon_1').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Stat Icon 1 is required.</span>'
                );
                isValid = false;
            }
            if (!formData.get('stat_icon_2')) {
                $('#stat_icon_2').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Stat Icon 2 is required.</span>'
                );
                isValid = false;
            }
            if (!formData.get('stat_icon_3')) {
                $('#stat_icon_3').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Stat Icon 3 is required.</span>'
                );
                isValid = false;
            }
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

            fetch('{{ route('api.hero.update', $hero->id) }}', {
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
                        notify('success', 'Hero Section Details updated Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('hero.index') }}';
                        }, 1500);

                    } else {
                        notify('error', data.message ||
                            'There was an error updating the Hero Section Detail page.');
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
            let value = this.value;

            if (value.length > 40) {
                this.value = value.slice(0, 40); // stop extra characters
                errorSpan.textContent = 'Title cannot exceed 40 characters.';
            } else if (value.trim() === '') {
                errorSpan.textContent = 'Title is required.';
            } else {
                errorSpan.textContent = '';
            }
        });
        
        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });

        document.getElementById('image').addEventListener('change', function() {
            $('#uploadImageBtn').next('.error-message').remove();
        });

        document.getElementById('button_title_1').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });
        document.getElementById('button_title_2').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });
        // Clear errors for stat fields
        ['stat_counter_1', 'stat_counter_2', 'stat_counter_3', 'stat_title_1', 'stat_title_2', 'stat_title_3'].forEach(
            function(id) {
                var el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', function() {
                        $(this).next('.error-message').text('');
                    });
                }
            });
        ['stat_icon_1', 'stat_icon_2', 'stat_icon_3'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', function() {
                    $(this).closest('.form-group').find('.error-message').remove();
                });
            }
        });

        // Handle delete image
        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ url('api/hero/' . $hero->id . '/image') }}',
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
    </script>
@endsection
