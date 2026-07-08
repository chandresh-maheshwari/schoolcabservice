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
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add Benefit Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Benefit Section Details</h4>
            </div>
            <div class="card-body">
                <form id="benefitSectionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="name" style="font-weight: bold;">Name <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Short Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="short_des" name="short_des" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Image <span style="color:red;">*</span>
                            <small style="color:#6c757d;">(Image must be at least 750 x 680 pixels)</small>
                        </label><br>
                        <button type="button" class="btn btn-primary" id="ImageBtn"
                            onclick="document.getElementById('image').click();">Upload Image</button>
                        <input type="file" id="image" name="image" accept="image/*" style="display:none;"
                            onchange="previewImage(event)">
                        <span id="imageName"></span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn"><i
                                class="fas fa-trash"></i></button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" id="submitBtn"
                            style="background-color: #2C9DD4; color: white;">Submit</button>
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

            // Validate form (only required fields in this form)
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
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "")) {
                $('#ImageBtn').after(
                    '<span class="error-message" style="color: red;">Image is required.</span>');
                isValid = false;
            }

            if (!isValid) return;

            fetch('{{ route('api.benefitSection.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    }
                })
                .then(async response => {
                    let data;
                    try {
                        data = await response.json();
                    } catch (e) {
                        throw new Error('Invalid server response');
                    }

                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'Something went wrong');
                    }

                    return data;
                })
                .then(data => {
                    notify('success', data.message || 'Benefit Section created successfully!');
                    setTimeout(() => {
                        window.location.href = '{{ route('benefitSection.index') }}';
                    }, 1500);
                })
                .catch(error => {
                    notify('error', error.message || 'Unexpected error');
                });
        });

        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });

        document.getElementById('image').addEventListener('change', function() {
            $('#ImageBtn').next('.error-message').remove();
        })

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
