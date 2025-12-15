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
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add Teams</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Teams Details</h4>
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
                    {{-- <div class="form-group">
                        <label for="image" style="font-weight: bold;">Image <span style="color: red;">*</span></label>
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)" required>
                            <span id="imageName"></span>
                            <img id="imagePreview" src="#" alt="Image Preview"
                                style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        </div>
                    </div> --}}
                    <div class="form-group">
                        <label for="image" style="font-weight: bold;"> Image <span style="color: red;">*</span></label>
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
                            <button type="button" class="btn" style="display: none" id="removeImageBtn"><i
                                    class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="role" style="font-weight: bold;">Role <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="role" name="role" required>
                    </div>
                    {{--
                    <div id="social-container" class ="social-fields">
                        <div class="" id="social-1"  style="border: #2C9DD4 1px solid; padding: 10px; border-radius: 5px;">
                            <button class="plus-btn float-end mb-2" title="Add New social Media" onclick="addSocialFields()">+</button>
                            <div class="form-group">
                                <label for="social_icon_1" style="font-weight: bold;">Social icon <span
                                        style="color: red;">*</span></label>
                                <input type="text" class="form-control" id="social_icon_1" name="social_icon_1" required>
                            </div>
                            <div class="form-group">
                                <label for="social_link_1" style="font-weight: bold;">Social Link <span
                                        style="color: red;">*</span></label>
                                <input type="text" class="form-control" id="social_link_1" name="social_link_1" required>
                            </div>
                        </div>
                    </div> --}}

                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('teams.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Include common icon picker JS -->
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>

    <script>
        let counter = 2;

        // Function to add new social fields
        function addSocialFields() {
            const socialContainer = document.getElementById('social-container');
            const newDiv = document.createElement('div');
            newDiv.classList.add('social-fields');
            newDiv.setAttribute('id', 'social-' + counter);

            // newDiv.innerHTML = `
        // <div class="mt-2" style="border: #2C9DD4 1px solid; padding: 10px; border-radius: 5px;">
        //     <span class="remove-btn float-end text-danger" onclick="removeSocialFields('social-${counter}')">✖</span>
        //     <div class="form-group">
        //         <label for="social_icon_${counter}" style="font-weight: bold;">Social icon ${counter} <span style="color: red;">*</span></label>
        //         <input type="text" class="form-control" id="social_icon_${counter}" name="social_icon_${counter}" required>
        //     </div>
        //     <div class="form-group">
        //         <label for="social_link_${counter}" style="font-weight: bold;">Social Link ${counter} <span style="color: red;">*</span></label>
        //         <input type="text" class="form-control" id="social_link_${counter}" name="social_link_${counter}" required>
        //     </div>
        // </div>
        // `;
            newDiv.innerHTML = `
            <div class="mt-2" style="border: #2C9DD4 1px solid; padding: 10px; border-radius: 5px;">
                <span class="remove-btn float-end text-danger" onclick="removeSocialFields('social-${counter}')">✖</span>
                <div class="form-group">
                    <label for="social_icon_${counter}" style="font-weight: bold;">Social icon <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="social_icon_${counter}" name="social_icon_${counter}" required>
                </div>
                <div class="form-group">
                    <label for="social_link_${counter}" style="font-weight: bold;">Social Link <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="social_link_${counter}" name="social_link_${counter}" required>
                </div>
            </div>
            `;

            // Append the new fields to the social container
            socialContainer.appendChild(newDiv);

            // Increment the counter
            counter++;
        }

        // Function to remove social fields
        function removeSocialFields(id) {
            const element = document.getElementById(id);
            element.remove();
            // counter--;
        }

        CKEDITOR.replace('description');

        // function previewImage(event, previewId, imageNameId) {
        //     var reader = new FileReader();
        //     reader.onload = function() {
        //         var output = document.getElementById(previewId);
        //         output.src = reader.result;
        //         output.style.display = 'block';
        //     };
        //     reader.readAsDataURL(event.target.files[0]);

        //     var imageName = document.getElementById(imageNameId);
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
             if (!formData.get('role')) {
                document.getElementById('role').nextElementSibling.textContent = 'Role is required.';
                isValid = false;
            }
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
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

            fetch('{{ route('api.teams.store') }}', {
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
                        notify('success', 'teams details created Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('teams.index') }}';
                        }, 1500);
                    } else {
                        notify('error', data.message || 'There was an error creating the teams details.');
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

            if (value.length > 30) {
                this.value = value.slice(0, 30); // stop extra characters
                errorSpan.textContent = 'Title cannot exceed 30 characters.';
            } else if (value.trim() === '') {
                errorSpan.textContent = 'Title is required.';
            } else {
                errorSpan.textContent = '';
            }
        });
          document.getElementById('role').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });

        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });

        document.getElementById('image').addEventListener('change', function() {
            $('#uploadImageBtn').next('.error-message').remove();
        })
        document.getElementById('image').addEventListener('change', function(event) {
            previewImage(event, 'imagePreview', 'imageName');
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
