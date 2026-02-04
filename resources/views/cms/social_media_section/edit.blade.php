{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    {{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="social-edit-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Edit Social</h2>
        </div> --}}
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('socialMediaSection.index') }}">Socials</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Social</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="social-edit-header">Edit Social</h4>
            </div>
            <div class="card-body">
                <form id="socialEditForm">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="social_name" style="font-weight: bold;">Social Name</label>
                        <input type="text" class="form-control" id="social_name" name="social_name"
                            value="{{ $social->social_name }}" required>
                        <div id="social_name-error-message" style="color: red;"></div>
                    </div>
                    <div class="form-group">
                        <label for="social_link" style="font-weight: bold;">
                            Social Link <span style="color:red;">*</span>
                        </label>
                        <input type="url" class="form-control" id="social_link" name="social_link"
                            value="{{ $social->social_link }}" required placeholder="https://example.com">
                        <div id="social_link-error-message" style="color: red;"></div>
                    </div>

                    <div class="form-group">
                        <label for="social_icon" style="font-weight: bold;">Social Icon <span
                                style="color: red;">*</span></label>
                        <select class="form-control" id="social_icon" name="social_icon" required></select>
                        <div id="social_icon-error-message" style="color: red;"></div>
                    </div>

                    <button type="button" class="btn btn-primary" id="updateBtn"
                        style="background-color: #2d336b; color: white;">Update</button>
                    <a href="{{ route('socialMediaSection.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2
            $('#social_icon').select2({
                placeholder: "Select an Icon",
                allowClear: true,
                templateResult: formatIcon,
                templateSelection: formatIcon,
                width: '100%'
            });

            // Populate social icons
            const socialIcons = [{
                    value: 'fab fa-facebook-f',
                    text: 'Facebook'
                },
                {
                    value: 'fab fa-twitter',
                    text: 'Twitter'
                },
                {
                    value: 'fab fa-instagram',
                    text: 'Instagram'
                },
                {
                    value: 'fab fa-linkedin-in',
                    text: 'LinkedIn'
                },
                {
                    value: 'fab fa-youtube',
                    text: 'YouTube'
                },
                {
                    value: 'fab fa-pinterest-p',
                    text: 'Pinterest'
                },
                {
                    value: 'fab fa-tiktok',
                    text: 'TikTok'
                },
                {
                    value: 'fab fa-github',
                    text: 'GitHub'
                },
                {
                    value: 'fab fa-telegram-plane',
                    text: 'Telegram'
                }
            ];

            const socialIconSelect = document.getElementById('social_icon');
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.disabled = true;
            defaultOption.textContent = 'Select an icon';
            socialIconSelect.appendChild(defaultOption);

            @if (isset($social->social_icon))
                const selectedIcon = "{{ $social->social_icon }}";
            @else
                const selectedIcon = '';
            @endif

            socialIcons.forEach(icon => {
                const option = document.createElement('option');
                option.value = icon.value;
                option.textContent = icon.text;
                option.setAttribute('data-icon', icon.value);
                if (selectedIcon === icon.value) {
                    option.selected = true;
                }
                socialIconSelect.appendChild(option);
            });

            function formatIcon(icon) {
                if (!icon.id) return icon.text;
                return $('<span><i class="' + $(icon.element).data('icon') + '" style="margin-right:8px"></i>' +
                    icon.text + '</span>');
            }

            // Add empty error-message spans if not already present
            ['social_name', 'social_link'].forEach(id => {
                const input = document.getElementById(id);
                if (input && !document.getElementById(`${id}-error-message`)) {
                    const error = document.createElement('span');
                    error.className = 'error-message';
                    error.id = `${id}-error-message`;
                    error.style.color = 'red';
                    input.parentNode.insertBefore(error, input.nextSibling);
                }
            });

            // Input error clearing
            document.getElementById('social_name').addEventListener('input', function() {
                document.getElementById('social_name-error-message').textContent = '';
            });

            document.getElementById('social_link').addEventListener('input', function() {
                document.getElementById('social_link-error-message').textContent = '';
            });

            document.getElementById('social_icon').addEventListener('change', function() {
                document.getElementById('social_icon-error-message').textContent = '';
            });

            // Submit handler
            document.getElementById('updateBtn').addEventListener('click', function() {
                const form = document.getElementById('socialEditForm');
                const formData = new FormData(form);

                // Clear previous errors
                document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

                let isValid = true;

                if (!formData.get('social_name') || !formData.get('social_name').trim()) {
                    document.getElementById('social_name-error-message').textContent =
                        'Social Name is required.';
                    isValid = false;
                }

                if (!formData.get('social_link') || !formData.get('social_link').trim()) {
                    document.getElementById('social_link-error-message').textContent =
                        'Social Link is required.';
                    isValid = false;
                }

                if (!formData.get('social_icon')) {
                    document.getElementById('social_icon-error-message').textContent =
                        'Social Icon is required.';
                    isValid = false;
                }

                if (!isValid) return;

                Swal.fire({
                    title: 'Please wait...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Submit form
                formData.append('_method', 'PUT');

                fetch('{{ route('api.socialMediaSection.update', $social->id) }}', {
                        method: 'POST', // Use POST with _method=PUT for file uploads in Laravel
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw err; // 👈 validation errors
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        Swal.close();

                        if (data.success) {
                            notify('success', data.message || 'Social updated Successfully!');
                            setTimeout(function() {
                                window.location.href =
                                '{{ route('socialMediaSection.index') }}';
                            }, 1500);
                        }
                    })
                    .catch(error => {
                        Swal.close();

                        if (error.errors) {
                            Object.keys(error.errors).forEach(function(field) {
                                const message = error.errors[field][0];

                                const errorDiv = document.getElementById(field +
                                    '-error-message');
                                if (errorDiv) {
                                    errorDiv.textContent = message;
                                }

                                notify('error', message);
                            });
                        } else {
                            notify('error', 'An unexpected error occurred.');
                        }
                    });
            });
        });
    </script>
@endsection
