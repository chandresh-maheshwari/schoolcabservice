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
                <style>
                    #userForm .password-input-group {
                        position: relative;
                    }

                    #userForm .password-input-group .form-control {
                        padding-right: 42px;
                    }

                    #userForm .password-input-group .input-group-append {
                        position: absolute;
                        right: 14px;
                        top: 50%;
                        transform: translateY(-50%);
                        display: flex;
                        align-items: center;
                        z-index: 3;
                    }

                    #userForm .password-input-group .input-group-text {
                        border: 0;
                        background: transparent;
                        padding: 0;
                        min-height: auto;
                        color: #2d336b;
                        cursor: pointer;
                    }
                </style>
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
                        <label for="mobile" style="font-weight: bold;">
                            Mobile Number <span style="color: red;">*</span>
                        </label>
                        <input type="text" class="form-control" id="mobile" name="mobile" minlength="10"
                            maxlength="11" required>
                        <small class="error-message" style="color:#ff0000 !important;"></small>
                    </div>

                    <div class="form-group">
                        <label for="email" style="font-weight: bold;">Email <span style="color: red;">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password" style="font-weight: bold;">Password <span style="color: red;">*</span> <small
                                style="font-size: 88%; color: #888;">(8-15 characters, include at least one number and one
                                special character)</small></label>
                        <div class="input-group password-input-group">
                            <input type="password" class="form-control" id="password" name="password" required
                                autocomplete="new-password">
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePassword('password')">
                                    <i class="fa fa-eye" id="togglePasswordIcon"></i>
                                </span>
                            </div>
                        </div>
                        <span class="error-message password-error" style="color:red;"></span>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" style="font-weight: bold;">Confirm Password <span
                                style="color: red;">*</span></label>
                        <div class="input-group password-input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required autocomplete="new-password">
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePassword('confirm_password')">
                                    <i class="fa fa-eye" id="toggleConfirmPasswordIcon"></i>
                                </span>
                            </div>
                        </div>
                        <span class="error-message confirm-password-error" style="color:red;"></span>
                    </div>
                    <div class="form-group">
                        <label for="photo" style="font-weight: bold;">Profile Image</label>
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('photo').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Profile Picture</button>
                            <input type="file" class="form-control-file" id="photo" name="photo"
                                accept="image/*" style="display: none;" onchange="previewImage(event)">
                            <span id="imageName"></span>
                        </div>
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
        let isSubmittingUserForm = false;

        document.getElementById('submitBtn').addEventListener('click', function() {
            if (isSubmittingUserForm) {
                return;
            }

            var formData = new FormData(document.getElementById('userForm'));
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

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

                document.getElementById('mobile')
                    .nextElementSibling.textContent = 'Phone Number is required.';
                isValid = false;

            } else {
                const phoneRegex = /^\d{10,11}$/;
                if (!phoneRegex.test(formData.get('mobile'))) {
                    document.getElementById('mobile')
                        .nextElementSibling.textContent =
                        'Phone Number must be 10 or 11 digits only.';
                    isValid = false;
                } else {
                    document.getElementById('mobile')
                        .nextElementSibling.textContent = '';
                }
            }
            if (!formData.get('email')) {
                document.getElementById('email').nextElementSibling.textContent = 'Email is required.';
                isValid = false;
            }
            if (!formData.get('password')) {
                document.querySelector('.password-error').textContent =
                    'Password is required.';
                isValid = false;
            } else {
                const passwordRegex = /^(?=.*[0-9])(?=.*[\W_]).{8,15}$/;
                if (!passwordRegex.test(formData.get('password'))) {
                    document.querySelector('.password-error').textContent =
                        'Password must be 8-15 characters and include at least one number and one special character.';
                    isValid = false;
                } else {
                    document.querySelector('.password-error').textContent = '';
                }
            }
            if (!formData.get('confirm_password')) {
                document.querySelector('.confirm-password-error').textContent =
                    'Confirm Password is required.';
                isValid = false;
            } else if (formData.get('password') !== formData.get('confirm_password')) {
                document.querySelector('.confirm-password-error').textContent =
                    'Passwords do not match.';
                isValid = false;
            } else {
                document.querySelector('.confirm-password-error').textContent = '';
            }
            if (!formData.get('role_id')) {
                $('#role').parent().find('.error-message').remove();
                $('#role').parent().append(
                    '<span class="error-message" style="color: red;">Role is required.</span>');
                isValid = false;
            }
            if (!isValid) {
                return;
            }

            isSubmittingUserForm = true;
            document.getElementById('submitBtn').disabled = true;

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
                        notify('success', data.message || 'User registered Successfully!');
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
                    isSubmittingUserForm = false;
                    document.getElementById('submitBtn').disabled = false;
                })
                .catch(error => {
                    Swal.close();
                    isSubmittingUserForm = false;
                    document.getElementById('submitBtn').disabled = false;
                    notify('error', 'An unexpected error occurred.');
                });
        });

        window.togglePassword = function(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.closest('.password-input-group').querySelector('i');
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        };

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
            document.querySelector('.password-error').textContent = '';
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

        document.querySelectorAll('.input-group').forEach(function(group) {
            var errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.style.color = 'red';
            group.parentNode.appendChild(errorSpan);
        });


        document.getElementById('confirm_password').addEventListener('input', function() {
            document.querySelector('.confirm-password-error').textContent = '';
        });

        document.getElementById('photo').addEventListener('change', function() {
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
                imageInputSelector: '#photo',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });
    </script>
@endsection
