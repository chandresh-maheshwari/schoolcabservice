{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')
@section('content')
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('faqSection.index') }}">FAQ Section</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit FAQ
                            Section
                            Details</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="hero-edit-header">Edit
                    FAQ Section Details</h4>
            </div>
            <div class="card-body">
                <form id="faqSectionForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                       <div class="form-group">
                        <label for="name" style="font-weight: bold;">Name </label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="{{ $faqSection->name }}" required>
                    </div>
                    <div class="form-group">
                        <label for="question" style="font-weight: bold;">Question  <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="question" name="question"
                            value="{{ $faqSection->question }}" required>
                    </div>
                    <div class="form-group">
                        <label for="answer" style="font-weight: bold;">Answer <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="answer" name="answer" required>{{ $faqSection->answer }}</textarea>
                    </div>
                        <button type="button" class="btn btn-primary" id="submitBtn"
                            style="background-color: #2C9DD4; color: white;">Update</button>
                        <a href="{{ route('faqSection.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>

                </form>
            </div>
        </div>
    </div>

    <script>
        CKEDITOR.replace('answer');


        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('faqSectionForm'));
            formData.set('answer', CKEDITOR.instances.answer.getData());

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            // Validate form
            var isValid = true;
            if (!formData.get('question')) {
                document.getElementById('question').nextElementSibling.textContent = 'Question is required.';
                isValid = false;
            }

            if (!CKEDITOR.instances.answer.getData().trim()) {
                $('#answer').next('.cke').after(
                    '<span class="error-message" style="color: red;">Answer is required.</span>');
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            formData.append('_method', 'PUT');
            fetch('{{ route('api.faqSection.update', $faqSection->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'Accept': 'application/json'
                    }
                })
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) {
                        throw data;
                    }

                    return data;
                })
                .then(data => {
                    Swal.close();

                    notify('success', 'FAQ Section Details updated Successfully!');
                    setTimeout(() => {
                        window.location.href = '{{ route('faqSection.index') }}';
                    }, 1500);
                })
                .catch(error => {
                    Swal.close();
                    if (error.type === 'validation' && error.errors) {
                        Object.values(error.errors).forEach(messages => {
                            notify('error', messages[0]);
                        });
                    }
                    else if (error.message) {
                        notify('error', error.message);
                    } else {
                        notify('error', 'Something went wrong');
                    }
                });
        });

        // Add error message spans for regular inputs
        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) { // Exclude Select2
                var errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.appendChild(errorSpan);
            }
        });
        const titleInput = document.getElementById('question');
        const errorSpan = titleInput.parentNode.querySelector('.error-message');
         document.getElementById('question').addEventListener('input', function() {
            $(this).next('.error-message').text('');
        });

        CKEDITOR.instances.answer.on('change', function() {
            $('#answer').next('.cke').next('.error-message').remove();
        });

    </script>
@endsection
