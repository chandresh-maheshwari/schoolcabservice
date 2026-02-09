@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item">
                            <a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Add About Section Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add About Section Details</h4>
            </div>

            <div class="card-body">
                <form id="aboutSectionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label> Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Title <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label> Image <span style="color:red;">*</span></label><br>
                        <button type="button" class="btn btn-primary" id="ImageBtn"
                            onclick="document.getElementById('image').click();">Upload Image</button>
                        <input type="file" id="image" name="image" accept="image/*" style="display:none;"
                            onchange="previewImage(event)">
                        <span id="imageName"></span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn"><i
                                class="fas fa-trash"></i></button>
                    </div>
                    <div class="form-group">
                        <label>Description <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Button Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="button_name" name="button_name" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Button Link <span style="color:red;">*</span></label>
                        <input type="url" class="form-control" id="button_link" name="button_link" autocomplete="off">
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                    <a href="{{ route('aboutSection.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
           CKEDITOR.replace('description');
        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('aboutSectionForm'));
              formData.set('description', CKEDITOR.instances.description.getData());
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('name')) showError('#name', 'Name is required');
            if (!formData.get('title')) showError('#title', 'Title is required');
            if (!formData.get('button_name')) showError('#button_name', 'Button Name is required');
            if (!formData.get('button_link')) showError('#button_link', 'Button Link is required');
if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }

        // Answer validation (CKEditor)
        if (!CKEDITOR.instances.description.getData().trim()) {
            if ($('#description').next('.cke').next('.error-message').length === 0) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color:red;">Description is required.</span>'
                );
            }
            isValid = false;
        }
            function isValidPositive(value) {
                return /^[a-zA-Z0-9]+$/.test(value);
            }

            var imageInput = document.getElementById('image');
            var imagePreview = document.getElementById('imagePreview');
            var imageError = document.getElementById('imageError');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#ImageBtn').after(
                    '<span class="error-message" style="color: red;">Image is required.</span>');
                isValid = false;
            }

            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.aboutSection.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {

                    let data;
                    try {
                        data = await res.json();
                    } catch (e) {
                        throw 'Invalid server response';
                    }

                    if (!res.ok || data.success === false) {
                        throw data.message || 'Something went wrong';
                    }

                    return data;
                })
                .then(() => {
                    Swal.close();
                    notify('success', 'About Section created successfully!');
                    setTimeout(() => {
                        window.location.href = '{{ route('aboutSection.index') }}';
                    }, 1500);
                })
                .catch(error => {
                    Swal.close();
                    notify(
                        'error',
                        typeof error === 'string' ?
                        error :
                        (error.message || 'Unexpected error')
                    );
                });
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });


CKEDITOR.instances.description.on('change', function () {
        $('#description').next('.cke').next('.error-message').remove();
    });
        document.getElementById('image').addEventListener('change', function() {
            $('#ImageBtn').next('.error-message').remove();
        })


        const allowedRegex = /^[a-zA-Z0-9]+$/;

        // real-time typing + paste validation


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
