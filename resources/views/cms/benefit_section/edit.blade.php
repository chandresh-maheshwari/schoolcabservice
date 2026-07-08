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
                                href="{{ route('benefitSection.index') }}">Benefit Section</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Benefit Section
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
                    Benefit Section Details</h4>
            </div>
            <div class="card-body">
                <form id="benefitSectionForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="name" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="{{ $benefitSection->name }}" required>
                    </div>
                    <div class="form-group">
                        <label for="short_des" style="font-weight: bold;">Short Description <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="short_des" name="short_des"
                            value="{{ $benefitSection->short_des }}" required>
                    </div>
                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" required>{{ $benefitSection->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Image <span style="color:red;">*</span>
                            <small style="color:#6c757d;">(Image must be at least 750 x 680 pixels)</small>
                        </label><br>
                        <button type="button" class="btn btn-primary" id="ImageBtn"
                            onclick="document.getElementById('image').click();">Upload Image</button>
                        <input type="file" id="image" name="image" accept="image/*" style="display:none;"
                            onchange="previewImage(event)">
                        <br>
                        @php
                            $imagePath = $benefitSection->image
                                ? public_path('storage/benefitSection/' . $benefitSection->image)
                                : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $imageUrl = $imageExists
                                ? asset('storage/benefitSection/' . $benefitSection->image)
                                : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName">
                            {{ $imageExists && !$isDefaultImage ? basename($benefitSection->image) : 'No image' }}
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
            var formData = new FormData(document.getElementById('benefitSectionForm'));
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
            if (!formData.get('short_des')) {
                document.getElementById('short_des').nextElementSibling.textContent =
                    'Short Description is required.';
                isValid = false;
            }
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }

            var imageInput = document.getElementById('image');
            var imagePreview = document.getElementById('imagePreview');
            var currentImageSrc = imagePreview.getAttribute('src');

            if (!imageInput.files.length && (currentImageSrc == "#" || currentImageSrc == "")) {
                $('#ImageBtn').after('<span class="error-message" style="color: red;">Image is required.</span>');
                isValid = false;
            }

            if (!isValid) return;

            fetch('{{ route('api.benefitSection.update', $benefitSection->id) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: (() => {
                        formData.append('_method', 'PUT');
                        return formData;
                    })()
                })
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) throw data;
                    return data;
                })
                .then(data => {
                    notify('success', data.message || 'Benefit section updated successfully.');
                    window.location.href = '{{ route('benefitSection.index') }}';
                })
                .catch(error => {
                    notify('error', error.message || 'Something went wrong.');
                });
        });

        document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });

        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.benefitSection.benefitImage', $benefitSection->id) }}',
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
