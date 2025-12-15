{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')


@section('content')
    {{-- <div class="container-fluid" style="width: 101%; padding-right: 7px; padding-left: 0px; margin-right: auto; margin-left: auto; margin-top: 9px;">
    <div class="card" style="background-color: #f8f9fa; border-color: #e3e6f0;">
        <div class="card-header" style="background-color: #a9b5df; color: white; padding: 10px 15px 1px;">
            <h2 class="category-edit-header" style="text-align: center; font-size: 1.5em; margin-bottom: 6px; font-weight: bold; color: #2d336b;">Edit Category</h2>
        </div> --}}
    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a class="breadcrumbLink"
                                href="{{ route('categories.index') }}">Categories</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Edit Category</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="category-edit-header">Edit Category</h4>
            </div>
            <div class="card-body">
                <form id="categoryEditForm">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="name" style="font-weight: bold;">Category Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="{{ $category->name }}" required>
                        <div id="name-error-message" style="color: red;"></div>
                    </div>
                    <div class="form-group">
                        <label for="category_link" style="font-weight: bold;">Category Link <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="category_link" name="category_link"
                            value="{{ $category->category_link }}" required>
                        <div id="category-link-error-message" style="color: red;"></div>
                    </div>
                    <div class="form-group">
                        <label for="category_icon" style="font-weight: bold;">Category Icon <span
                                style="color: red;">*</span></label>
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text bg-white" id="icon-preview"
                                style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                            <input type="text" class="form-control" id="category_icon" name="category_icon"
                                value="{{ $category->category_icon }}" required placeholder="Select an icon..."
                                aria-describedby="icon-preview" style="height: 40px;">
                            <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                data-iconset="fontawesome5"
                                style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                    class="fas fa-icons"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="status" style="font-weight: bold;">Status <span style="color: red;">*</span></label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="" disabled selected>Please select</option>
                            <option value="1" {{ $category->status ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !$category->status ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <div id="status-error-message" style="color: red;"></div>
                    </div>
                    <div class="form-group">
                        <label for="order" style="font-weight: bold;">Order <span style="color: red;">*</span></label>
                        <input type="number" class="form-control" id="order" name="order"
                            value="{{ $category->order }}" required min="1">
                    </div>
                    <button type="button" class="btn btn-primary" id="updateBtn"
                        style="background-color: #2d336b; color: white;">Update</button>
                    <a href="{{ route('categories.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>


    <script>
        // Add empty error spans after each form control (if not already present)
        

        document.getElementById('updateBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('categoryEditForm'));

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(function(el) {
                el.textContent = '';
            });

            // Form validation
            let isValid = true;

            var nameInput = document.getElementById('name');
            var categoryLinkInput = document.getElementById('category_link');
            var statusInput = document.getElementById('status');
            var categoryIconInput = document.getElementById('category_icon');
            var orderInput = document.getElementById('order');

            if (!formData.get('name') || !nameInput.value.trim()) {
                nameInput.nextElementSibling.textContent = 'Category Name is required.';
                isValid = false;
            }

            if (!formData.get('category_link') || !categoryLinkInput.value.trim()) {
                categoryLinkInput.nextElementSibling.textContent = 'Category Link is required.';
                isValid = false;
            }

            if (!formData.get('status')) {
                $('#status').parent().find('.error-message').remove();
                $('#status').parent().append(
                    '<span class="error-message" style="color: red;">Status is required.</span>');
                isValid = false;
            }

            if (!formData.get('category_icon') || !categoryIconInput.value.trim()) {
                categoryIconInput.nextElementSibling.textContent = 'Category Icon is required.';
                isValid = false;
            }

            if (!formData.get('order') || !orderInput.value.trim()) {
                orderInput.nextElementSibling.textContent = 'Order is required.';
                isValid = false;
            }

             // Add error span for order input
        if (orderInput && !orderInput.classList.contains('select2-hidden-accessible') && !orderInput.nextElementSibling
            ?.classList?.contains('error-message')) {
            // var errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.style.color = 'red';
            orderInput.parentNode.insertBefore(errorSpan, orderInput.nextSibling);
        }
        
            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            fetch('{{ route('api.categories.update', $category->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'X-HTTP-Method-Override': 'PUT'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.close();
                        notify('success', 'Category updated Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('categories.index') }}';
                        }, 1500);
                    } else {
                        notify('error', data.message || 'There was an error updating the category.');
                    }
                })
                .catch(error => {
                    Swal.close();
                    notify('error', 'An unexpected error occurred.');
                });
        });

        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible') && !input.nextElementSibling?.classList
                ?.contains('error-message')) {
                var errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.parentNode.insertBefore(errorSpan, input.nextSibling);
            }
        });

        // Clear error on input
        // document.getElementById('name').addEventListener('input', function() {
        //     this.nextElementSibling.textContent = '';
        // });
        const titleInput = document.getElementById('name');
        const errorSpan = titleInput.parentNode.querySelector('.error-message');

        titleInput.addEventListener('input', function() {
            let value = this.value;

            if (value.length > 20) {
                this.value = value.slice(0, 20); // stop extra characters
                errorSpan.textContent = 'Category name cannot exceed 20 characters.';
            } else if (value.trim() === '') {
                errorSpan.textContent = 'Category name is required.';
            } else {
                errorSpan.textContent = '';
            }
        });

        document.getElementById('category_link').addEventListener('input', function() {
            this.nextElementSibling.textContent = '';
        });
        $('#category_link, #category_icon, #order').on('input', function() {
            $(this).next('.error-message').text('');
        });
        $('#status').on('change', function() {
            $(this).parent().find('.error-message').remove();
        });

        $(document).ready(function() {
            $('#status').select2({
                placeholder: "Select a Status",
                allowClear: true
            });

            // Initialize icon picker
            $('[role="iconpicker"]').iconpicker({
                iconset: 'fontawesome5',
                input: '#category_icon',
            });

            // Update icon preview on icon select and on input change
            function updateIconPreview(iconClass) {
                var preview = document.getElementById('icon-preview');
                preview.innerHTML = iconClass ? '<i class="' + iconClass + '"></i>' : '';
            }
            // On icon picker select
            $('[role="iconpicker"]').on('iconpickerSelected', function(e) {
                $('#category_icon').val(e.iconpickerValue);
                $('#category_icon')[0].dispatchEvent(new Event('input'));
                updateIconPreview(e.iconpickerValue);
            });
            // On manual input change
            $('#category_icon').on('input', function() {
                updateIconPreview(this.value);
            });
            // Initialize preview if value exists
            updateIconPreview($('#category_icon').val());
            $('[role="iconpicker"]').on('click', function() {
                setTimeout(function() {
                    var $popover = $('.iconpicker-popover.popover.fade.bottom');
                    if ($popover.is(':visible')) {
                        $popover.css('display', 'none');
                    } else {
                        $popover.css('display', 'block');
                    }
                }, 10);
            });
        });

       
    </script>
@endsection
