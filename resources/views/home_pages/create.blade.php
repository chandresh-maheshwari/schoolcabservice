{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
 
        <div class="section-breadcrumb">
    <div class="breadcrumb-wrapper pb-0">
        <div class="container">
            <nav aria-label="breadcrumb-nav">
                <ol class="breadcrumb breadcrumb-style-2 my-20">
                    <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a></li> 
                    <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add Home Page</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
        <div class="container-fluid" >
    <div class="card">
        <div class="card-header" >
            <h4 class="home-page-create-header" >Add Home Page</h4>
        </div>
        <div class="card-body">
            <form id="homePageForm" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="title" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="template" style="font-weight: bold;">Template</label>
                    <select class="form-control" id="template" name="template">
                        <option value="">Select Template</option>
                        @foreach(($templates ?? []) as $tpl)
                            <option value="{{ $tpl }}">{{ ucfirst(str_replace('_',' ', $tpl)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="category_id" style="font-weight: bold;">Category <span style="color: red;">*</span></label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="image" style="font-weight: bold;">Image <span style="color: red;">*</span></label>
                    <div class="mt-2">
                        <input type="file" class="form-control-file" id="image" name="image" accept="image/*" style="display: none;" onchange="previewImage(event)">
                        <button type="button" class="btn btn-primary" id="uploadImageBtn" onclick="document.getElementById('image').click();" style="background-color: #2d336b; color: white;">Upload Image</button>
                        <span id="imageName"></span>
                        <img id="imagePreview" src="#" alt="Image Preview" style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                    </div>
                </div>
                <div class="form-group">
                    <label for="description" style="font-weight: bold;">Description <span style="color: red;">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                </div>
                <button type="button" class="btn btn-primary" id="submitBtn" style="background-color: #2d336b; color: white;">Submit</button>
                <a href="{{ route('home_pages.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
            </form>
        </div>
    </div>
</div>

<!-- <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script> -->
<script>
    CKEDITOR.replace('description');

    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function() {
            var output = document.getElementById('imagePreview');
            output.src = reader.result;
            output.style.display = 'block';
        }
        reader.readAsDataURL(event.target.files[0]);

        // Update the image name
        var imageName = document.getElementById('imageName');
        imageName.textContent = event.target.files[0].name;
    }

    document.getElementById('submitBtn').addEventListener('click', function() {
        var formData = new FormData(document.getElementById('homePageForm'));
        formData.set('description', CKEDITOR.instances.description.getData());

        // Clear previous error messages
        document.querySelectorAll('.error-message').forEach(function(el) {
            el.textContent = '';
        });

        // Validate form
        var isValid = true;
        if (!formData.get('title')) {
            document.getElementById('title').nextElementSibling.textContent = 'Title is required.';
            isValid = false;
        }
        if (!formData.get('category_id')) {
            $('#category_id').next('.select2').find('.select2-selection').after('<span class="error-message" style="color: red;">Category is required.</span>');
            isValid = false;
        }
        if (!CKEDITOR.instances.description.getData().trim()) {
            $('#description').next('.cke').after('<span class="error-message" style="color: red;">Description is required.</span>');
            isValid = false;
        }
        if (!formData.get('image').name) {
            $('#uploadImageBtn').after('<span class="error-message" style="color: red;">Image is required.</span>');
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

        fetch('{{ route('api.home_pages.store') }}', {
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
                notify('success', 'Home Page created Successfully!');
                setTimeout(function() {
                    window.location.href = '{{ route('home_pages.index') }}';
                }, 1500);
            } else {
                notify('error', 'There was an error creating the Home Page.');
            }
        })
        .catch(error => {
            Swal.close();
            notify('error', 'An unexpected error occurred.');
        });
    });

    // Add error message spans for regular inputs
    document.querySelectorAll('.form-control').forEach(function(input) {
        if (!input.classList.contains('select2-hidden-accessible')) { // Exclude Select2
            var errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.style.color = 'red';
            input.parentNode.insertBefore(errorSpan, input.nextSibling);
        }
    });

    $(document).ready(function() {
        $('#category_id').select2({
            placeholder: "Select a Category",
            allowClear: true
        });
    });

  
    document.getElementById('title').addEventListener('input', function() {
        this.nextElementSibling.textContent = '';
    });

    $('#category_id').on('change', function() {
        $(this).next('.select2-container').find('.error-message').remove();
    });

    CKEDITOR.instances.description.on('change', function() {
        $('#description').next('.cke').next('.error-message').remove();
    });

    document.getElementById('image').addEventListener('change', function() {
        $('#uploadImageBtn').next('.error-message').remove();
    });
</script>
@endsection 