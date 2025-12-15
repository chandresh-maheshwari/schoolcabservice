{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    {{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="about-us-create-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Add Service</h2>
        </div> --}}
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a
                                class="breadcrumbLink"href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add Advance Capability
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Advance Capabilities Details</h4>
            </div>
            <div class="card-body">
                <form id="heroSectionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="title" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="advance_capability_icon" style="font-weight: bold;">Advance capability Icon<span
                                style="color: red;">*</span></label>
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text bg-white" id="stat-icon-preview-1"
                                style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                            <input type="text" class="form-control" id="advance_capability_icon"
                                name="advance_capability_icon" required placeholder="Select an icon..."
                                aria-describedby="stat-icon-preview-1" style="height: 40px;">
                            <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                data-iconset="fontawesome5" data-input="advance_capability_icon"
                                data-preview="stat-icon-preview-1"
                                style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                    class="fas fa-icons"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="feature_benifit_1" style="font-weight: bold;">Feature Benefits 1 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="feature_benifit_1" name="feature_benifit_1" required>
                    </div>
                    <div class="form-group">
                        <label for="feature_benifit_2" style="font-weight: bold;">Feature Benefits 2 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="feature_benifit_2" name="feature_benifit_2" required>
                    </div>
                    <div class="form-group">
                        <label for="feature_status_badge" style="font-weight: bold;">Feature Status Badge <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="feature_status_badge" name="feature_status_badge"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="image" style="font-weight: bold;"> Image <span
                                style="color: red;">*</span></label>
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <span id="imageName"></span>
                            {{-- <img id="imagePreview" src="#" alt="Image Preview"
                                style="display: none; width: 100px; height: 100px; margin-top: 10px;"> --}}
                        </div>
                         <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                            <img id="imagePreview" src="#" alt="Image Preview"
                                style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                            <button type="button" class="btn" style="display: none" id="removeImageBtn"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>

                    {{-- <div class="form-group">
                        <label for="image" style="font-weight: bold;">Banner Image <span
                                style="color: red;">*</span></label>
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <span id="imageName"></span>
                            <img id="imagePreview" src="#" alt="Image Preview"
                                style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        </div>
                    </div> --}}


                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('advance_capability.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Include common icon picker JS -->
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>

    <script>
        CKEDITOR.replace('description');

        // function previewImage(event) {
        //     var reader = new FileReader();
        //     reader.onload = function() {
        //         var output = document.getElementById('imagePreview');
        //         output.src = reader.result;
        //         output.style.display = 'block';
        //     }
        //     reader.readAsDataURL(event.target.files[0]);

        //     var imageName = document.getElementById('imageName');
        //     imageName.textContent = event.target.files[0].name;
        // }

        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('heroSectionForm'));
            formData.set('description', CKEDITOR.instances.description.getData());

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            // Validate form (only required fields in this form)
            var isValid = true;
            if (!formData.get('title')) {
                document.getElementById('title').nextElementSibling.textContent = 'Title is required.';
                isValid = false;
            }
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }
            if (!formData.get('advance_capability_icon')) {
                $('#advance_capability_icon').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Icon is required.</span>'
                );
                isValid = false;
            }
            if (!formData.get('feature_benifit_1')) {
                document.getElementById('feature_benifit_1').nextElementSibling.textContent =
                    'Feature Benefits 1 is required.';
                isValid = false;
            }
            if (!formData.get('feature_benifit_2')) {
                document.getElementById('feature_benifit_2').nextElementSibling.textContent =
                    'Feature Benefits 2 is required.';
                isValid = false;
            }
            if (!formData.get('feature_status_badge')) {
                document.getElementById('feature_status_badge').nextElementSibling.textContent =
                    'Feature Status Badge is required.';
                isValid = false;
            }
            if (!formData.get('image') || !formData.get('image').name) {
                $('#uploadImageBtn').after(
                    '<span class="error-message" style="color: red;">Image is required.</span>');
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

            fetch('{{ route('api.advance_capability.store') }}', {
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
                        notify('success', 'Advance Capability Created Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('advance_capability.index') }}';
                        }, 1500);
                    } else {
                        notify('error', data.message || 'There was an error creating the capability details.');
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
                input.parentNode.appendChild(errorSpan);
            }
        });

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
        document.getElementById('feature_benifit_1').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        document.getElementById('feature_benifit_2').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        document.getElementById('feature_status_badge').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });

        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });
        document.getElementById('advance_capability_icon').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('image').addEventListener('change', function() {
            $('#uploadImageBtn').next('.error-message').remove();
        });
        document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });
        // Removed select2 initializations for non-existent fields
    </script>
@endsection
