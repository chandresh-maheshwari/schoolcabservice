{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
{{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="social-create-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Create Socials</h2>
        </div> --}}
        <div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Create Socials</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4 class="social-create-header">Create Socials</h4>
                </div>
        <div class="card-body">
            <form id="socialForm">
                @csrf
                <div class="form-group">
                    <label for="social_name" style="font-weight: bold;">Social Name <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="social_name" name="social_name" required>
                </div>
                <div class="form-group">
                    <label for="social_link" style="font-weight: bold;">Social Link <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="social_link" name="social_link" required>
                </div>
                <div class="form-group">
                    <label for="social_icon" style="font-weight: bold;">Social Icon <span style="color: red;">*</span></label>
                    <select class="form-control" id="social_icon" name="social_icon" required></select>
                    <div id="icon-error-message" style="color: red;"></div>
                </div>

                <button type="button" class="btn btn-primary" id="submitBtn" style="background-color: #2C9DD4; color: white;">Create</button>
                <a href="{{ route('socialMediaSection.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
    // Initialize Select2 dropdowns
$(document).ready(function() {

    function formatIcon(icon) {
        if (!icon.id) return icon.text;
        return $('<span><i class="' + $(icon.element).data('icon') + '" style="margin-right:8px"></i>' + icon.text + '</span>');
    }

    $('#social_icon').select2({
        placeholder: "Select an Icon",
        allowClear: true,
        templateResult: formatIcon,
        templateSelection: formatIcon,
        width: '100%'
    });
});

// Populate social icons
const socialIcons = [
    { value: 'fab fa-facebook-f', text: 'Facebook' },
    { value: 'fab fa-twitter', text: 'Twitter' },
    { value: 'fab fa-instagram', text: 'Instagram' },
    { value: 'fab fa-linkedin-in', text: 'LinkedIn' },
    { value: 'fab fa-youtube', text: 'YouTube' },
    { value: 'fab fa-pinterest-p', text: 'Pinterest' },
    { value: 'fab fa-tiktok', text: 'TikTok' },
    { value: 'fab fa-github', text: 'GitHub' },
    { value: 'fab fa-telegram-plane', text: 'Telegram' }
];

const socialIconSelect = document.getElementById('social_icon');
const defaultOption = document.createElement('option');
defaultOption.value = '';
defaultOption.disabled = true;
defaultOption.selected = true;
defaultOption.textContent = 'Select an icon';
socialIconSelect.appendChild(defaultOption);

socialIcons.forEach(icon => {
    const option = document.createElement('option');
    option.value = icon.value;
    option.textContent = icon.text;
    option.setAttribute('data-icon', icon.value);
    socialIconSelect.appendChild(option);
});

// Insert error spans on page load
document.querySelectorAll('.form-control').forEach(function(input) {
    if (!input.classList.contains('select2-hidden-accessible')) {
        const errorSpan = document.createElement('span');
        errorSpan.className = 'error-message';
        errorSpan.style.color = 'red';
        input.parentNode.insertBefore(errorSpan, input.nextSibling);
    }
});

// Submit button logic
document.getElementById('submitBtn').addEventListener('click', function() {
    const form = document.getElementById('socialForm');
    const formData = new FormData(form);
    const nameInput = document.getElementById('social_name');
    const linkInput = document.getElementById('social_link');
    const iconInput = document.getElementById('social_icon');

    // Clear previous errors
    document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

    let isValid = true;

    if (!formData.get('social_name') || !formData.get('social_name').trim()) {
        nameInput.nextElementSibling.textContent = 'Social Name is required.';
        isValid = false;
    }

    if (!formData.get('social_link') || !formData.get('social_link').trim()) {
        linkInput.nextElementSibling.textContent = 'Social Link is required.';
        isValid = false;
    }

    if (!formData.get('social_icon')) {
        document.getElementById('icon-error-message').textContent = 'Social Icon is required.';
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
    fetch('{{ route('api.socialMediaSection.store') }}', {
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
            Swal.close();
            notify('success', 'Social created Successfully!');
            setTimeout(function() {
                window.location.href = '{{ route('socialMediaSection.index') }}';
            }, 1500);
        } else {
            Swal.close();
            notify('error', 'There was an error creating the social.');
        }
    })
    .catch(() => {
        Swal.close();
        notify('error', 'An unexpected error occurred.');
    });
});

// Clear field error on input/change
document.getElementById('social_name').addEventListener('input', function() {
    this.nextElementSibling.textContent = '';
});

document.getElementById('social_link').addEventListener('input', function() {
    this.nextElementSibling.textContent = '';
});


$('#social_name, #social_link').on('input', function() {
    $(this).next('.error-message').text('');
});


// Clear error when selecting a social icon
$('#social_icon').on('change', function() {
    $('#icon-error-message').text('');
});

</script>
@endsection
