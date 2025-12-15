{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    {{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="user-listing-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Update Contact </h2>
        </div> --}}
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Update Contact </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="user-listing-header">Update Contact </h4>
            </div>
            <div class="card-body">
                <form id="contactForm">
                    @csrf

                    {{-- <div class="form-group">
                    <label for="address" style="font-weight: bold;">Address <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="address" name="address" required>
                    <div id="address-error" style="color: red; display: none;">Please enter an address.</div>
                </div> --}}
                    <div class="form-group">
                        <label for="title" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ $contact->title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ $contact->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="location_title" style="font-weight: bold;">Location Title <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="location_title" name="location_title"
                            value="{{ $contact->location_title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="location" style="font-weight: bold;">Location <span style="color: red;">*</span></label>
                        <textarea class="form-control" id="location" name="location" rows="4" required>{{ $contact->location }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="contact_title" style="font-weight: bold;">Contact Title <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="contact_title" name="contact_title"
                            value="{{ $contact->contact_title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_1" style="font-weight: bold;"> Contact 1 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="contact_1" name="contact_1" minlength='10'
                            maxlength='12' value="{{ $contact->contact_1 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_2" style="font-weight: bold;">Contact 2 </label>
                        <input type="text" class="form-control" id="contact_2" name="contact_2" minlength='10'
                            maxlength='12' value="{{ $contact->contact_2 }}">
                    </div>

                    {{-- <div class="form-group">
                        <label for="contact_1" style="font-weight: bold;">Phone Number 1 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="contact_1" name="contact_1" required>
                        <div id="phone-required-error_1" style="color: red; display: none;">Mobile number is required.</div>
                        <div id="phone-format-error_1" style="color: red; display: none;">Please enter a valid mobile
                            number.
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="contact_2" style="font-weight: bold;">Phone Number 2 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="contact_2" name="contact_2" required>
                        <div id="phone-required-error_2" style="color: red; display: none;">Mobile number is required.</div>
                        <div id="phone-format-error_2" style="color: red; display: none;">Please enter a valid mobile
                            number.
                        </div>
                    </div> --}}
                    <div class="form-group">
                        <label for="email_title" style="font-weight: bold;">Email Title <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="email_title" name="email_title"
                            value="{{ $contact->email_title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="email_1" style="font-weight: bold;">Email 1 <span style="color: red;">*</span></label>
                        <input type="email" class="form-control" id="email_1" name="email_1"
                            value="{{ $contact->email_1 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="email_2" style="font-weight: bold;">Email 2 </label>
                        <input type="email" class="form-control" id="email_2" name="email_2"
                            value="{{ $contact->email_2 }}">
                    </div>

                    <div class="form-group">
                        <label for="contact_form_title" style="font-weight: bold;">Conatct Form Title <span
                                style="color: red;">*</span>
                        </label>
                        <input type="text" class="form-control" id="contact_form_title" name="contact_form_title"
                         value="{{ $contact->contact_form_title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_form_description" style="font-weight: bold;">Contact Form Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="contact_form_description" name="contact_form_description" rows="4" required>{{ $contact->contact_form_description }}</textarea>
                    </div>
                    {{-- <div class="form-group">
                        <label for="email_1" style="font-weight: bold;">Email 1 <span style="color: red;">*</span></label>
                        <input type="email" class="form-control" id="email_1" name="email_1" required>
                        <div id="email-required-error_1" style="color: red; display: none;">Email 1 is required.</div>
                        <div id="email-format-error_1" style="color: red; display: none;">Please enter a valid email
                            address.
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email_2" style="font-weight: bold;">Email 2 <span
                                style="color: red;">*</span></label>
                        <input type="email" class="form-control" id="email_2" name="email_2" required>
                        <div id="email-required-error_1" style="color: red; display: none;">Email 1 is required.</div>
                        <div id="email-format-error_1" style="color: red; display: none;">Please enter a valid email
                            address.
                        </div>
                    </div> --}}

                    {{-- <div class="form-group">
                        <label for="status" style="font-weight: bold;">Status <span style="color: red;">*</span></label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="" disabled selected>Select Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div id="status-error" style="color: red; display: none;">Please select a status.</div>
                    </div> --}}
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('contacts.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        CKEDITOR.replace('description');
        CKEDITOR.replace('location');
        CKEDITOR.replace('contact_form_description');


        document.getElementById('submitBtn').addEventListener('click', function() {
            // var addressContent = document.getElementById('address').value.trim();
            // var mobileContent = document.getElementById('contact_1').value.trim();
            // var emailContent = document.getElementById('email').value.trim();
            // var statusContent = document.getElementById('status').value;
            var formData = new FormData(document.getElementById('contactForm'));
            formData.set('description', CKEDITOR.instances.description.getData());
            formData.set('location', CKEDITOR.instances.description.getData());
            formData.set('contact_form_description', CKEDITOR.instances.contact_form_description.getData());

            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });
            var isValid = true;

            // Validation for Title
            if (!formData.get('title')) {
                document.getElementById('title').nextElementSibling.textContent = 'Title is required.';
                isValid = false;
            }

            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }
            if (!formData.get('location_title')) {
                document.getElementById('location_title').nextElementSibling.textContent =
                    'Location Title is required.';
                isValid = false;
            }
            if (!CKEDITOR.instances.location.getData().trim()) {
                $('#location').next('.cke').after(
                    '<span class="error-message" style="color: red;">Location is required.</span>');
                isValid = false;
            }
            if (!formData.get('contact_title')) {
                document.getElementById('contact_title').nextElementSibling.textContent =
                    'Contact Title is required.';
                isValid = false;
            }
            if (!formData.get('contact_1')) {
                document.getElementById('contact_1').nextElementSibling.textContent =
                    'Phone Number 1 is required.';
                isValid = false;
            } else {
                // Regular expression to check for 10-12 digits only
                const phoneRegex = /^\d{10,12}$/;

                if (!phoneRegex.test(formData.get('contact_1'))) {
                    document.getElementById('contact_1').nextElementSibling.textContent =
                        'Phone Number must contain only digits and be between 10 and 12 characters long.';
                    isValid = false;
                }
                // If validation passes, clear any previous error message
                else {
                    document.getElementById('contact_1').nextElementSibling.textContent = '';
                }
            }
            if (formData.get('contact_2')) {
                // Regular expression to check for 10-12 digits only
                const phoneRegex = /^\d{10,12}$/;

                if (!phoneRegex.test(formData.get('contact_2'))) {
                    document.getElementById('contact_2').nextElementSibling.textContent =
                        'Phone Number must contain only digits and be between 10 and 12 characters long.';
                    isValid = false;
                }
                // If validation passes, clear any previous error message
                else {
                    document.getElementById('contact_2').nextElementSibling.textContent = '';
                }
            }
            if (!formData.get('email_title')) {
                document.getElementById('email_title').nextElementSibling.textContent =
                    'Email Title is required.';
                isValid = false;
            }
            // if (!formData.get('email_1')) {
            //     document.getElementById('email_1').nextElementSibling.textContent =
            //         'Email 1 Title is required.';
            //     isValid = false;
            // }
            var email = formData.get('email_1');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!email) {
                document.getElementById('email_1').nextElementSibling.textContent =
                    'Email 1 Title is required.';
                isValid = false;
            } else if (!emailRegex.test(email)) {
                document.getElementById('email_1').nextElementSibling.textContent =
                    'Please enter a valid email address.';
                isValid = false;
            } else {
                document.getElementById('email_1').nextElementSibling.textContent = '';
            }

            const emailValue = formData.get('email_2').trim();
            if (emailValue !== '' && !emailRegex.test(emailValue)) {
                document.getElementById('email_2').nextElementSibling.textContent =
                    'Please enter a valid email address.';
                isValid = false;
            } else {
                document.getElementById('email_2').nextElementSibling.textContent = '';
            }

            if (!formData.get('contact_form_title')) {
                document.getElementById('contact_form_title').nextElementSibling.textContent =
                    'Contact Title is required.';
                isValid = false;
            }
            if (!CKEDITOR.instances.contact_form_description.getData().trim()) {
                $('#contact_form_description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Contact Form Description is required.</span>'
                    );
                isValid = false;
            }
            // alert(isValid)
            if (!isValid) {
                return;
            }

            // var formData = new FormData(document.getElementById('contactForm'));

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            fetch('{{ route('api.contacts.update', $contact->id) }}', {
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
                        notify('success', 'Contacts Section Details updated Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('contacts.index') }}';
                        }, 1500);

                    } else {
                        notify('error', data.message ||
                            'There was an error updating the Contacts Section Detail page.');
                    }
                })
                .catch(error => {
                    Swal.close();
                    notify('error', 'An unexpected error occurred.');

                });
        });

        // Update error message spans for regular inputs
        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) { // Exclude Select2
                var errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.appendChild(errorSpan);
            }
        });

        // Clear error messages on user input
        // document.getElementById('title').addEventListener('input', function() {
        //     this.parentNode.querySelector('.error-message').textContent = '';
        // });

        const titleInput = document.getElementById('title');
        const errorSpan = titleInput.parentNode.querySelector('.error-message');

        titleInput.addEventListener('input', function() {
            let value = this.value;

            if (value.length > 40) {
                this.value = value.slice(0, 40); // stop extra characters
                errorSpan.textContent = 'Title cannot exceed 40 characters.';
            } else if (value.trim() === '') {
                errorSpan.textContent = 'Title is required.';
            } else {
                errorSpan.textContent = '';
            }
        });
        // document.getElementById('title').addEventListener('input', function() {
        //     $(this).next('.error-message').text('');
        // });
        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });
        document.getElementById('location_title').addEventListener('input', function() {
            $(this).next('.error-message').text('');

        });
        CKEDITOR.instances.description.on('change', function() {
            $('#location').next('.cke').next('.error-message').remove();
        });
        document.getElementById('contact_title').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });
        document.getElementById('contact_1').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });
        document.getElementById('email_title').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });
        document.getElementById('email_1').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });
        document.getElementById('contact_form_title').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });
        CKEDITOR.instances.description.on('change', function() {
            $('#contact_form_description').next('.cke').next('.error-message').remove();
        });
        // document.getElementById('address').addEventListener('input', function() {
        //     var addressContent = document.getElementById('address').value.trim();
        //     if (addressContent) {
        //         document.getElementById('address-error').style.display = 'none';
        //     }
        // });

        // document.getElementById('mobile_number').addEventListener('input', function() {
        //     var mobileContent = document.getElementById('mobile_number').value.trim();
        //     if (mobileContent) {
        //         document.getElementById('mobile-required-error').style.display = 'none';
        //     }
        //     if (/^[0-9]+$/.test(mobileContent)) {
        //         document.getElementById('mobile-format-error').style.display = 'none';
        //     }
        // });

        // document.getElementById('email').addEventListener('input', function() {
        //     var emailContent = document.getElementById('email').value.trim();
        //     if (emailContent) {
        //         document.getElementById('email-required-error').style.display = 'none';
        //     }
        //     if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailContent)) {
        //         document.getElementById('email-format-error').style.display = 'none';
        //     }
        // });

        // document.getElementById('status').addEventListener('change', function() {
        //     var statusContent = document.getElementById('status').value;
        //     if (statusContent) {
        //         document.getElementById('status-error').style.display = 'none';
        //     }
        // });

        $(document).ready(function() {
            $('#status').select2({
                placeholder: "Select a Status",
                allowClear: true
            });
        });
    </script>
@endsection
