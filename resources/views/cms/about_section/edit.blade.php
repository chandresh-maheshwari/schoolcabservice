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
                            Edit About Section Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit About Section Details</h4>
            </div>

            <div class="card-body">
                <form id="aboutSectionForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label> Name </label>
                        <input type="text" class="form-control" id="name" name="name" autocomplete="off" value="{{ $aboutSection->name }}">
                    </div>
                    <div class="form-group">
                        <label>Title </label>
                        <input type="text" class="form-control" id="title" name="title" autocomplete="off" value="{{ $aboutSection->title }}">
                    </div>
                    <div class="form-group">
                        <label>Image <span style="color:red;">*</span></label><br>
                        <button type="button" class="btn btn-primary" id="ImageBtn"
                            onclick="document.getElementById('image').click();">Upload Image</button>
                        <input type="file" id="image" name="image" accept="image/*"
                            style="display:none;" onchange="previewImage(event)">
                        <br>
                        @php
                            $imagePath = $aboutSection->image
                                ? public_path('storage/aboutSection/' . $aboutSection->image)
                                : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $imageUrl = $imageExists
                                ? asset('storage/aboutSection/' . $aboutSection->image)
                                : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName">
                            {{ $imageExists && !$isDefaultImage ? basename($aboutSection->image) : 'No image' }}
                        </span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div">
                        <img id="imagePreview" src="{{ $imageUrl }}" alt="Image Preview"
                            style="display: block; width: 100px; height: 100px; margin-top: 10px;">
                        {{-- {{basename($imageUrl) !== 'Default.jpg'}} --}}
                        <button type="button" id="removeImageBtn" class="btn btn-sm"
                            style="display: none; margin-top: 10px; margin-left: 10px;">
                            <i class="fas fa-trash"></i> </button>
                        @if (!$isDefaultImage)
                            <button type="button" id="deleteImageBtn" class="btn btn-sm"
                                style="margin-top: 10px; margin-left: 10px;">
                                <i class="fas fa-trash"></i> </button>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Description <span style="color:red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ $aboutSection->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Button Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="button_name" name="button_name" autocomplete="off" value="{{ $aboutSection->button_name }}">
                    </div>
                    <div class="form-group">
                        <label>Button Link <span style="color:red;">*</span></label>
                        <input type="url" class="form-control" id="button_link" name="button_link" autocomplete="off" value="{{ $aboutSection->button_link }}">
                    </div>

                    <button type="button" class="btn btn-primary" id="submitBtn">Update</button>
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


            if (!formData.get('button_name')) showError('#button_name', 'Button Name is required');
            if (!formData.get('button_link')) showError('#button_link', 'Button Link is required');

            // Image validation for edit: Image is optional if it already exists
            var imageInput = document.getElementById('image');
            var imagePreview = document.getElementById('imagePreview');
            var currentImageSrc = imagePreview.getAttribute('src');

            // If no new file selected AND no existing image (src is # or empty)
            if (!imageInput.files.length && (currentImageSrc == "#" || currentImageSrc == "")) {
                 $('#ImageBtn').after(
                    '<span class="error-message" style="color: red;">Image is required.</span>');
                isValid = false;
            }
              if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }

            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            // Append _method PUT explicitly if not picked up by some server configs,
            // though @method('PUT') adds _method field which FormData should catch.
            // But just to be safe with FormData sometimes needing help with PUT/PATCH in Laravel:
            formData.append('_method', 'PUT');

            fetch('{{ route('api.aboutSection.update', $aboutSection->id) }}', {
    method: 'POST', // Laravel file upload friendly
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
        throw data.message || 'Update failed';
    }

    return data;
})
.then(() => {
    Swal.close();
    notify('success', 'About Section updated successfully!');
    setTimeout(() => {
        window.location.href = '{{ route('aboutSection.index') }}';
    }, 1500);
})
.catch(error => {
    Swal.close();
    notify(
        'error',
        typeof error === 'string'
            ? error
            : (error.message || 'Unexpected error')
    );
});
        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });

        document.getElementById('image').addEventListener('change', function() {
            $('#ImageBtn').next('.error-message').remove();
        })

        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });
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

         const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.aboutSection.aboutImage', $aboutSection->id) }}',
                    csrfToken: document.querySelector('input[name="_token"]').value,
                    imagePreviewSelector: '#imagePreview',
                    buttonSelector: '#deleteImageBtn',
                    nameSelector: '#imageName',
                    successMessage: 'Image deleted successfully.'
                });
            });
        }
    </script>
@endsection
