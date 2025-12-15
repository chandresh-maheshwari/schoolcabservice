{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    {{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="about-us-create-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Update Service</h2>
        </div> --}}
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a
                                class="breadcrumbLink"href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink" href="{{ route('portfolio.index') }}">Service</a>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Update Portfolio</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Update Portfolio Details</h4>
            </div>
            <div class="card-body">
                <form id="heroSectionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="title" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ $portfolio->title }}" required>
                    </div>

                    <div class="form-group">
                        <label for="short_desc" style="font-weight: bold;">Short Desc <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="short_desc" name="short_desc" value="{{ $portfolio->short_desc }}" required>
                    </div>
                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ $portfolio->description }}</textarea>
                    </div>
                    {{-- <div class="form-group">
                        <label for="image" style="font-weight: bold;">Portfolio Image <span
                                style="color: red;">*</span></label>
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Portfolio Image</button>
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)" required>
                            <span
                                id="portfolioImageName">{{ $portfolio->image ? basename($portfolio->image) : 'No image selected' }}</span>
                            <img id="portfolioImagePreview"  src="{{ $portfolio->image ? asset('storage/' . str_replace('storage/', '', $portfolio->image)) : '' }}"  alt="Image Preview"
                                style="display: {{ $portfolio->image ? 'block' : 'none' }}; width: 100px; height: 100px; margin-top: 10px;">
                            @if ($portfolio->image)
                                <button type="button" id="deleteImageBtn" class="btn btn-danger btn-sm" style="margin-top: 10px; margin-left: 10px;">
                                    <i class="fas fa-trash"></i> Delete Image
                                </button>
                            @endif
                        </div>
                    </div> --}}
                    {{-- <div class="form-group">
                        <label for="image" style="font-weight: bold;">Image <span style="color: red;">*</span></label>
                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)">
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('image').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>
                            @php
                                $imagePath = $portfolio->image ? public_path($portfolio->image) : null;
                                $imageExists = $imagePath && File::exists($imagePath);
                                $imageUrl = $imageExists ? asset($portfolio->image) : asset('images/Default.jpg');
                                $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                            @endphp
                            <span id="imageName">
                                {{ $imageExists ? basename($portfolio->image) : 'No image selected' }}
                            </span>
                        </div>
                        <div id="dlt_btn_div" class="dlt_btn_div">
                            @php
                                $imagePath = $portfolio->image ? public_path($portfolio->image) : null;
                                $imageExists = $imagePath && File::exists($imagePath);
                                $imageUrl = $imageExists ? asset($portfolio->image) : asset('images/Default.jpg');
                                $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                            @endphp
                            <img id="imagePreview" src="{{ $imageUrl }}" alt="Image Preview"
                                style="display: block; width: 100px; height: 100px; margin-top: 10px;">
                            {{-- {{basename($imageUrl) !== 'Default.jpg'}} --}
                            <button type="button" id="removeImageBtn" class="btn btn-sm"
                                style="display: none; margin-top: 10px; margin-left: 10px;">
                                <i class="fas fa-trash"></i> </button>
                            @if (!$isDefaultImage)
                                <button type="button" id="deleteImageBtn" class="btn btn-sm"
                                    style="margin-top: 10px; margin-left: 10px;">
                                    <i class="fas fa-trash"></i> </button>
                            @endif
                        </div>
                    </div> --}}
                    <div class="form-group">
                        <label style="font-weight: bold;">Portfolio Images</label>
                        <div class="mt-2">
                            <input type="file" class="form-control-file" id="add_images" name="add_images[]" multiple accept="image/*"
                             style="display: none;" onchange="handleAdditionalImages(event)">
                             
                             {{-- <input type="file" class="form-control-file" id="image" name="image" accept="image/*"
                                style="display: none;" onchange="previewImage(event)"> --}}
                            <button type="button" class="btn btn-primary" id="uploadImageBtn"
                                onclick="document.getElementById('add_images').click();"
                                style="background-color: #2C9DD4; color: white;">Upload Image</button>

                            <span id="addImagesCount"></span>
                    </div>
                        <div id="allImagesPreview" class="d-flex flex-wrap align-items-center" style="gap: 10px; margin-top:10px;">
                            @if($portfolio->images && $portfolio->images->count() > 0)
                                @foreach($portfolio->images as $index => $img)
                                    @php
                                        $imagePath = $img->image_path ? public_path($img->image_path) : null;
                                        $imageExists = $imagePath && File::exists($imagePath);
                                        $imageUrl = $imageExists ? asset($img->image_path) : asset('images/Default.jpg');
                                        $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                                    @endphp
                                    <div class="image-preview-item existing-image-item mb-3" data-id="{{ $img->id }}" data-is-default="{{ $isDefaultImage ? 'true' : 'false' }}" style="position: relative; flex: 0 0 auto; border: 1px solid #ddd; border-radius: 8px; padding: 10px; background: #f8f9fa;">
                                        <img src="{{ $imageUrl }}" alt="Image" 
                                             style="width: 120px; height: 120px; object-fit: cover; border-radius: 4px; display: block; margin-bottom: 10px;"
                                             onerror="this.onerror=null; this.src='{{ asset('images/Default.jpg') }}';">
                                        <button type="button" class="btn btn-sm btn-danger delete-existing-img" 
                                                style="position: absolute; top: 15px; right: 15px; padding: 2px 6px; font-size: 12px;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        
                                        <!-- Controls container -->
                                        <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 8px;">
                                            <!-- Main image switch (radio button behavior) -->
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <input type="checkbox" name="existing_main_image_switch" value="{{ $img->id }}" id="existing_main_{{ $img->id }}" class="form-check-input main-switch-toggle-existing"
                                                    {{ ($img->is_main ?? false) || ($index === 0 && !$portfolio->images->where('is_main', 1)->count()) ? 'checked' : '' }}>
                                                <label for="existing_main_{{ $img->id }}" style="font-size: 12px; font-weight: 600; color: #007bff; margin: 0;">
                                                    Show on Home Page
                                                </label>
                                            </div>
                                            
                                            <!-- Status checkbox -->
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <input type="checkbox" name="existing_status_{{ $img->id }}" 
                                                       id="existing_status_{{ $img->id }}" class="form-check-input"
                                                       {{ ($img->status ?? 1) ? 'checked' : '' }}>
                                                <label for="existing_status_{{ $img->id }}" style="font-size: 12px; font-weight: 600; color: #28a745; margin: 0;">
                                                    Show in Detail Page
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <small>You can upload more images for this portfolio. Each should be at least the required dimensions.</small>
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_title_1" style="font-weight: bold;">Portfolio Information Title 1
                        </label>
                        <input type="text" class="form-control" id="portfolio_info_title_1" name="portfolio_info_title_1"
                            value="{{ $portfolio->portfolio_info_title_1 }}">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_1" style="font-weight: bold;">Portfolio Information 1 </label>
                        <input type="text" class="form-control" id="portfolio_info_1" name="portfolio_info_1"
                            value="{{ $portfolio->portfolio_info_1 }}">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_title_2" style="font-weight: bold;">Portfolio Information Title 2
                        </label>
                        <input type="text" class="form-control" id="portfolio_info_title_2"
                            name="portfolio_info_title_2" value="{{ $portfolio->portfolio_info_title_2 }}">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_2" style="font-weight: bold;">Portfolio Information Value 2</label>
                        <input type="text" class="form-control" id="portfolio_info_2" name="portfolio_info_2"
                            value="{{ $portfolio->portfolio_info_2 }}">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_title_3" style="font-weight: bold;">Portfolio Information Title 3
                        </label>
                        <input type="text" class="form-control" id="portfolio_info_title_3"
                            name="portfolio_info_title_3" value="{{ $portfolio->portfolio_info_title_3 }}">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_3" style="font-weight: bold;">Portfolio Information 3</label>
                        <input type="text" class="form-control" id="portfolio_info_3" name="portfolio_info_3"
                            value="{{ $portfolio->portfolio_info_3 }}">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_title_4" style="font-weight: bold;">Portfolio Information Title 4
                        </label>
                        <input type="text" class="form-control" id="portfolio_info_title_4"
                            name="portfolio_info_title_4" value="{{ $portfolio->portfolio_info_title_4 }}">
                    </div>
                    <div class="form-group">
                        <label for="portfolio_info_4" style="font-weight: bold;">Portfolio Information 4</label>
                        <input type="text" class="form-control" id="portfolio_info_4" name="portfolio_info_4"
                            value="{{ $portfolio->portfolio_info_4 }}">
                    </div>
                    <div class="form-group">
                        <label for="button_title" style="font-weight: bold;">Button Title </label>
                        <input type="text" class="form-control" id="button_title" name="button_title"
                            value="{{ $portfolio->button_title }}">
                    </div>
                    <div class="form-group">
                        <label for="button_link" style="font-weight: bold;">Button Link </label>
                        <input type="text" class="form-control" id="button_link" name="button_link"
                            value="{{ $portfolio->button_link }}">
                    </div>
                    <div class="form-group">
                        <label for="category_id" style="font-weight: bold;">Category <span
                                style="color: red;">*</span></label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ $portfolio->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}
                                </option>
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
    <script src="{{ asset('js/common.js') }}"></script>

    <script>
        CKEDITOR.replace('description', {
            allowedContent: true,
        });

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
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }
            // Check if there are existing images (excluding default images) or new images selected
            const allExistingImages = document.querySelectorAll('.existing-image-item:not([style*="display: none"])');
            const existingNonDefaultImages = Array.from(allExistingImages).filter(img => {
                return img.getAttribute('data-is-default') !== 'true';
            });
            const hasNewImages = additionalFiles.length > 0;
            
            if (existingNonDefaultImages.length === 0 && !hasNewImages) {
                $('#uploadImageBtn').after(
                    '<span class="error-message" style="color: red;">At least one image select.</span>');
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

            // Add additional images
            additionalFiles.forEach((file) => {
                formData.append('add_images[]', file);
            });
            // Add images to delete
            imagesToDelete.forEach((id) => {
                formData.append('delete_images[]', id);
            });

            // On submit:
            // 1. Collect existing images toggle states
            const existingImageUpdates = [];
            document.querySelectorAll('.existing-image-item').forEach((div) => {
              const id = div.getAttribute('data-id');
              if (!id) return;
              const isMain = div.querySelector('.main-switch-toggle-existing')?.checked ? 1 : 0;
              const status = div.querySelector('input[name^="existing_status_"]')?.checked ? 1 : 0;
              existingImageUpdates.push({id, is_main:isMain, status});
            });
            formData.append('existing_image_updates', JSON.stringify(existingImageUpdates));

            // 2. For new images
            const newImageStatusData = additionalFiles.map((file, index) => {
              const isMain = document.querySelector(`.main-switch-toggle-new[value="${index}"]`)?.checked ? 1 : 0;
              const status = document.querySelector(`input[name="new_image_status_${index}"]`)?.checked ? 1 : 0;
              return {is_main: isMain, status: status};
            });
            formData.append('new_image_statuses', JSON.stringify(newImageStatusData));

            fetch(`{{ route('api.portfolio.update', $portfolio->id) }}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'X-HTTP-Method-Override': 'PUT',
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    notify('success', 'Portfolio Updated Successfully!');
                    setTimeout(function() {
                        window.location.href = '{{ route('portfolio.index') }}';
                    }, 1500);
                } else {
                    notify('error', data.message || 'There was an error updating the portfolio Section Detail page.');
                }
            })
            .catch(error => {
                Swal.close();
                notify('error', 'An unexpected error occurred.');
            });
        });

        // Update error message spans for regular inputs
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
            // Add event listeners for existing switches
            document.querySelectorAll('.main-switch-toggle-existing').forEach((el) => {
                el.addEventListener('change', function () {
                    if (this.checked) {
                        document.querySelectorAll('.main-switch-toggle-existing').forEach((other) => { if (other !== this) other.checked = false; });
                        document.querySelectorAll('.main-switch-toggle-new').forEach((other) => { other.checked = false; });
                    }
                });
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
        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });

        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });

        // document.getElementById('image').addEventListener('change', function() {
        //     $('#uploadImageBtn').next('.error-message').remove();
        // })
        // document.getElementById('image').addEventListener('change', function(event) {
        //     previewImage(event, 'imagePreview', 'imageName');
        // });

        /* Previous inline delete handler replaced by common helper */
        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.portfolio.deleteImage', $portfolio->id) }}',
                    csrfToken: document.querySelector('input[name="_token"]').value,
                    imagePreviewSelector: '#imagePreview',
                    buttonSelector: '#deleteImageBtn',
                    nameSelector: '#imageName',
                    successMessage: 'Image deleted successfully.'
                });
            });
        }
        // document.getElementById('removeImageBtn').addEventListener('click', function() {
        //     window.clearImageSelection({
        //         imagePreviewSelector: '#imagePreview',
        //         imageNameSelector: '#imageName',
        //         imageInputSelector: '#image',
        //         removeImageBtnSelector: '#removeImageBtn'
        //     });
        // });
        // Removed select2 initializations for non-existent fields
        
        // Handle additional images for edit form
        const additionalFiles = [];
        const imagesToDelete = [];
        
        // Add a new function to update all image previews in the same container
        function updateAllImagesPreview() {
            const previewContainer = document.getElementById('allImagesPreview');
            const countSpan = document.getElementById('addImagesCount');
            // Remove all current new previews (keep only .existing-image-item unless hidden)
            previewContainer.querySelectorAll('.new-image-preview-item').forEach(x => x.remove());
            
            // Add new images being uploaded
            additionalFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'image-preview-item new-image-preview-item mb-3';
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
                        additionalFiles.splice(index, 1);
                        updateAllImagesPreview();
                    };
                    
                    // Controls container
                    const controlsDiv = document.createElement('div');
                    controlsDiv.style.cssText = 'display: flex; flex-direction: column; gap: 8px; margin-top: 8px;';
                    
                    // Main image switch (radio button behavior) - only for new images if no existing main image
                    const mainImageDiv = document.createElement('div');
                    mainImageDiv.style.cssText = 'display: flex; align-items: center; gap: 8px;';
                    
                    const mainImageSwitch = document.createElement('input');
                    mainImageSwitch.type = 'checkbox';
                    mainImageSwitch.name = 'new_main_image_switch';
                    mainImageSwitch.value = index;
                    mainImageSwitch.id = `new_main_image_switch_${index}`;
                    mainImageSwitch.className = 'form-check-input main-switch-toggle-new';
                    mainImageSwitch.checked = (!document.querySelector('.main-switch-toggle-existing:checked') && index === 0);
                    mainImageSwitch.addEventListener('change', function () {
                        if (this.checked) {
                            document.querySelectorAll('.main-switch-toggle-existing').forEach((el) => { el.checked = false; });
                            document.querySelectorAll('.main-switch-toggle-new').forEach((el) => { if (el !== this) el.checked = false; });
                        }
                    });
                    
                    const mainImageLabel = document.createElement('label');
                    mainImageLabel.htmlFor = `new_main_image_switch_${index}`;
                    mainImageLabel.textContent = 'Show on Home Page';
                    mainImageLabel.style.cssText = 'font-size: 12px; font-weight: 600; color: #007bff; margin: 0;';
                    
                    mainImageDiv.appendChild(mainImageSwitch);
                    mainImageDiv.appendChild(mainImageLabel);
                    
                    // Status checkbox
                    const statusDiv = document.createElement('div');
                    statusDiv.style.cssText = 'display: flex; align-items: center; gap: 8px;';
                    
                    const statusCheckbox = document.createElement('input');
                    statusCheckbox.type = 'checkbox';
                    statusCheckbox.name = `new_image_status_${index}`;
                    statusCheckbox.id = `new_status_${index}`;
                    statusCheckbox.className = 'form-check-input';
                    statusCheckbox.checked = true; // Active by default
                    
                    const statusLabel = document.createElement('label');
                    statusLabel.htmlFor = `new_status_${index}`;
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
            
            countSpan.textContent = additionalFiles.length > 0 ? `${additionalFiles.length} new image(s) selected` : '';
        }
        // Update: handleAdditionalImages function should call updateAllImagesPreview
        function handleAdditionalImages(event) {
            const files = event.target.files;
            Array.from(files).forEach((file) => {
                additionalFiles.push(file);
            });
            
            // Clear any existing error messages when images are selected
            $('#uploadImageBtn').next('.error-message').remove();
            
            updateAllImagesPreview();
            event.target.value = '';
        }
        
        // Handle delete existing images
        document.querySelectorAll('.delete-existing-img').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const imageDiv = this.closest('.existing-image-item');
                const imageId = imageDiv.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this image?')) {
                    imagesToDelete.push(imageId);
                    imageDiv.style.display = 'none';
                    
                    // If this was the main image, select another one
                    const mainRadio = imageDiv.querySelector('input[name="existing_main_image_switch"]');
                    if (mainRadio && mainRadio.checked) {
                        // Try to select the first visible existing image
                        const firstVisibleExisting = document.querySelector('.existing-image-item:not([style*="display: none"]) input[name="existing_main_image_switch"]');
                        if (firstVisibleExisting) {
                            firstVisibleExisting.checked = true;
                        } else {
                            // If no existing images, select first new image
                            const firstNewImage = document.querySelector('input[name="new_main_image_switch"]');
                            if (firstNewImage) {
                                firstNewImage.checked = true;
                            }
                        }
                    }
                }
            });
        });

        // Handle radio button behavior between existing and new images
        document.addEventListener('change', function(e) {
            if (e.target.name === 'existing_main_image_switch') {
                // Uncheck all new image radio buttons
                document.querySelectorAll('input[name="new_main_image_switch"]').forEach(radio => {
                    radio.checked = false;
                });
            } else if (e.target.name === 'new_main_image_switch') {
                // Uncheck all existing image radio buttons
                document.querySelectorAll('input[name="existing_main_image_switch"]').forEach(radio => {
                    radio.checked = false;
                });
            }
        });
        
        // Update submit button to include additional images and delete list
        // document.getElementById('submitBtn').addEventListener('click', function() {
        //     var formData = new FormData(document.getElementById('heroSectionForm'));
        //     formData.set('description', CKEDITOR.instances.description.getData());
            
        //     // Add additional images
        //     additionalFiles.forEach((file) => {
        //         formData.append('add_images[]', file);
        //     });
            
        //     // Add images to delete
        //     imagesToDelete.forEach((id) => {
        //         formData.append('delete_images[]', id);
        //     });
        // }, true);
    </script>
@endsection
