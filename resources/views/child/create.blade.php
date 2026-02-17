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
                            Add Child Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Child Details</h4>
            </div>

            <div class="card-body">
                <form id="childForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Child Name<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="child_name" name="child_name" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Parent Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="parent_id" id="parent_id">
                            <option value="">Select Parent Name</option>
                            @foreach ($parents as $type)
                                <option value="{{ $type->id }}">
                                    {{ $type->parent_id }}
                                    @if (!empty($type->father_name))
                                        {{ $type->father_name }}
                                    @endif
                                </option>
                            @endforeach

                        </select>
                    </div>
                    <div class="form-group">
                        <label>School Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="school_id" id="school_id">
                            <option value="">Select School Name</option>
                            @foreach ($schoolData as $type)
                                <option value="{{ $type->id }}">
                                    {{ $type->school_id }}
                                    @if (!empty($type->school_name))
                                        {{ $type->school_name }}
                                    @endif
                                </option>
                            @endforeach

                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pickup Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="pickup_name" id="pickup_name">
                            <option value="">Select Pickup Name</option>
                            @foreach ($stopPickData as $type)
                                <option value="{{ $type->id }}">
                                    {{ $type->pickup_name }}
                                </option>
                            @endforeach

                        </select>
                    </div>
                    <div class="form-group">
                        <label>Stop Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="stop_name" id="stop_name">
                            <option value="">Select Stop Name</option>
                            @foreach ($stopPickData as $type)
                                <option value =
                                    "{{ $type->id }}">
                                    {{ $type->stop_name }}

                                </option>
                            @endforeach

                        </select>
                    </div>
                    <div class="form-group">
                        <label>Route Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="route_id" id="route_id">
                            <option value="">Select Stop Name</option>
                            @foreach ($routeData as $type)
                                <option value="{{ $type->id }}">
                                    {{ $type->name }}
                                </option>
                            @endforeach

                        </select>
                    </div>
                    <div class="form-group">
                        <label>Gender<span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="gender" name="gender" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Date Of Birth <span style="color:red;">*</span></label>
                        <input type="date" class="form-control" name="date_of_birth" id="date_of_birth">
                    </div>
                    <div class="form-group">
                        <label> Image <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 636 × 424 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="ImageBtn"
                            onclick="document.getElementById('image').click();"> Upload Image</button>
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
                        <label> Child Aadhar Card Image <span style="color:red;">*</span> <small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="ImageBtn1"
                            onclick="document.getElementById('child_adhaar_card_image').click();"> Upload Image</button>
                        <input type="file" id="child_adhaar_card_image" name="child_adhaar_card_image"
                            accept="image/*" style="display:none;" onchange="previewImage1(event)">
                        <span id="imageName1"></span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview1" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn1"><i
                                class="fas fa-trash"></i></button>
                    </div>

                    <div class="form-group">
                        <label>Class <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="class" name="class" required
                            autocomplete="off" oninput="this.value = this.value < 1 ? '' : this.value">
                    </div>
                    <div class="form-group">
                        <label>Section <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="section" name="section" autocomplete="off">
                    </div>
                    <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                    <a href="{{ route('child.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- JS --}}
    <script>
        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('childForm'));
            let isValid = true;

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('child_name')) showError('#child_name', 'Child Name is required');
            if (!formData.get('parent_id')) showError('#parent_id', 'Parent Name  is required');
            let schoolSelect = document.getElementById('school_id');
            let schoolValue = schoolSelect.value;

            if (schoolValue === "") {
                $('#school_id').after(
                    '<span class="error-message" style="color:red;">School Name is required</span>'
                );
                isValid = false;
            }
            let routeSelect = document.getElementById('route_id');
            let routeValue = routeSelect.value;

            if (routeValue === "") {
                $('#route_id').after(
                    '<span class="error-message" style="color:red;">Route Name is required</span>'
                );
                isValid = false;
            }
            if (!formData.get('pickup_name')) showError('#pickup_name', 'Pickup Name is required');
            if (!formData.get('stop_name')) showError('#stop_name', 'Stop Name is required');
            if (!formData.get('gender')) showError('#gender', 'Gender is required');
            if (!formData.get('date_of_birth')) showError('#date_of_birth',
                ' Date Of Birth is required');
            if (!formData.get('class')) showError('#class', ' Class is required');
            if (!formData.get('section')) showError('#section',
                'Section is required');



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
                    '<span class="error-message" style="color: red;"> Image is required.</span>');
                isValid = false;
            }
            var imageInput1 = document.getElementById('child_adhaar_card_image');
            var imagePreview1 = document.getElementById('imagePreview1');
            var imageError1 = document.getElementById('imageError');
            var currentImageSrc1 = imagePreview1.getAttribute('src');
            var isDefaultImage1 = currentImageSrc1.includes('Default.jpg');
            // console.log(!imageInput.files.length && isDefaultImage);
            if (!imageInput1.files.length && isDefaultImage1 || (currentImageSrc1 == "#" || currentImageSrc1 ==
                    "")) {
                // if (!imageInput.files.length && isDefaultImage) {
                // if (!formData.get('image') || !formData.get('image').name) {
                $('#ImageBtn1').after(
                    '<span class="error-message" style="color: red;">Child Adhaar Card Image is required.</span>'
                );
                isValid = false;
            }

            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.child.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {

                    let data;

                    // 🔹 Safe JSON parsing
                    try {
                        data = await res.json();
                    } catch (e) {
                        throw 'Invalid server response';
                    }

                    // 🔹 Backend / HTTP error
                    if (!res.ok || data.success === false) {

                        let errorMsg = data.message || 'Something went wrong';

                        // Laravel validation errors support
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join('<br>');
                        }

                        throw errorMsg; // 👈 REAL MESSAGE
                    }

                    return data;
                })
                .then(data => {
                    Swal.close();

                    notify('success', 'Child created successfully!');
                    setTimeout(() => {
                        window.location.href = '{{ route('child.index') }}';
                    }, 1500);
                })
                .catch(error => {
                    Swal.close();

                    // 🔥 EXACT ERROR MESSAGE TO TOASTER
                    notify(
                        'error',
                        typeof error === 'string' ?
                        error :
                        (error.message || 'An unexpected error occurred.')
                    );
                });

        });

        /* REAL-TIME ERROR REMOVE */
        $(document).on('input change', 'input, select', function() {
            $(this).next('.error-message').remove();
        });

        $(document).on('change', '#parent_id', function() {
            $(this).next('.error-message').remove();
        });
        $(document).on('change', '#school_id', function() {
            $(this).next('.error-message').remove();
        });
        $(document).on('change', '#route_id', function() {
            $(this).next('.error-message').remove();
        });

        document.getElementById('image').addEventListener('change', function() {
            $('#ImageBtn').next('.error-message').remove();
        })

        document.getElementById('child_adhaar_card_image').addEventListener('change', function() {
            $('#ImageBtn1').next('.error-message').remove();
        });


        function isPastDate(selectedDate) {
            const today = new Date();
            today.setHours(0, 0, 0, 0); // remove time

            const inputDate = new Date(selectedDate);
            return inputDate < today;
        }


        const allowedRegex = /^[a-zA-Z0-9]+$/;

        document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });

        document.getElementById('removeImageBtn1').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview1',
                imageNameSelector: '#imageName1',
                imageInputSelector: '#child_adhaar_card_image',
                removeImageBtnSelector: '#removeImageBtn1'
            });
        });
    </script>
@endsection
