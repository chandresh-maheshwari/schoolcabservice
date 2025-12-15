{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    {{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="cms-category-edit-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Edit Client</h2>
        </div> --}}
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('client.index') }}">Client</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Client</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="cms-category-edit-header">Edit Client</h4>
            </div>

            <div class="card-body">
                <form id="clientForm">
                    @csrf
                    @method('PUT')
                    {{-- <div class="form-group">
                    <label for="name" style="font-weight: bold;">Category Name <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $category->name }}" required>
                </div> --}}
                    <div class="form-group">
                        <label for="image" style="font-weight: bold;">Image <span style="color: red;">*</span></label>
                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Client Image</button>
                            @php
                                $imagePath = $client->client ? public_path($client->client) : null;
                                $imageExists = $imagePath && File::exists($imagePath);
                                $imageUrl = $imageExists ? asset($client->client) : asset('images/Default.jpg');
                                $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                            @endphp
                            <span id="imageName">
                                {{ !$isDefaultImage && $imageExists ? basename($client->image) : 'No image' }}
                            </span>
                            {{-- @if (!$isDefaultImage)
                                <span id="imageName">
                                    {{ $imageExists ? basename($client->client) : 'No image selected' }}</span>
                            @else
                                <span id="imageName">No image</span>
                            @endif --}}
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
                    {{-- <div id="name-error" style="color: red; display: none;">Please enter a category name.</div> --}}
                    <button type="button" class="btn btn-primary" id="updateBtn"
                        style="background-color: #2C9DD4; color: white;">Update</button>
                    <a href="{{ route('client.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/common_js.js') }}"></script>
    <script>
        document.getElementById('updateBtn').addEventListener('click', function() {

            var formData = new FormData(document.getElementById('clientForm'));
            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            // Validate form
            var isValid = true;

            var imageInput = document.getElementById('image');
            var imagePreview = document.getElementById('imagePreview');
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');
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

            fetch('{{ route('api.client.update', $client->id) }}', {
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
                        notify('success', 'Client Updated Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('client.index') }}';
                        }, 1500);
                    } else {
                        notify('error', data.message || 'There was an error updating the Client.');
                    }
                })
                .catch(error => {
                    Swal.close();
                    notify('error', 'An unexpected error occurred.');
                });
        });

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
        document.getElementById('image').addEventListener('change', function() {
            $('#uploadImageBtn').next('.error-message').remove();
        });

        // Handle delete image
        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ url('api/client/' . $client->id . '/image') }}',
                    csrfToken: document.querySelector('input[name="_token"]').value,
                    imagePreviewSelector: '#imagePreview',
                    buttonSelector: '#deleteImageBtn',
                    nameSelector: '#imageName',
                    successMessage: 'Image deleted successfully.'
                });
            });
            // $('.error-message').remove();
            // $('#uploadImageBtn').after('<span class="error-message" style="color:red;">Imagssssssse is required.</span>');
        }


        document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });

        // const removeImageBtn = document.getElementById('removeImageBtn');
        // if (removeImageBtn) {
        //     removeImageBtn.addEventListener('click', function() {
        //         window.clearImageSelection({
        //             imagePreviewSelector: '#portfolioImagePreview',
        //             imageNameSelector: '#portfolioImageName',
        //             imageInputSelector: '#image',
        //             removeImageBtnSelector: '#removeImageBtn'
        //         });
        //     });
        // }
    </script>
@endsection
