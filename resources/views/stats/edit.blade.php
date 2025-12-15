@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('stats.index') }}">Stats</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Stats</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Stats Details</h4>
            </div>
            <div class="card-body">
                <form id="statsForm" enctype="multipart/form-data">
                    @csrf

                    {{-- Hidden input to check if image already exists --}}
                    @php
                        $imagePath = $stats->image ? public_path($stats->image) : null;
                        $imageExists = $imagePath && File::exists($imagePath);
                        $imageUrl = $imageExists ? asset($stats->image) : asset('images/Default.jpg');
                        $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                    @endphp
                    <input type="hidden" id="has_existing_image" value="{{ $imageExists ? 1 : 0 }}">

                    {{-- Stats Counter --}}
                    <div class="form-group">
                        <label for="stats_counter" style="font-weight: bold;">Stats Counter <span
                                style="color: red;">*</span></label>
                        <input type="number" class="form-control" id="stats_counter" name="stats_counter"
                            value="{{ $stats->stats_counter }}" min="1"  oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/^0+/, '');" required>
                        <span class="error-message" style="color: red;"></span>
                    </div>

                    {{-- Stat Icon --}}
                    <div class="form-group">
                        <label for="stat_icon" style="font-weight: bold;">Stat Icon <span
                                style="color: red;">*</span></label>
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text bg-white" id="stat-icon-preview"
                                style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                            <input type="text" class="form-control" id="stat_icon" name="stat_icon" required
                                placeholder="Select an icon..." aria-describedby="stat-icon-preview" style="height: 40px;"
                                value="{{ $stats->stat_icon }}">
                            <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                data-iconset="fontawesome5" data-input="stat_icon" data-preview="stat-icon-preview"
                                style="height: 40px; border-left: 0; border: 1px solid #ced4da;">
                                <i class="fas fa-icons"></i>
                            </button>
                        </div>
                        <span class="error-message" style="color: red;"></span>
                    </div>

                    {{-- Stats Title --}}
                    <div class="form-group">
                        <label for="stats_title" style="font-weight: bold;">Stats Title <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="stats_title" name="stats_title"
                            value="{{ $stats->stats_title }}" required>
                        <span class="error-message" style="color: red;"></span>
                    </div>

                    {{-- Image Upload --}}
                    <div class="form-group">
                        <label for="image" style="font-weight: bold;">Image <span style="color: red;">*</span></label>
                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            {{-- @if (!$isDefaultImage)
                                <span id="imageName">
                                    {{ $imageExists ? basename($client->client) : 'No image selected' }}</span>
                            @else
                                <span id="imageName">No image</span>
                            @endif --}}
                            <span id="imageName">
                                {{ !$isDefaultImage && $imageExists ? basename($stats->image) : 'No image' }}
                            </span>
                        </div>
                        <div id="dlt_btn_div" class="dlt_btn_div">
                            <img id="imagePreview" src="{{ $imageUrl }}" alt="Image Preview"
                                style="display: block; width: 100px; height: 100px; margin-top: 10px;">
                            <button type="button" id="removeImageBtn" class="btn btn-sm"
                                style="display: none; margin-top: 10px; margin-left: 10px;">
                                <i class="fas fa-trash"></i>
                            </button>
                            @if (!$isDefaultImage)
                                <button type="button" id="deleteImageBtn" class="btn btn-sm"
                                    style="margin-top: 10px; margin-left: 10px;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endif
                        </div>
                        <span class="error-message" style="color: red;"></span>
                    </div>

                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('stats.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/common-iconpicker.js') }}"></script>
    <script src="{{ asset('js/common_js.js') }}"></script>

    <script>
        document.getElementById('submitBtn').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('statsForm'));
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

            let isValid = true;

            // Validation
            if (!formData.get('stats_counter')) {
                document.querySelector('#stats_counter + .error-message').textContent =
                    'Stats Counter is required.';
                isValid = false;
            }
            if (!formData.get('stats_title')) {
                document.querySelector('#stats_title + .error-message').textContent = 'Stats Title is required.';
                isValid = false;
            }
            if (!formData.get('stat_icon')) {
                document.querySelector('#stat_icon').closest('.form-group').querySelector('.error-message')
                    .textContent =
                    'Stat Icon is required.';
                isValid = false;
            }

            // ✅ Image validation fix
            // const hasExistingImage = document.getElementById('has_existing_image').value === '1';
            // if ((!formData.get('image') || !formData.get('image').name) && !hasExistingImage) {
            //     document.querySelector('#uploadImageBtn').insertAdjacentHTML('afterend',
            //         '<span class="error-message" style="color: red;">Image is required.</span>');
            //     isValid = false;
            // }
            var imageInput = document.getElementById('image');
            var imagePreview = document.getElementById('imagePreview');
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');

            // if (!imageInput.files.length && isDefaultImage) {
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "")) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#uploadImageBtn').after(
                    '<span class="error-message" style="color: red;">Image is required.</span>');
                isValid = false;
            }

            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.stats.update', $stats->id) }}', {
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
                        notify('success', 'Stats Updated Successfully!');
                        setTimeout(() => window.location.href = '{{ route('stats.index') }}', 1500);
                    } else {
                        notify('error', data.message || 'Error updating stats.');
                    }
                })
                .catch(() => {
                    Swal.close();
                    notify('error', 'Unexpected error occurred.');
                });
        });

        document.getElementById('stats_title').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });

        document.getElementById('stats_counter').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });

        document.getElementById('image').addEventListener('change', function() {
            $('#uploadImageBtn').next('.error-message').remove();
        });

        // Image preview
        // function previewImage(event) {
        //     const reader = new FileReader();
        //     reader.onload = function() {
        //         document.getElementById('imagePreview').src = reader.result;
        //         document.getElementById('imagePreview').style.display = 'block';
        //     };
        //     reader.readAsDataURL(event.target.files[0]);
        //     document.getElementById('imageName').textContent = event.target.files[0].name;
        //     document.getElementById('has_existing_image').value = 1;
        // }

        // Delete existing image
        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ url('api/stats/' . $stats->id . '/image') }}',
                    csrfToken: document.querySelector('input[name="_token"]').value,
                    imagePreviewSelector: '#imagePreview',
                    buttonSelector: '#deleteImageBtn',
                    nameSelector: '#imageName',
                    successMessage: 'Image deleted successfully.'
                });
                // When image deleted, mark as no image present
                document.getElementById('has_existing_image').value = 0;
            });
        }

        // Clear image selection
        document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#image',
                removeImageBtnSelector: '#removeImageBtn'
            });
            document.getElementById('has_existing_image').value = 0;
        });
    </script>
@endsection
