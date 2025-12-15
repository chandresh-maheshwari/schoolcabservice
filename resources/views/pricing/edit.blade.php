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
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add pricing</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add pricing Details</h4>
            </div>
            <div class="card-body">
                <form id="heroSectionForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="title" style="font-weight: bold;">Title <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ $pricing->title }}" required>
                    </div>
                    <div class="form-group">
                        <label for="currency" style="font-weight: bold;"> Currency Icon </label>
                        <div class="input-group" style="max-width: 400px;">
                            <span class="input-group-text bg-white" id="currency_icon_preview"
                                style="padding: 0 12px; border-right: 0; min-width: 40px; display: flex; align-items: center; justify-content: center; height: 40px;"></span>
                            <input type="text" class="form-control" id="currency" name="currency"
                                placeholder="Select an icon..." aria-describedby="currency_icon_preview"
                                style="height: 40px;" value="{{ $pricing->currency }}" required>
                            <button type="button" class="btn btn-outline-secondary" role="iconpicker"
                                data-iconset="fontawesome5" data-input="currency" data-preview="currency_icon_preview"
                                style="height: 40px; border-left: 0; margin-top: 0px; border: 1px solid #ced4da;"><i
                                    class="fas fa-icons"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="amount" style="font-weight: bold;">Amount <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="amount" name="amount"
                            oninput="this.value = this.value.replace(/[^0-9./]/g, '').replace(/(\..*)\./g, '$1');" value="{{ $pricing->amount }}" required>
                    </div>
                    <div class="form-group">
                        <label for="period" style="font-weight: bold;">Period <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="period" name="period"
                            value="{{ $pricing->period }}" required>
                    </div>

                    <div class="form-group">
                        <label for="description" style="font-weight: bold;">Description <span
                                style="color: red;">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ $pricing->description }}</textarea>
                    </div>
                    <div style="border: #2C9DD4 1px solid; padding: 10px; border-radius: 5px;">
                        <div class="form-group">
                            <h5 style="font-weight: bold; margin-top: 20px; margin-bottom: 15px; color: #2d336b;">
                                Features Items</h5>
                        </div>
                        <div class="form-group">
                            <label for="feature_title" style="font-weight: bold;">Feature Title <span
                                    style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="feature_title" name="feature_title"
                                value="{{ $pricing->feature_title }}" required>
                        </div>
                        <div class="form-group">
                            <label for="feature_1" style="font-weight: bold;">Feature 1 </label>
                            <input type="text" class="form-control" id="feature_1" name="feature_1"
                                value="{{ $pricing->feature_1 }}">
                        </div>
                        <div class="form-group">
                            <label for="feature_2" style="font-weight: bold;">Feature 2 </label>
                            <input type="text" class="form-control" id="feature_2" name="feature_2"
                                value="{{ $pricing->feature_2 }}">
                        </div>
                        <div class="form-group">
                            <label for="feature_3" style="font-weight: bold;">Feature 3 </label>
                            <input type="text" class="form-control" id="feature_3" name="feature_3"
                                value="{{ $pricing->feature_3 }}">
                        </div>
                        <div class="form-group">
                            <label for="feature_4" style="font-weight: bold;">Feature 4 </label>
                            <input type="text" class="form-control" id="feature_4" name="feature_4"
                                value="{{ $pricing->feature_4 }}">
                        </div>
                        <div class="form-group">
                            <label for="feature_5" style="font-weight: bold;">Feature 5 </label>
                            <input type="text" class="form-control" id="feature_5" name="feature_5"
                                value="{{ $pricing->feature_5 }}">
                        </div>
                        <div class="form-group">
                            <label for="feature_6" style="font-weight: bold;">Feature 6 </label>
                            <input type="text" class="form-control" id="feature_6" name="feature_6"
                                value="{{ $pricing->feature_6 }}">
                        </div>
                    </div>

                    <div class="button_data mt-1" style="border: #2C9DD4 1px solid; padding: 10px; border-radius: 5px;">
                        <div class="form-group">
                            <h5 style="font-weight: bold; margin-top: 20px; margin-bottom: 15px; color: #2d336b;">
                                Button
                                Deatils</h5>
                        </div>
                        <div class="form-group">
                            <label for="button_title" style="font-weight: bold;">Button Title<span
                                    style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="button_title" name="button_title"
                                value="{{ $pricing->button_title }}" required>
                        </div>
                        <div class="form-group">
                            <label for="button_link" style="font-weight: bold;">Button Link<span
                                    style="color: red;">*</span></label>
                            <input type="text" class="form-control" id="button_link" name="button_link"
                                value="{{ $pricing->button_link }}" required>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" id="submitBtn"
                        style="background-color: #2C9DD4; color: white;">Submit</button>
                    <a href="{{ route('pricing.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Include common icon picker JS -->
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>

    <script>
        CKEDITOR.replace('description');

        function previewImage(event, previewId, imageNameId) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById(previewId);
                output.src = reader.result;
                output.style.display = 'block';
            };
            reader.readAsDataURL(event.target.files[0]);

            var imageName = document.getElementById(imageNameId);
            imageName.textContent = event.target.files[0].name;
        }

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
            if (!formData.get('currency')) {
                $('#currency').closest('.form-group').append(
                    '<span class="error-message" style="color: red; display: block; margin-top: 5px;">Currency is required.</span>'
                );
                isValid = false;
            }
            if (!formData.get('amount')) {
                document.getElementById('amount').nextElementSibling.textContent = 'Amount is required.';
                isValid = false;
            }
            if (!formData.get('period')) {
                document.getElementById('period').nextElementSibling.textContent = 'Period is required.';
                isValid = false;
            }
            if (!CKEDITOR.instances.description.getData().trim()) {
                $('#description').next('.cke').after(
                    '<span class="error-message" style="color: red;">Description is required.</span>');
                isValid = false;
            }
            if (!formData.get('feature_title')) {
                document.getElementById('feature_title').nextElementSibling.textContent =
                    'Feature Title is required.';
                isValid = false;
            }
            if (!formData.get('button_title')) {
                document.getElementById('button_title').nextElementSibling.textContent =
                    'Button Title is required.';
                isValid = false;
            }
            // if (!formData.get('button_link')) {
            //     document.getElementById('button_link').nextElementSibling.textContent = 'Button Link is required.';
            //     isValid = false;
            // }

            const fieldData = formData.get('button_link');
            const urlRegex = /^(https?:\/\/[a-zA-Z0-9.-]+\/[^\s]*)$/i;
            if (!fieldData) {
                document.getElementById('button_link').nextElementSibling.textContent = "Link is required";
                isValid = false;
            } else if (!urlRegex.test(fieldData)) {
                document.getElementById('button_link').nextElementSibling.textContent = 'Please enter a valid URL.';
                isValid = false;
            } else {
                document.getElementById('button_link').nextElementSibling.textContent = '';
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

            fetch('{{ route('api.pricing.update', $pricing->id) }}', {
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
                        notify('success', 'Pricing Section Details Updated Successfully!');
                        setTimeout(function() {
                            window.location.href = '{{ route('pricing.index') }}';
                        }, 1500);

                    } else {
                        notify('error', data.message ||
                            'There was an error updating the pricing Section Detail page.');
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

            if (value.length > 20) {
                this.value = value.slice(0, 20); // stop extra characters
                errorSpan.textContent = 'Title cannot exceed 20 characters.';
            } else if (value.trim() === '') {
                errorSpan.textContent = 'Title is required.';
            } else {
                errorSpan.textContent = '';
            }
        });
        document.getElementById('currency').addEventListener('input', function() {
            $(this).closest('.form-group').find('.error-message').remove();
        });
        document.getElementById('amount').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        document.getElementById('period').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        CKEDITOR.instances.description.on('change', function() {
            $('#description').next('.cke').next('.error-message').remove();
        });
        document.getElementById('feature_title').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        document.getElementById('button_title').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });
        document.getElementById('button_link').addEventListener('input', function() {
            this.parentNode.querySelector('.error-message').textContent = '';
        });

        // Removed select2 initializations for non-existent fields
    </script>
@endsection
