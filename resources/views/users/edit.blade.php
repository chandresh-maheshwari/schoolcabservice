{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    {{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="color: white; text-align: center;">
            <input type="file" class="form-control-file" id="photo" name="photo" style="display: none;">
            <img id="current-image" src="{{ asset('storage/' . $user->photo) }}" alt="User Photo" class="rounded-circle" style="border: 3px solid #2d336b; width: 100px; height: 100px;">
            <div style="margin-top: 10px;">
                <button class="btn btn-primary" style="background-color: #2d336b; border: none;" onclick="document.getElementById('photo').click();">Update Profile Picture</button>
            </div> --}}
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('users.index') }}">Users</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit User</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <Add class="user-listing-header">Edit User</h4>
            </div>
            {{-- <div class="form-group mb-30">
                <img id="imagePreview"
                    src="{{ $user->photo ? asset('storage/' . $user->photo) : '/assets/images/person.jpg' }}"
                    alt="Image Preview" class="rounded-circle"
                    style="width: 100px; height: 100px; display: block;">
                <input type="file" class="custom-file-input" id="photo" name="photo" accept="image/*"
                    onchange="previewImage(event)" style="display: none;">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('photo').click();">Update
                    Profile Picture</button>
                <!-- <span id="imageName">No file chosen</span> -->
            </div> --}}
            {{-- <div class="form-group">
                <label for="image" style="font-weight: bold;">Image <span style="color: red;">*</span></label>
                <div class="mt-2">
                    <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                        style="display: none;" onchange="previewImage(event)">
                    <button type="button" class="btn btn-primary" id="uploadImageBtn"
                        onclick="document.getElementById('image').click();"
                        style="background-color: #2C9DD4; color: white;">Upload Image</button>
                    @php
                        $imagePath = $user->image ? public_path($user->image) : null;
                        $imageExists = $imagePath && File::exists($imagePath);
                        $imageUrl = $imageExists ? asset($user->image) : asset('images/Default.jpg');
                        $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                    @endphp
                    <span id="imageName">
                        {{ !$isDefaultImage && $imageExists ? basename($user->image) : 'No image' }}
                    </span>
                </div>
                <div id="dlt_btn_div" class="dlt_btn_div">
                    <img id="imagePreview" src="{{ $imageUrl }}" alt="Image Preview"
                        style="display: block; width: 100px; height: 100px; margin-top: 10px;">
                    <button type="button" id="removeImageBtn" class="btn btn-sm"
                        style="display: none; margin-top: 10px; margin-left: 10px;">
                        <i class="fas fa-trash"></i> </button>
                    @if (!$isDefaultImage)
                        <button type="button" id="deleteImageBtn" class="btn btn-sm"
                            style="margin-top: 10px; margin-left: 10px;">
                            <i class="fas fa-trash"></i> </button>
                    @endif
                </div>
            </div> --}}
            <div class="card-body">
                <form action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="image" style="font-weight: bold;">Image <span style="color: red;">*</span></label>
                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Profile Picture</button>
                            @php
                                $photoPath = ltrim((string) ($user->photo ?? ''), '/');
                                if ($photoPath !== '' && !\Illuminate\Support\Str::startsWith($photoPath, 'storage/')) {
                                    $photoPath = 'storage/' . $photoPath;
                                }
                                $imagePath = $photoPath !== '' ? public_path($photoPath) : null;
                                $imageExists = $imagePath && file_exists($imagePath);
                                $imageUrl = $imageExists ? asset($photoPath) : asset('images/Default.jpg');
                                $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                            @endphp
                            <span id="imageName">
                                {{ !$isDefaultImage && $imageExists ? basename($photoPath) : 'No image' }}
                            </span>
                        </div>
                        <div id="dlt_btn_div" class="dlt_btn_div">
                            <img id="imagePreview" src="{{ $imageUrl }}" alt="Image Preview"
                                style="display: block; width: 100px; height: 100px; margin-top: 10px;">
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
                        <label for="first_name" style="font-weight: bold;">First Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                            value="{{ $user->first_name }}" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name" style="font-weight: bold;">Last Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                            value="{{ $user->last_name }}" required>
                    </div>
                    <div class="form-group">
                        <label for="mobile" style="font-weight: bold;">
                            Mobile Number <span style="color: red;">*</span>
                        </label>
                        <input type="text" class="form-control" id="mobile" name="mobile"
                            value="{{ old('mobile', $user->mobile ?? '') }}" maxlength="11" required>

                        <small class="text-danger"></small>
                    </div>

                    <div class="form-group">
                        <label for="email" style="font-weight: bold;">Email <span style="color: red;">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="role" style="font-weight: bold;">Role <span style="color: red;">*</span></label>
                        <select class="form-control" id="role" name="role_id" required>
                            <option value="">Select a role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- <div class="form-group">
                                            <label for="photo" style="font-weight: bold;">Profile Picture</label>
                                            <div style="display: flex; align-items: center;">
                                                <div style="margin-right: 10px;">
                                                    @if ($user->photo)
    <img id="current-image" src="{{ asset('storage/' . $user->photo) }}" alt="User Photo" width="100">
    @endif
                                                    <img id="image-preview" src="#" alt="New Image Preview" style="display: none; max-width: 100px;">
                                                </div>
                                                <div>
                                                    <input type="file" class="form-control-file" id="photo" name="photo" style="display: none;">
                                                    <label for="photo" class="btn btn-primary" style="cursor: pointer; background-color: #2d336b; color: white;">Select New Profile Picture</label>
                                                </div>
                                            </div>
                                        </div> -->
                    <button type="submit" class="btn btn-primary" style="background-color: #2C9DD4;"
                        id="submitBtn">Update</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Add error message spans for regular inputs
            document.querySelectorAll('.form-control').forEach(function(input) {
                if (!input.classList.contains('select2-hidden-accessible')) { // Exclude Select2
                    var errorSpan = document.createElement('span');
                    errorSpan.className = 'error-message';
                    errorSpan.style.color = 'red';
                    input.parentNode.appendChild(errorSpan);
                }
            });

            // Add error message spans for select fields
            $('#role').parent().append('<span class="error-message" style="color: red;"></span>');

            // Input event listeners to clear error messages
            document.getElementById('first_name').addEventListener('input', function() {
                this.parentNode.querySelector('.error-message').textContent = '';
            });

            document.getElementById('last_name').addEventListener('input', function() {
                this.parentNode.querySelector('.error-message').textContent = '';
            });

            document.getElementById('mobile').addEventListener('input', function() {
                this.parentNode.querySelector('.error-message').textContent = '';
            });

            document.getElementById('email').addEventListener('input', function() {
                this.parentNode.querySelector('.error-message').textContent = '';
            });

            document.getElementById('image').addEventListener('change', function() {
                $('#uploadImageBtn').next('.error-message').remove();
            });

            document.getElementById('image').addEventListener('change', function(event) {
                previewImage(event, 'imagePreview', 'imageName');
            });

            $('#role').on('change', function() {
                $(this).parent().find('.error-message').text('');
            });

            // Add invalid event listeners to show custom messages
            document.querySelectorAll('input[required], select[required]').forEach(function(element) {
                element.addEventListener('invalid', function(e) {
                    e.preventDefault();
                    let errorMessage = '';
                    if (element.validity.valueMissing) {
                        switch (element.id) {
                            case 'first_name':
                                errorMessage = 'First Name is required.';
                                break;
                            case 'last_name':
                                errorMessage = 'Last Name is required.';
                                break;
                            case 'mobile':
                                errorMessage = 'Mobile Number is required.';
                                break;
                            case 'email':
                                errorMessage = 'Email is required.';
                                break;
                            case 'role':
                                errorMessage = 'Role is required.';
                                break;
                        }
                        element.parentNode.querySelector('.error-message').textContent =
                            errorMessage;
                    }
                });
            });

            $('form').on('submit', function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let isValid = true;

                // Clear previous error messages
                document.querySelectorAll('.error-message').forEach(function(el) {
                    el.textContent = '';
                });

                // Validate form fields
                if (!formData.get('first_name')) {
                    document.getElementById('first_name').parentNode.querySelector('.error-message')
                        .textContent = 'First Name is required.';
                    isValid = false;
                }
                if (!formData.get('last_name')) {
                    document.getElementById('last_name').parentNode.querySelector('.error-message')
                        .textContent = 'Last Name is required.';
                    isValid = false;
                }
                // if (!formData.get('mobile')) {
                //     document.getElementById('mobile').parentNode.querySelector('.error-message')
                //         .textContent = 'Mobile Number is required.';
                //     isValid = false;
                // }
                const mobileInput = document.getElementById('mobile');
                const mobileValue = formData.get('mobile');

                if (!mobileValue || mobileValue.trim() === '') {

                    mobileInput.nextElementSibling.textContent =
                        'Phone Number is required.';
                    isValid = false;

                } else {

                    // only 10 or 11 digits allowed
                    const phoneRegex = /^\d{10,11}$/;

                    if (!phoneRegex.test(mobileValue)) {

                        mobileInput.nextElementSibling.textContent =
                            'Phone Number must be 10 or 11 digits only.';
                        isValid = false;

                    } else {
                        mobileInput.nextElementSibling.textContent = '';
                    }
                }
                if (!formData.get('email')) {
                    document.getElementById('email').parentNode.querySelector('.error-message')
                        .textContent = 'Email is required.';
                    isValid = false;
                }
                if (!formData.get('role_id')) {
                    $('#role').parent().find('.error-message').text('Role is required.');
                    isValid = false;
                }

                var imageInput = document.getElementById('image');
                var imagePreview = document.getElementById('imagePreview');
                var imageError = document.getElementById('imageError');
                var currentImageSrc = imagePreview.getAttribute('src');
                var isDefaultImage = currentImageSrc.includes('Default.jpg');
                // console.log(!imageInput.files.length && isDefaultImage);
                if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" ||
                        currentImageSrc == "")) {
                    // if (!imageInput.files.length && isDefaultImage) {
                    // if (!formData.get('image') || !formData.get('image').name) {
                    $('#uploadImageBtn').after(
                        '<span class="error-message" style="color: red;">Image is required.</span>');
                    isValid = false;
                }

                if (!isValid) {
                    return;
                }

                // Check if the file is included in the FormData
                // if ($('#photo')[0].files.length > 0) {
                //     formData.append('photo', $('#photo')[0].files[0]);
                // }

                Swal.fire({
                    title: 'Please wait...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '{{ route('api.users.update', $user->id) }}',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            Swal.close();
                            notify('success', 'User updated Successfully!');
                            setTimeout(function() {
                                window.location.href = '{{ route('users.index') }}';
                            }, 1500);
                        } else {
                            Swal.close();
                            notify('error', 'There was an error updating the user.');
                        }
                    },
                    error: function() {
                        Swal.close();
                        notify('error', 'An unexpected error occurred.');
                    }
                });
            });
        });


        // function previewImage(event) {
        //     var reader = new FileReader();
        //     reader.onload = function() {
        //         var output = document.getElementById('imagePreview');
        //         output.src = reader.result;
        //     };
        //     reader.readAsDataURL(event.target.files[0]);
        // }

        $(document).ready(function() {
            $('#role').select2({
                placeholder: "Select a Role",
                allowClear: true
            });
        });
        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.users.deleteImage', $user->id) }}',
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
