{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

    @php
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $schoolSlug = request()->route('schoolSlug');
        $isSchoolPanel = filled($schoolSlug) && is_string($routeName) && str_starts_with($routeName, 'school.');

        $childCreateUrl = $isSchoolPanel
            ? route('school.child.create', ['schoolSlug' => $schoolSlug])
            : route('child.create');

        $childEditUrlTemplate = $isSchoolPanel
            ? route('school.child.edit', ['schoolSlug' => $schoolSlug, 'child' => '__CHILD__'])
            : route('child.edit', ['child' => '__CHILD__']);
    @endphp

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a
                                class="breadcrumbLink"href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add Parent
                            Details</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @include('child.partials.module_tabs', [
        'activeTab' => 'parent',
        'entityIds' => [],
    ])

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Parent Details</h4>
            </div>
            <div class="card-body">
                <form id="parentForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="father_name" style="font-weight: bold;">Father Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="father_name" name="father_name">
                    </div>
                    <div class="form-group">
                        <label for="mother_name" style="font-weight: bold;">Mother Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="mother_name" name="mother_name">
                    </div>
                    <div class="form-group">
                        <label for="contact_number" style="font-weight: bold;">
                            Contact Number <span style="color:red;">*</span>
                        </label>
                        <input type="tel" class="form-control" id="contact_number" name="contact_number"
                            placeholder="Enter 10 or 11 digit number" minlength="10" maxlength="11" pattern="[0-9]{10,11}"
                            required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="alternative_contact_number" style="font-weight: bold;">
                            Alternative Contact Number <span style="color:red;">*</span>
                        </label>
                        <input type="tel" class="form-control" id="alternative_contact_number"
                            name="alternative_contact_number" placeholder="Enter 10 or 11 digit number" minlength="10"
                            maxlength="11" pattern="[0-9]{10,11}" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="email" style="font-weight: bold;">Email <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="login_username" style="font-weight: bold;">Login Username <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="login_username" name="login_username">
                    </div>
                    <div class="form-group">
                        <label for="password" style="font-weight: bold;">Password <span style="color: red;">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation" style="font-weight: bold;">Confirm Password <span style="color: red;">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="address_1" style="font-weight: bold;">Address 1 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="address_1" name="address_1">
                    </div>
                    <div class="form-group">
                        <label for="address_2" style="font-weight: bold;">Address 2 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="address_2" name="address_2">
                    </div>
                    <div class="form-group">
                        <label for="state" style="font-weight: bold;">
                            State <span style="color: red;">*</span>
                        </label>
                        <select class="form-control" id="state" name="state" required>
                            <option value="">Select State</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->name }}">
                                    {{ $state->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="city" style="font-weight: bold;">
                            City <span style="color: red;">*</span>
                        </label>
                        <select class="form-control" id="city" name="city" required>
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pincode" style="font-weight: bold;">Pincode <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="pincode" name="pincode">
                    </div>
                    <div class="form-group">
                        <label>Father Aadhar Card Image <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 636 × 424 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="fatherAdherImageBtn"
                            onclick="document.getElementById('father_adhaar_card_image').click();">Upload Image</button>
                        <input type="file" id="father_adhaar_card_image" name="father_adhaar_card_image"
                            accept="image/*" style="display:none;" onchange="previewImage(event)">
                        <span id="imageName"></span>

                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn"><i
                                class="fas fa-trash"></i></button>
                    </div>
                    <div class="form-group">
                        <label>Mother Aadhar Card Image <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="motherAdherImageBtn"
                            onclick="document.getElementById('mother_adhaar_card_image').click();">Upload Image</button>
                        <input type="file" id="mother_adhaar_card_image" name="mother_adhaar_card_image"
                            accept="image/*" style="display:none;" onchange="previewImage1(event)">
                        <span id="imageName1"></span>

                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview1" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn1"><i
                                class="fas fa-trash"></i></button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" id="submitBtn"
                            style="background-color: #2C9DD4; color: white;">Submit</button>
                        <a href="{{ route('parent.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Include common icon picker JS -->
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        /* ===============================
                           STATE → CITY DROPDOWN (API)
                        ================================ */
        $(document).ready(function() {

            $('#state').on('change', function() {
                let state = $(this).val();
                $('#city').html('<option>Loading...</option>');
                if (!state) {
                    $('#city').html('<option value="">Select City</option>');
                    return;
                }

                $.ajax({
                    url: "{{ route('api.parent.getCities') }}",
                    type: "POST",
                    timeout: 15000,
                    data: {
                        state: state,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        let cities = [];
                        if (Array.isArray(response)) {
                            cities = response;
                        } else if (response && Array.isArray(response.cities)) {
                            cities = response.cities;
                        } else if (response && Array.isArray(response.data)) {
                            cities = response.data;
                        }

                        $('#city').empty().append('<option value="">Select City</option>');
                        cities.forEach(city => {
                            $('#city').append(
                                `<option value="${city}">${city}</option>`
                            );
                        });

                        if (!cities.length) {
                            $('#city').html('<option value="">No cities found</option>');
                        }
                    },
                    error: function(xhr, status) {
                        console.error('City load failed:', status, xhr && xhr.responseText ? xhr.responseText : '');
                        $('#city').html('<option value="">Error loading cities</option>');
                    }
                });
            });

        });

        /* ===============================
           FORM SUBMIT (YOUR EXISTING CODE)
        ================================ */
        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('parentForm'));

            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            let isValid = true;
            if (!formData.get('father_name')) {
                document.getElementById('father_name')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Father Name is required.';
                isValid = false;
            }
            if (!formData.get('mother_name')) {
                document.getElementById('mother_name')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Mother Name is required.';
                isValid = false;
            }
            if (!formData.get('contact_number')) {
                document.getElementById('contact_number')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Contact Number is required.';
                isValid = false;
            }
            if (!formData.get('alternative_contact_number')) {
                document.getElementById('alternative_contact_number')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'AlterNative Contact Number is required.';
                isValid = false;
            }
            if (!formData.get('email')) {
                document.getElementById('email')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Email is required.';
                isValid = false;
            }
            if (!formData.get('login_username')) {
                document.getElementById('login_username')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Login Username is required.';
                isValid = false;
            }
            if (!formData.get('password')) {
                document.getElementById('password')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Password is required.';
                isValid = false;
            }
            if (!formData.get('password_confirmation')) {
                document.getElementById('password_confirmation')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Confirm Password is required.';
                isValid = false;
            }
            if (formData.get('password') && formData.get('password_confirmation') && formData.get('password') !== formData.get('password_confirmation')) {
                document.getElementById('password_confirmation')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Password and Confirm Password must match.';
                isValid = false;
            }
            if (!formData.get('address_1')) {
                document.getElementById('address_1')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Address 1 is required.';
                isValid = false;
            }
            if (!formData.get('address_2')) {
                document.getElementById('address_2')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Address 2 is required.';
                isValid = false;
            }
            if (!formData.get('state')) {
                document.getElementById('state')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'State is required.';
                isValid = false;
            }
            if (!formData.get('city')) {
                document.getElementById('city')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'City is required.';
                isValid = false;
            }
            if (!formData.get('pincode')) {
                document.getElementById('pincode')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Pincode is required.';
                isValid = false;
            }


            function enforcePhoneDigits(el) {
                el.value = el.value.replace(/\D/g, '').slice(0, 11);
            }

            document.getElementById('contact_number')
                .addEventListener('input', function() {
                    enforcePhoneDigits(this);
                });

            document.getElementById('alternative_contact_number')
                .addEventListener('input', function() {
                    enforcePhoneDigits(this);
                });

            var imageInput = document.getElementById('father_adhaar_card_image');
            var imagePreview = document.getElementById('imagePreview');
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#fatherAdherImageBtn').after(
                    '<span class="error-message" style="color: red;">Father Adhaar Card Image is required.</span>'
                );
                isValid = false;
            }
            var imageInput1 = document.getElementById('mother_adhaar_card_image');
            var imagePreview1 = document.getElementById('imagePreview1');
            var imageError1 = document.getElementById('imageError');
            var currentImageSrc1 = imagePreview1.getAttribute('src');
            var isDefaultImage1 = currentImageSrc1.includes('Default.jpg');
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput1.files.length && isDefaultImage1 || (currentImageSrc1 == "#" || currentImageSrc1 ==
                    "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#motherAdherImageBtn').after(
                    '<span class="error-message" style="color: red;">Mother Adhaar Card Image is required.</span>'
                );
                isValid = false;
            }
            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.parent.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })
                .then(async res => {

                    let data = null;

                    // 🔹 Safely parse JSON
                    try {
                        data = await res.json();
                    } catch (e) {
                        // JSON parse fail (HTML / server error)
                        throw 'Invalid server response';
                    }

                    // 🔹 If backend says error OR HTTP error
                    if (!res.ok || data.success === false) {

                        let errorMsg = data.message || 'Something went wrong';

                        // Laravel validation errors support
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join('<br>');
                        }

                        throw errorMsg; // 👈 REAL MESSAGE THROW
                    }

                    return data;
                })
                .then(data => {
                    Swal.close();

                    notify('success', 'Parent created Successfully!');

                    // If this parent was created as part of the Child module flow, keep context for Child form.
                    if (data && data.id) {
                        try {
                            sessionStorage.setItem('childModule.parent_id', String(data.id));
                        } catch (e) {}
                    }

                    const params = new URLSearchParams(window.location.search);
                    const childIdFromQuery = params.get('child_id') || '';
                    const childIdFromStorage = (function () {
                        try { return sessionStorage.getItem('childModule.child_id') || ''; } catch (e) { return ''; }
                    })();
                    const childId = childIdFromQuery || childIdFromStorage;

                    // If we are in "Child -> Parents" flow, link parent to child, then open Child edit.
                    if (childId && data && data.id) {
                        fetch('/api/child/' + encodeURIComponent(childId) + '/set-parent', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ parent_id: data.id })
                            })
                            .then(res => res.json())
                            .then(linkRes => {
                                if (!linkRes || !linkRes.success) {
                                    throw (linkRes && linkRes.message) ? linkRes.message : 'Failed to link parent to child';
                                }

                                const editUrl = @json($childEditUrlTemplate).replace('__CHILD__', encodeURIComponent(childId));
                                if (typeof window.__childModuleLoadPage === 'function') {
                                    window.__childModuleLoadPage(editUrl);
                                } else {
                                    window.location.href = editUrl;
                                }
                            })
                            .catch(err => {
                                notify('error', typeof err === 'string' ? err : (err.message || 'Link failed'));
                                // Fallback to Child create.
                                const fallbackUrl = @json($childCreateUrl);
                                if (typeof window.__childModuleLoadPage === 'function') {
                                    window.__childModuleLoadPage(fallbackUrl);
                                } else {
                                    window.location.href = fallbackUrl;
                                }
                            });
                        return;
                    }

                    // Default: back to Child create (no child context).
                    const fallbackUrl = @json($childCreateUrl);
                    setTimeout(() => {
                        if (typeof window.__childModuleLoadPage === 'function') {
                            window.__childModuleLoadPage(fallbackUrl);
                        } else {
                            window.location.href = fallbackUrl;
                        }
                    }, 400);
                })
                .catch(error => {
                    Swal.close();

                    // 🔥 EXACT MESSAGE (JS / BACKEND)
                    notify('error', typeof error === 'string' ? error : (error.message ||
                        'Something went wrong'));
                });

        });

        /* ===============================
           ERROR SPANS AUTO ADD
        ================================ */
        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) {
                let errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.appendChild(errorSpan);
            }
        });

        $('#contact_number,#alternative_contact_number,#pincode').on('input paste', function() {
            const value = $(this).val();
            if (value && !/^\d*\.?\d*$/.test(value)) {
                $(this).val(value.slice(0, -1));
            }
        });

        document.getElementById('father_adhaar_card_image').addEventListener('change', function() {
            $('#fatherAdherImageBtn').next('.error-message').remove();
        })

        document.getElementById('mother_adhaar_card_image').addEventListener('change', function() {
            $('#motherAdherImageBtn').next('.error-message').remove();
        });

        /* ===============================
           CLEAR ERROR ON INPUT
        ================================ */
        $('#father_name, #mother_name, #contact_number, #email, #login_username, #password, #password_confirmation, #state, #city, #pincode, #alternative_contact_number,#address_1,#address_2')
            .on(
                'change input',
                function() {
                    $(this).closest('.form-group').find('.error-message').text('');
                });

        document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#father_adhaar_card_image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });
        document.getElementById('removeImageBtn1').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview1',
                imageNameSelector: '#imageName1',
                imageInputSelector: '#mother_adhaar_card_image',
                removeImageBtnSelector: '#removeImageBtn1'
            });
        });
    </script>
@endsection
