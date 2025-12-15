{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    {{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="user-listing-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Add Contact Info</h2>
        </div> --}}
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Header Info</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="user-listing-header">Edit Header Info</h4>
            </div>
            <div class="card-body">
                <form id="headerForm">
                    @csrf

                    <div class="form-group">
                        <label for="title" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ $header->title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="link" style="font-weight: bold;">Link <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="link" name="link" value="{{ $header->link }}"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="button_title" style="font-weight: bold;">Button Title <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="button_title" name="button_title"
                            value="{{ $header->button_title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="button_link" style="font-weight: bold;">Button Link <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="button_link" name="button_link"
                            value="{{ $header->button_link }}" required>
                    </div>
                    <div class="form-group">
                        <label for="image" style="font-weight: bold;">Logo Image <span
                                style="color: red;">*</span></label>
                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            <span
                                id="imageName">{{ $header->image ? basename($header->image) : 'No image selected' }}</span>
                            {{-- <img id="imagePreview" src="{{ asset($header->image) }}" alt="Image Preview"
                                style="display: block; width: 100px; height: 100px; margin-top: 10px;"> --}}
                        </div>
                        <div id="dlt_btn_div" class="dlt_btn_div">
                            @php
                                $imagePath = $header->image ? public_path($header->image) : null;
                                $imageExists = $imagePath && File::exists($imagePath);
                                $imageUrl = $imageExists ? asset($header->image) : asset('images/Default.jpg');
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
                    <a href="{{ route('header.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('headerForm'));

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            var isValid = true;
            if (!formData.get('title')) {
                document.getElementById('title').nextElementSibling.textContent = 'Title is required.';
                isValid = false;
            }
            const urlRegex = /^(https?:\/\/[a-zA-Z0-9.-]+\/[^\s]*)$/i;
            const linkData = formData.get('link');
            if (!linkData) {
                document.getElementById('link').nextElementSibling.textContent = "Link is required.";
                isValid = false;
            } else if (!urlRegex.test(linkData)) {
                document.getElementById('link').nextElementSibling.textContent = 'Please enter a valid URL.';
                isValid = false;
            } else {
                document.getElementById('link').nextElementSibling.textContent = '';
            }

            if (!formData.get('button_title')) {
                document.getElementById('button_title').nextElementSibling.textContent =
                    'Button Title is required.';
                isValid = false;
            }

            const buttonLinkData = formData.get('button_link');
            if (!buttonLinkData) {
                document.getElementById('button_link').nextElementSibling.textContent = "Button Link is required.";
                isValid = false;
            } else if (!urlRegex.test(buttonLinkData)) {
                document.getElementById('button_link').nextElementSibling.textContent = 'Please enter a valid URL.';
                isValid = false;
            } else {
                document.getElementById('button_link').nextElementSibling.textContent = '';
            }
            if (!formData.get('image') || !formData.get('image').name) {
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
            fetch('{{ route('api.header.update', $header->id) }}', {
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
                        notify('success', 'Header created Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('header.index') }}';
                        }, 1500);
                    } else if (data.errors) {
                        if (data.errors.mobile_number && data.errors.mobile_number.length > 0) {
                            notify('error', data.errors.mobile_number[0]);
                        } else if (data.errors.email && data.errors.email.length > 0) {
                            notify('error', data.errors.email[0]);
                        } else {
                            let firstField = Object.keys(data.errors)[0];
                            notify('error', data.errors[firstField][0]);
                        }
                    } else if (data.message) {
                        notify('error', data.message);
                    } else {
                        notify('error', data.message || 'There was an error creating the contact info.');
                    }
                })
                .catch(error => {
                    Swal.close();
                    notify('error', 'An unexpected error occurred.');
                });
        });

        // Add error message spans for all relevant inputs
        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) { // Exclude Select2
                var errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.appendChild(errorSpan);
            }
        });

        document.getElementById('title').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });

        document.getElementById('link').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('button_title').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });

        document.getElementById('button_link').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('image').addEventListener('change', function() {
            $('#uploadImageBtn').next('.error-message').remove();
        });

        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ url('api/header/' . $header->id . '/image') }}',
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
