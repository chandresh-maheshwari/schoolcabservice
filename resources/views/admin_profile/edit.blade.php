{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
@php
    $authUser = Auth::user();
    $schoolSlug = $currentSchoolSlug ?? request()->route('schoolSlug');
    $isSchoolUser = $authUser && method_exists($authUser, 'isSchool') && $authUser->isSchool() && $schoolSlug;
    $dashboardUrl = $isSchoolUser
        ? route('school.dashboard', ['schoolSlug' => $schoolSlug])
        : route('admin_layout.index');
    $profileIndexUrl = $isSchoolUser
        ? route('school.profile', ['schoolSlug' => $schoolSlug])
        : route('admin.profile');
@endphp
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
                <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ $dashboardUrl }}">Dashboard</a></li> 
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ $profileIndexUrl }}">Profile</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Profile</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <Add class="user-listing-header">Edit Profile</h4>
                    </div>
                    <div class="form-group text-center mb-30">
                        @php
                            $photoPath = ltrim((string) ($user->photo ?? ''), '/');
                            if ($photoPath !== '' && !\Illuminate\Support\Str::startsWith($photoPath, 'storage/')) {
                                $photoPath = 'storage/' . $photoPath;
                            }
                        @endphp
                        <img id="imagePreview" src="{{ $photoPath !== '' ? asset($photoPath) : asset('images/default-user-avatar.svg') }}" alt="Image Preview" class="rounded-circle" style="width: 100px; height: 100px; display: block; margin: 1% auto;" onerror="this.onerror=null;this.src='{{ asset('images/default-user-avatar.svg') }}';">
                        <input type="file" class="custom-file-input" id="photo" name="photo" accept="image/*" onchange="previewImage(event)" style="display: none;">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('photo').click();">Update Profile Picture</button>
                    </div>
        <div class="card-body">
            <form id="updateProfileForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('POST')
                <div class="form-group">
                    <label for="first_name" style="font-weight: bold;">First Name <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="{{ $user->first_name }}" required>
                </div>
                <div class="form-group">
                    <label for="last_name" style="font-weight: bold;">Last Name <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="{{ $user->last_name }}" required>
                </div>
                <div class="form-group">
                    <label for="mobile" style="font-weight: bold;">Mobile Number <span style="color: red;">*</span></label>
                    <input type="number" class="form-control" id="mobile" name="mobile" value="{{ $user->mobile }}" required>
                </div>
                <div class="form-group">
                    <label for="email" style="font-weight: bold;">Email <span style="color: red;">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
                </div>
                <div class="form-group">
                    <label for="role" style="font-weight: bold;">Role <span style="color: red;">*</span></label>
                    <select class="form-control" id="role" name="role_id" required>
                        <option value="">Select a role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
              
                <button type="submit" class="btn btn-primary" style="background-color: #2C9DD4;" id="submitBtn">Update</button>
                <a href="{{ $profileIndexUrl }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
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

        $('#role').on('change', function() {
            $(this).parent().find('.error-message').text('');
        });

        // Add invalid event listeners to show custom messages
        document.querySelectorAll('input[required], select[required]').forEach(function(element) {
            element.addEventListener('invalid', function(e) {
                e.preventDefault();
                let errorMessage = '';
                if (element.validity.valueMissing) {
                    switch(element.id) {
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
                    element.parentNode.querySelector('.error-message').textContent = errorMessage;
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
                document.getElementById('first_name').parentNode.querySelector('.error-message').textContent = 'First Name is required.';
                isValid = false;
            }
            if (!formData.get('last_name')) {
                document.getElementById('last_name').parentNode.querySelector('.error-message').textContent = 'Last Name is required.';
                isValid = false;
            }
            if (!formData.get('mobile')) {
                document.getElementById('mobile').parentNode.querySelector('.error-message').textContent = 'Mobile Number is required.';
                isValid = false;
            }
            if (!formData.get('email')) {
                document.getElementById('email').parentNode.querySelector('.error-message').textContent = 'Email is required.';
                isValid = false;
            }
            if (!formData.get('role_id')) {
                $('#role').parent().find('.error-message').text('Role is required.');
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            // Check if the file is included in the FormData
            if ($('#photo')[0].files.length > 0) {
                formData.append('photo', $('#photo')[0].files[0]);
            }

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '/api/profile/' + {{ $user->id }},
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        Swal.close();
                        notify('success', 'Profile updated Successfully!');
                        setTimeout(function() {
                            window.location.href = @json($profileIndexUrl);
                        }, 1500);
                    } else {
                        Swal.close();
                        notify('error', response.error);
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    let errorMessage = 'An unexpected error occurred.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
                    }
                    notify('error', errorMessage);
                }
            });
        });
    });

    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function() {
            var output = document.getElementById('imagePreview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    $(document).ready(function() {
        $('#role').select2({
            placeholder: "Select a Role",
            allowClear: true
        });
    });
</script>
@endsection
