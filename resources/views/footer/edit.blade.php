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
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add Contact Info</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="user-listing-header">Add Contact Info</h4>
            </div>
            <div class="card-body">
                <form id="footerForm">
                    @csrf

                    <div class="form-group">
                        <label for="title" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ $footer->title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="footer_link" style="font-weight: bold;">Footer Link <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="footer_link" name="footer_link"
                            value="{{ $footer->footer_link }}" required>
                    </div>
                    <div class="form-group">
                        <label for="location" style="font-weight: bold;">Location <span style="color: red;">*</span></label>
                        <textarea class="form-control" id="location" name="location" rows="4" required>{{ $footer->location }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="contact_title" style="font-weight: bold;">Contact Title <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="contact_title" name="contact_title"
                            value="{{ $footer->contact_title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="contact" style="font-weight: bold;">Contact <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="contact" name="contact" minlength='10'
                            maxlength='12' value="{{ $footer->contact }}" required>
                    </div>
                    <div class="form-group">
                        <label for="email_title" style="font-weight: bold;">Email Title <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="email_title" name="email_title"
                            value="{{ $footer->email_title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="email" style="font-weight: bold;">Email <span style="color: red;">*</span></label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="{{ $footer->email }}"required>
                    </div>
                    <div class="form-group">
                        <label for="footer_link_title" style="font-weight: bold;">Footer Link Title <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="footer_link_title" name="footer_link_title"
                            value="{{ $footer->footer_link_title }}"required>
                    </div>
                    <div class="form-group">
                        <label for="page_title_1" style="font-weight: bold;">Page Title 1<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="page_title_1" name="page_title_1"
                            value="{{ $footer->page_title_1 }}"required>
                    </div>
                    <div class="form-group">
                        <label for="page_link_1" style="font-weight: bold;">Page Link 1<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="page_link_1" name="page_link_1"
                            value="{{ $footer->page_link_1 }}"required>
                    </div>
                    <div class="form-group">
                        <label for="page_title_2" style="font-weight: bold;">Page Title 2<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="page_title_2" name="page_title_2"
                            value="{{ $footer->page_title_2 }}"required>
                    </div>
                    <div class="form-group">
                        <label for="page_link_2" style="font-weight: bold;">Page Link 2<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="page_link_2" name="page_link_2"
                            value="{{ $footer->page_link_2 }}"required>
                    </div>
                    <div class="form-group">
                        <label for="page_title_3" style="font-weight: bold;">Page Title 3<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="page_title_3" name="page_title_3"
                            value="{{ $footer->page_title_3 }}"required>
                    </div>
                    <div class="form-group">
                        <label for="page_link_3" style="font-weight: bold;">Page Link 3<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="page_link_3" name="page_link_3"
                            value="{{ $footer->page_link_3 }}"required>
                    </div>
                    <div class="form-group">
                        <label for="page_title_4" style="font-weight: bold;">Page Title 4<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="page_title_4" name="page_title_4"
                            value="{{ $footer->page_title_4 }}"required>
                    </div>
                    <div class="form-group">
                        <label for="page_link_4" style="font-weight: bold;">Page Link 4<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="page_link_4" name="page_link_4"
                            value="{{ $footer->page_link_4 }}"required>
                    </div>
                    <div class="form-group">
                        <label for="footer_service_title" style="font-weight: bold;">Footer Service Title<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="footer_service_title" name="footer_service_title"
                            value="{{ $footer->footer_service_title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="service_title_1" style="font-weight: bold;">Service Title 1<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="service_title_1" name="service_title_1"
                            value="{{ $footer->service_title_1 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="service_link_1" style="font-weight: bold;">Service Link 1<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="service_link_1" name="service_link_1"
                            value="{{ $footer->service_link_1 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="service_title_2" style="font-weight: bold;">Service Title 2<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="service_title_2" name="service_title_2"
                            value="{{ $footer->service_title_2 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="service_link_2" style="font-weight: bold;">Service Link 2<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="service_link_2" name="service_link_2"
                            value="{{ $footer->service_link_2 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="service_title_3" style="font-weight: bold;">Service Title 3<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="service_title_3" name="service_title_3"
                            value="{{ $footer->service_title_3 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="service_link_3" style="font-weight: bold;">Service Link 3<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="service_link_3" name="service_link_3"
                            value="{{ $footer->service_link_3 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="service_title_4" style="font-weight: bold;">Service Title 4<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="service_title_4" name="service_title_4"
                            value="{{ $footer->service_title_4 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="service_link_4" style="font-weight: bold;">Service Link 4<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="service_link_4" name="service_link_4"
                            value="{{ $footer->service_link_4 }}" required>
                    </div>
                    <div class="form-group">
                        <label for="follow_us" style="font-weight: bold;">Follow Us<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="follow_us" name="follow_us"
                            value="{{ $footer->follow_us }}" required>
                    </div>
                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ $footer->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="copy_right_text" style="font-weight: bold;">Copy Right Text<span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="copy_right_text" name="copy_right_text"
                            value="{{ $footer->copy_right_text }}" required>
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('footer.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        CKEDITOR.replace('description');
        CKEDITOR.replace('location');

        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('footerForm'));
            formData.set('location', CKEDITOR.instances.location.getData());
            formData.set('description', CKEDITOR.instances.description.getData());

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });
            // (Optional) Add your validation here if needed, but do not re-create FormData after this point.
            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            fetch('{{ route('api.footer.update', $footer->id) }}', {
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
                        notify('success', 'Footer Updated Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('footer.index') }}';
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

        // Add event listeners to clear error messages on user input for each field
        document.getElementById('title').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('footer_link').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        // document.getElementById('location').addEventListener('input', function() {
        //     this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
        //         .nextElementSibling.textContent = '');
        // });
        CKEDITOR.instances.description.on('change', function() {
            $('#location').next('.cke').next('.error-message').remove();
        });

        document.getElementById('contact_title').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        // document.getElementById('contact').addEventListener('input', function() {
        //     this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
        //         .nextElementSibling.textContent = '');
        // });
        if (!formData.get('contact')) {
            document.getElementById('contact').nextElementSibling.textContent =
                'Contact is required.';
            isValid = false;
        } else {
            const phoneRegex = /^\d{10,12}$/;
            if (!phoneRegex.test(formData.get('contact'))) {
                document.getElementById('contact').nextElementSibling.textContent =
                    'Phone Number must contain only digits and be between 10 and 12 characters long.';
                isValid = false;
            } else {
                document.getElementById('contact').nextElementSibling.textContent = '';
            }
        }

        document.getElementById('email_title').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('email').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('footer_link_title').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('page_title_1').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('page_link_1').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('page_title_2').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('page_link_2').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('page_title_3').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('page_link_3').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('page_title_4').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('page_link_4').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('footer_service_title').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('service_title_1').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('service_link_1').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('service_title_2').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('service_link_2').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('service_title_3').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('service_link_3').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('service_title_4').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('service_link_4').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        document.getElementById('follow_us').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        // document.getElementById('description').addEventListener('input', function() {
        //     this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
        //         .nextElementSibling.textContent = '');
        // });
        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });

        document.getElementById('copy_right_text').addEventListener('input', function() {
            this.nextElementSibling && this.nextElementSibling.classList.contains('error-message') && (this
                .nextElementSibling.textContent = '');
        });

        // For CKEditor instances, listen to change events to clear error messages
        if (CKEDITOR.instances.description) {
            CKEDITOR.instances.description.on('change', function() {
                $('#description').next('.cke').next('.error-message').remove();
            });
        }
        if (CKEDITOR.instances.location) {
            CKEDITOR.instances.location.on('change', function() {
                $('#location').next('.cke').next('.error-message').remove();
            });
        }
    </script>
@endsection
