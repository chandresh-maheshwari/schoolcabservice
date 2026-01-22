{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a
                                class="breadcrumbLink"href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add FAQ Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add FAQ Section Details</h4>
            </div>
            <div class="card-body">
                <form id="faqSectionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="question" style="font-weight: bold;">Question <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="question" name="question" required>
                    </div>
                    <div class="form-group">
                        <label for="answer" style="font-weight: bold;">Answer <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="answer" name="answer" rows="4" required></textarea>
                    </div>
                    
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('faqSection.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>


    <script>
    CKEDITOR.replace('answer');

    document.getElementById('submitBtn').addEventListener('click', function () {

        var formData = new FormData(document.getElementById('faqSectionForm'));
        formData.set('answer', CKEDITOR.instances.answer.getData());

        // Clear previous error messages
        document.querySelectorAll('.error-message').forEach(function (el) {
            el.textContent = '';
        });

        var isValid = true;

         if (!formData.get('question').trim()) {
                document.getElementById('question').nextElementSibling.textContent = 'Question is required.';
                isValid = false;
            }
        if (!CKEDITOR.instances.answer.getData().trim()) {
                $('#answer').next('.cke').after(
                    '<span class="error-message" style="color: red;">Answer is required.</span>');
                isValid = false;
            }

        // Answer validation (CKEditor)
        if (!CKEDITOR.instances.answer.getData().trim()) {
            if ($('#answer').next('.cke').next('.error-message').length === 0) {
                $('#answer').next('.cke').after(
                    '<span class="error-message" style="color:red;">Answer is required.</span>'
                );
            }
            isValid = false;
        }

        if (!isValid) {
            return false;
        }

        Swal.fire({
            title: 'Please wait...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('{{ route('api.faqSection.store') }}', {
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
                notify('success', 'FAQ Section details created Successfully!');
                setTimeout(function () {
                    window.location.href = '{{ route('faqSection.index') }}';
                }, 1500);
            } else {
                notify('error', data.message || 'There was an error creating the FAQ section details.');
            }
        })
        .catch(error => {
            Swal.close();
            notify('error', 'An unexpected error occurred.');
        });
    });

    // Error message spans
    document.querySelectorAll('.form-control').forEach(function (input) {
        if (!input.classList.contains('select2-hidden-accessible')) {
            var errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.style.color = 'red';
            input.parentNode.appendChild(errorSpan);
        }
    });

       document.getElementById('question').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });
    CKEDITOR.instances.answer.on('change', function () {
        $('#answer').next('.cke').next('.error-message').remove();
    });
</script>

@endsection
