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
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add Portfolio</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Portfolio Details</h4>
            </div>
            <div class="card-body">
                <form id="heroSectionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="title" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="short_desc" style="font-weight: bold;">Short Desc <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="short_desc" name="short_desc" required>
                    </div>
                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>

                    {{-- <div class="form-group">
                        <label for="image" style="font-weight: bold;">Portfolio Image <span
                                style="color: red;">*</span></label>
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Portfolio Image</button>
                            <input type="file" class="form-control-file" id="image" name="image"
                                accept="image/*" style="display: none;" onchange="previewImage(event)" required>
                            <span id="portfolioImageName"></span>
                            <img id="portfolioImagePreview" src="#" alt="Image Preview"
                                style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        </div>
                    </div> --}}
                    <div class="form-group">
                        <label for="images" style="font-weight: bold;">Portfolio Images <span style="color: red;">*</span></label>
                        <div class="mt-2">
                            {{-- <button type="button" class="btn btn-primary" id="uploadImagesBtn"
                                onclick="document.getElementById('images').click();"
                                style="background-color: #2C9DD4; color: white;">Select Multiple Images</button> --}}
                                <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('images').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            <input type="file" class="form-control-file" id="images" name="images[]" multiple accept="image/*"
                                style="display: none;" onchange="handleMultipleImages(event)">
                            <span id="imagesCount"></span>
                        </div>
                        <div id="imagesPreview" class="mt-3 d-flex flex-wrap" style="gap: 10px;"></div>
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_title_1" style="font-weight: bold;">Portfolio Information Title 1 </label>
                        <input type="text" class="form-control" id="portfolio_info_title_1" name="portfolio_info_title_1">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_1" style="font-weight: bold;">Portfolio Information 1 </label>
                        <input type="text" class="form-control" id="portfolio_info_1" name="portfolio_info_1">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_title_2" style="font-weight: bold;">Portfolio Information Title 2 </label>
                        <input type="text" class="form-control" id="portfolio_info_title_2" name="portfolio_info_title_2">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_2" style="font-weight: bold;">Portfolio Information Value 2</label>
                        <input type="text" class="form-control" id="portfolio_info_2" name="portfolio_info_2">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_title_3" style="font-weight: bold;">Portfolio Information Title 3 </label>
                        <input type="text" class="form-control" id="portfolio_info_title_3" name="portfolio_info_title_3">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_3" style="font-weight: bold;">Portfolio Information 3</label>
                        <input type="text" class="form-control" id="portfolio_info_3" name="portfolio_info_3">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_title_4" style="font-weight: bold;">Portfolio Information Title 4 </label>
                        <input type="text" class="form-control" id="portfolio_info_title_4" name="portfolio_info_title_4">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_4" style="font-weight: bold;">Portfolio Information 4</label>
                        <input type="text" class="form-control" id="portfolio_info_4" name="portfolio_info_4">
                    </div>
                    <div class="form-group">
                        <label for="button_title" style="font-weight: bold;">Button Title </label>
                        <input type="text" class="form-control" id="button_title" name="button_title">
                    </div>
                    <div class="form-group">
                        <label for="button_link" style="font-weight: bold;">Button Link </label>
                        <input type="text" class="form-control" id="button_link" name="button_link">
                    </div>
                    <div class="form-group">
                        <label for="category_id" style="font-weight: bold;">Category <span
                                style="color: red;">*</span></label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('portfolio.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Include common icon picker JS -->
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>

    <script>
        CKEDITOR.replace('description', {
            allowedContent: true,
        });

        //    function previewImage(event, previewId, imageNameId) {
        //         var reader = new FileReader();
        //         reader.onload = function() {
        //             var output = document.getElementById(previewId);
        //             output.src = reader.result;
        //             output.style.display = 'block';
        //         };
        //         reader.readAsDataURL(event.target.files[0]);

        //         var imageName = document.getElementById(imageNameId);
        //         imageName.textContent = event.target.files[0].name;
        //     }

        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('heroSectionForm'));
            formData.set('description', CKEDITOR.instances.description.getData());

            // Add all selected images to formData
            selectedFiles.forEach((file, index) => {
                formData.append('images[]', file);
            });

            // Get status for each image (which images to show in detail page)
            const imageStatusData = selectedFiles.map((file, index) => {
                const isMain = document.querySelector(`.main-switch-toggle[value="${index}"]`)?.checked ? 1 : 0;
                const status = document.querySelector(`input[name="image_status_${index}"]`)?.checked ? 1 : 0;
                return {is_main: isMain, status: status};
            });
            formData.append('image_statuses', JSON.stringify(imageStatusData));

            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            // Validate form (only required fields in this form)
            var isValid = true;
            if (!formData.get('title')) {
                document.getElementById('title').nextElementSibling.textContent = 'Title is required.';
                isValid = false;
            }
             if (!formData.get('short_desc')) {
                document.getElementById('short_desc').nextElementSibling.textContent = 'Short Desc is required.';
                isValid = false;
            }
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }
            // Check if at least one image is selected
            if (selectedFiles.length === 0) {
                $('#uploadImageBtn').after(
                    '<span class="error-message" style="color: red;">At least one image is required.</span>');
                isValid = false;
            }
            if (!formData.get('category_id')) {
                $('#category_id').next('.select2').find('.select2-selection').after(
                    '<span class="error-message" style="color: red;">Category is required.</span>');
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

            // console.log(formData);
            fetch('{{ route('api.portfolio.store') }}', {
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
                        notify('success', 'Portfolio Created Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('portfolio.index') }}';
                        }, 1500);
                    } else {
                        notify('error', data.message || 'There was an error creating the portfolio details.');
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
        $(document).ready(function() {
            $('#category_id').select2({
                placeholder: "Select a Category",
                allowClear: true
            });
        });
        $('#category_id').on('change', function() {
            $(this).next('.select2-container').find('.error-message').remove();
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
        document.getElementById('short_desc').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });

        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });

        // Handle multiple images
        const selectedFiles = [];
        
        function handleMultipleImages(event) {
            const files = event.target.files;
            const previewContainer = document.getElementById('imagesPreview');
            const countSpan = document.getElementById('imagesCount');
            
            // Add new files to existing array
            Array.from(files).forEach((file) => {
                selectedFiles.push(file);
            });
            
            // Clear any existing error messages when images are selected
            $('#uploadImageBtn').next('.error-message').remove();
            
            // Update previews and count
            updatePreviews();
            countSpan.textContent = selectedFiles.length > 0 ? `${selectedFiles.length} image(s) selected` : '';
            
            // Reset the input so you can select more images
            event.target.value = '';
        }
        
        function removeImage(index) {
            selectedFiles.splice(index, 1);
            updatePreviews();
            
            // Update the count display
            const countSpan = document.getElementById('imagesCount');
            countSpan.textContent = selectedFiles.length > 0 ? `${selectedFiles.length} image(s) selected` : '';
            
            // If we removed the main image, set the first remaining image as main
            setTimeout(() => {
                const mainImageRadios = document.querySelectorAll('input[name="main_image_index"]');
                const checkedRadio = document.querySelector('input[name="main_image_index"]:checked');
                if (!checkedRadio && mainImageRadios.length > 0) {
                    mainImageRadios[0].checked = true;
                }
            }, 100);
        }
        
        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            document.getElementById('images').files = dataTransfer.files;
        }
        
        document.getElementById('images').addEventListener('change', function() {
            $('#uploadImageBtn').next('.error-message').remove();
        });
        
        function updatePreviews() {
            const previewContainer = document.getElementById('imagesPreview');
            const countSpan = document.getElementById('imagesCount');
            previewContainer.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'image-preview-item mb-3';
                    previewDiv.setAttribute('data-index', index);
                    previewDiv.style.cssText = 'position: relative; flex: 0 0 auto; border: 1px solid #ddd; border-radius: 8px; padding: 10px; background: #f8f9fa;';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = 'width: 120px; height: 120px; object-fit: cover; border-radius: 4px; display: block; margin-bottom: 10px;';
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn btn-sm btn-danger';
                    removeBtn.style.cssText = 'position: absolute; top: 15px; right: 15px; padding: 2px 6px; font-size: 12px;';
                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    removeBtn.onclick = function() {
                        const currentIndex = parseInt(previewDiv.getAttribute('data-index'));
                        removeImage(currentIndex);
                    };
                    
                    // Controls container
                    const controlsDiv = document.createElement('div');
                    controlsDiv.style.cssText = 'display: flex; flex-direction: column; gap: 8px; margin-top: 8px;';
                    
                    // MAIN IMAGE SWITCH (styled mutually exclusive checkbox)
                    const mainImageDiv = document.createElement('div');
                    mainImageDiv.style.cssText = 'display: flex; align-items: center; gap: 8px;';
                    
                    const mainImageSwitch = document.createElement('input');
                    mainImageSwitch.type = 'checkbox';
                    mainImageSwitch.name = `main_image_switch`;
                    mainImageSwitch.className = 'form-check-input main-switch-toggle';
                    mainImageSwitch.value = index;
                    mainImageSwitch.id = `main_image_switch_${index}`;
                    mainImageSwitch.checked = (index === 0);
                    mainImageSwitch.addEventListener('change', function () {
                        if (this.checked) {
                            document.querySelectorAll('.main-switch-toggle').forEach((el) => {
                                if (el !== this) el.checked = false;
                            });
                        }
                    });
                    
                    const mainImageLabel = document.createElement('label');
                    mainImageLabel.htmlFor = `main_image_switch_${index}`;
                    mainImageLabel.textContent = 'Show on Home Page';
                    mainImageLabel.style.cssText = 'font-size: 12px; font-weight: 600; color: #007bff; margin: 0;';
                    
                    mainImageDiv.appendChild(mainImageSwitch);
                    mainImageDiv.appendChild(mainImageLabel);
                    
                    // Status checkbox
                    const statusDiv = document.createElement('div');
                    statusDiv.style.cssText = 'display: flex; align-items: center; gap: 8px;';
                    
                    const statusCheckbox = document.createElement('input');
                    statusCheckbox.type = 'checkbox';
                    statusCheckbox.name = `image_status_${index}`;
                    statusCheckbox.id = `status_${index}`;
                    statusCheckbox.className = 'form-check-input';
                    statusCheckbox.checked = true; // Active by default
                    
                    const statusLabel = document.createElement('label');
                    statusLabel.htmlFor = `status_${index}`;
                    statusLabel.textContent = 'Show in Detail Page';
                    statusLabel.style.cssText = 'font-size: 12px; font-weight: 600; color: #28a745; margin: 0;';
                    
                    statusDiv.appendChild(statusCheckbox);
                    statusDiv.appendChild(statusLabel);
                    
                    controlsDiv.appendChild(mainImageDiv);
                    controlsDiv.appendChild(statusDiv);
                    
                    previewDiv.appendChild(img);
                    previewDiv.appendChild(removeBtn);
                    previewDiv.appendChild(controlsDiv);
                    previewContainer.appendChild(previewDiv);
                };
                reader.readAsDataURL(file);
            });
            
            countSpan.textContent = selectedFiles.length > 0 ? `${selectedFiles.length} image(s) selected` : '';
            updateFileInput();
        }

        // Removed select2 initializations for non-existent fields
    </script>
@endsection
