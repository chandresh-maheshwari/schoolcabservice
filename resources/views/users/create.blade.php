{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    {{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="user-listing-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Add User</h2>
        </div> --}}
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Create User</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <Add class="user-listing-header">Add User</h4>
            </div>
            <div class="card-body">
                <form id="userForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="first_name" style="font-weight: bold;">First Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name" style="font-weight: bold;">Last Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="mobile" style="font-weight: bold;">Mobile Number <span
                                style="color: red;">*</span></label>
                        <input type="number" class="form-control" id="mobile" name="mobile" minlength='10'
                            maxlength='12' required>
                    </div>
                    <div class="form-group">
                        <label for="email" style="font-weight: bold;">Email <span style="color: red;">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password" style="font-weight: bold;">Password <span style="color: red;">*</span> <small
                                style="font-size: 88%; color: #888;">(8-15 characters, include at least one number and one
                                special character)</small></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required
                                autocomplete="new-password">
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePassword('password')">
                                    <i class="fa fa-eye" id="togglePasswordIcon"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" style="font-weight: bold;">Confirm Password <span
                                style="color: red;">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required autocomplete="new-password">
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePassword('confirm_password')">
                                    <i class="fa fa-eye" id="toggleConfirmPasswordIcon"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="form-group">
                    <label for="photo" style="font-weight: bold;">Profile Picture : </label>
                    <div class="mt-2">
                    <input type="file" class="form-control-file" id="photo" name="photo" style="display: none;">
                    <div style="display: flex; align-items: center;">
                        <label for="photo" class="btn btn-primary" style="cursor: pointer; background-color: #2d336b; color: white; margin-right: 10px;">Upload Profile Picture</label>
                        <span id="file-name" style="color: red;"></span>
                    </div>
                    <img id="image-preview" src="#" alt="Image Preview" style="display: none; margin-top: 10px; max-width: 100px;">
                    </div>
                </div> --}}
                    <div class="form-group">
                        <label for="image" style="font-weight: bold;">Profile Image <span
                                style="color: red;">*</span></label>
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Profile Picture</button>
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <span id="imageName"></span>
                        </div>
                        <!-- Make sure to add id="dlt_btn_div" for reference -->
                        <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                            <img id="imagePreview" src="#" alt="Image Preview"
                                style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                            <button type="button" class="btn" style="display: none" id="removeImageBtn"><i
                                    class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="role" style="font-weight: bold;">Role <span style="color: red;">*</span></label>
                        <select class="form-control" id="role" name="role_id" required>
                            <option value="">Select a role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2d336b; color: white;">Submit</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // document.getElementById('photo').addEventListener('change', function() {
        //     var file = this.files[0];
        //     if (file) {
        //         var fileName = file.name;
        //         document.getElementById('file-name').textContent = fileName;
        //         document.getElementById('file-name').style.color = 'black';

        //         var reader = new FileReader();
        //         reader.onload = function(e) {
        //             var img = document.getElementById('image-preview');
        //             img.src = e.target.result;
        //             img.style.display = 'block';
        //         }
        //         reader.readAsDataURL(file);
        //     }
        // });

        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('userForm'));

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            // Validate form
            var isValid = true;
            if (!formData.get('first_name')) {
                document.getElementById('first_name').nextElementSibling.textContent = 'First Name is required.';
                isValid = false;
            }
            if (!formData.get('last_name')) {
                document.getElementById('last_name').nextElementSibling.textContent = 'Last Name is required.';
                isValid = false;
            }
            if (!formData.get('mobile')) {
                document.getElementById('mobile').nextElementSibling.textContent =
                    'Phone Number 1 is required.';
                isValid = false;
            } else {
                // Regular expression to check for 10-12 digits only
                const phoneRegex = /^\d{10,12}$/;

                if (!phoneRegex.test(formData.get('mobile'))) {
                    document.getElementById('mobile').nextElementSibling.textContent =
                        'Phone Number must contain only digits and be between 10 and 12 characters long.';
                    isValid = false;
                }
                // If validation passes, clear any previous error message
                else {
                    document.getElementById('mobile').nextElementSibling.textContent = '';
                }
            }
            if (!formData.get('email')) {
                document.getElementById('email').nextElementSibling.textContent = 'Email is required.';
                isValid = false;
            }
            if (!formData.get('password')) {
                document.getElementById('password').parentNode.nextElementSibling.textContent =
                    'Password is required.';
                isValid = false;
            }
            if (!formData.get('confirm_password')) {
                document.getElementById('confirm_password').parentNode.nextElementSibling.textContent =
                    'Confirm Password is required.';
                isValid = false;
            }
            if (formData.get('password') !== formData.get('confirm_password')) {
                document.getElementById('confirm_password').parentNode.nextElementSibling.textContent =
                    'Passwords do not match.';
                isValid = false;
            }
            if (!formData.get('role_id')) {
                $('#role').parent().find('.error-message').remove();
                $('#role').parent().append(
                    '<span class="error-message" style="color: red;">Role is required.</span>');
                isValid = false;
            }
            if (!formData.get('image') || !formData.get('image').name) {
                $('#uploadImageBtn').after(
                    '<span class="error-message" style="color: red;">Image is required.</span>');
                isValid = false;
            }
            // if (!formData.get('photo').name) {
            //     document.getElementById('file-name').textContent = 'Profile Picture is required.';
            //     isValid = false;
            // }

            if (!isValid) {
                return;
            }

            Swal.fire({
                title: 'Loading...',
                text: 'Please wait while we process your request',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('{{ route('api.register') }}', {
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
                        notify('success', 'User registered Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('users.index') }}';
                        }, 1500);
                    } else {
                        Swal.close();
                        let errorMessages = '';
                        if (data.errors) {
                            errorMessages = Object.values(data.errors).flat().join('\n');
                        } else {
                            errorMessages = 'There was an error registering the user.';
                        }
                        notify('error', errorMessages);
                    }
                })
                .catch(error => {
                    Swal.close();
                    notify('error', 'An unexpected error occurred.');
                });
        });

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Add error message spans for regular inputs
        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) { // Exclude Select2
                var errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.appendChild(errorSpan);
            }
        });

        document.getElementById('first_name').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        document.getElementById('password').addEventListener('input', function() {
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

        // Add error message spans for password fields
        document.querySelectorAll('.input-group').forEach(function(group) {
            var errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.style.color = 'red';
            group.parentNode.appendChild(errorSpan);
        });

      
        document.getElementById('confirm_password').addEventListener('input', function() {
            this.parentNode.parentNode.querySelector('.error-message').textContent = '';
        });

        document.getElementById('image').addEventListener('change', function() {
            $('#uploadImageBtn').next('.error-message').remove();
        });

        $('#role').on('change', function() {
            $(this).parent().find('.error-message').remove();
        });

        $(document).ready(function() {
            $('#role').select2({
                placeholder: "Select a Role",
                allowClear: true
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
    </script>
@endsection
