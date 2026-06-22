@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')
    @php
        $currentSchoolSlug = request()->route('schoolSlug');
        $childIndexRoute = !empty($isSchoolUser)
            ? route('school.child.index', ['schoolSlug' => $currentSchoolSlug])
            : route('child.index');
        $childUpdateRoute = !empty($isSchoolUser)
            ? route('school.child.update', ['schoolSlug' => $currentSchoolSlug, 'child' => $child->id])
            : route('child.update', $child->id);
        $currentPickupSelection = (string) ($child->pickup_name ?? '');
        $transportOptions = collect($stopPickData ?? [])->map(function ($stop) {
            return [
                'id' => (int) $stop->id,
                'route_id' => (int) $stop->route_id,
                'pickup_name' => (string) ($stop->pickup_name ?? ''),
                'stop_name' => (string) ($stop->stop_name ?? ''),
            ];
        })->values();
        $routePointOptions = $routeData
            ->mapWithKeys(function ($route) {
                $routeJson = is_array($route->route_json ?? null) ? $route->route_json : [];
                $points = collect();

                $appendPoint = function ($point, string $fallbackType) use ($points) {
                    if (!is_array($point)) {
                        return;
                    }

                    $name = trim((string) ($point['name'] ?? $point['address'] ?? ''));
                    if ($name === '') {
                        return;
                    }

                    $points->push([
                        'name' => $name,
                        'type' => strtolower(trim($fallbackType)) ?: 'pickup',
                    ]);
                };

                $appendPoint($routeJson['start_point'] ?? null, 'start');

                foreach ((array) ($routeJson['pickup_points'] ?? []) as $point) {
                    $appendPoint($point, 'pickup');
                }

                $appendPoint($routeJson['end_point'] ?? null, 'end');

                return [
                    (int) $route->id => $points->values()->all(),
                ];
            })
            ->all();
        $transportRouteMap = $transportOptions
            ->filter(function ($item) use ($routePointOptions) {
                $pickupNames = collect($routePointOptions[(int) ($item['route_id'] ?? 0)] ?? [])
                    ->filter(fn ($point) => ($point['type'] ?? '') === 'pickup')
                    ->pluck('name')
                    ->map(fn ($name) => trim((string) $name))
                    ->filter()
                    ->values();

                return $pickupNames->isEmpty() || $pickupNames->contains(trim((string) ($item['pickup_name'] ?? '')));
            })
            ->groupBy('route_id')
            ->map(function ($items) use ($currentPickupSelection) {
                return $items
                    ->groupBy(function ($item) {
                        return strtolower(trim((string) ($item['pickup_name'] ?? ''))) . '|' .
                            strtolower(trim((string) ($item['stop_name'] ?? '')));
                    })
                    ->map(function ($duplicateItems) use ($currentPickupSelection) {
                        $selectedMatch = $duplicateItems->first(function ($item) use ($currentPickupSelection) {
                            return (string) ($item['id'] ?? '') === $currentPickupSelection;
                        });

                        return $selectedMatch ?: $duplicateItems->first();
                    })
                    ->values()
                    ->all();
            })
            ->all();
    @endphp

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item">
                            <a class="breadcrumbLink" href="{{ route('admin_layout.index') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active">
                            Edit Child Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @include('child.partials.module_tabs', [
        'activeTab' => 'child',
        'entityIds' => $moduleEntityIds ?? ['child' => $child->id, 'parent' => $child->parent_id],
    ])

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Edit Child Details</h4>
            </div>

            <div class="card-body">
                <form id="childForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- ================= Child ================= --}}
                    <div class="form-group">
                        <label>Child Name <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="child_name" name="child_name" value="{{ $child->child_name }}">
                    </div>
                    {{-- Parent is linked via the Parents tab (no direct field here). --}}

                    {{-- ================= School ================= --}}
                    <div class="form-group">
                        <label>School Name <span style="color:red;">*</span></label>
                        @if (!empty($isSchoolUser) && !empty($defaultSchoolId))
                            <input type="hidden" name="school_id" id="school_id" value="{{ $defaultSchoolId }}">
                            <input type="text" class="form-control" value="{{ $defaultSchoolName ?? 'School' }}" disabled>
                        @else
                            <select class="form-control" name="school_id" id="school_id">
                                <option value="">Select School Name</option>
                                @foreach ($schoolData as $type)
                                    <option value="{{ $type->id }}" {{ $child->school_id == $type->id ? 'selected' : '' }}>
                                        {{ $type->school_name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    {{-- ================= Route ================= --}}
                    <div class="form-group">
                        <label>Route Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="route_id" id="route_id">
                            <option value="">Select Route Name</option>
                            @foreach ($routeData as $type)
                                <option value="{{ $type->id }}" {{ $child->route_id == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ================= Pickup ================= --}}
                    <div class="form-group">
                        <label>Start Point</label>
                        <input type="text" class="form-control" id="start_point_display" readonly
                            placeholder="Select Route first">
                    </div>

                    <div class="form-group">
                        <label>Pickup Point <span style="color:red;">*</span></label>
                        <select class="form-control" id="pickup_point_select">
                            <option value="">Select Route first</option>
                        </select>
                    </div>

                    <input type="hidden" name="pickup_name" id="pickup_name"
                        value="{{ $child->pickup_name }}">
                    <div class="form-group">
                        <label>Stop Name</label>
                        <input type="text" class="form-control" id="stop_name_display" readonly
                            placeholder="Select Route first">
                        <input type="hidden" name="stop_name" id="stop_name"
                            value="{{ $child->stop_name }}">
                    </div>

                    {{-- ================= Gender ================= --}}
                    <div class="form-group">
                        <label>Gender <span style="color:red;">*</span></label>
                        <div id="genderGroup" style="display:flex; gap:20px; flex-wrap:wrap; margin-top:8px;">
                            <label style="display:flex; align-items:center; gap:6px; margin-bottom:0;">
                                <input type="radio" name="gender" value="Male"
                                    {{ strtolower((string) $child->gender) === 'male' ? 'checked' : '' }}>
                                <span>Male</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:6px; margin-bottom:0;">
                                <input type="radio" name="gender" value="Female"
                                    {{ strtolower((string) $child->gender) === 'female' ? 'checked' : '' }}>
                                <span>Female</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:6px; margin-bottom:0;">
                                <input type="radio" name="gender" value="Other"
                                    {{ strtolower((string) $child->gender) === 'other' ? 'checked' : '' }}>
                                <span>Other</span>
                            </label>
                        </div>
                    </div>

                    {{-- ================= DOB ================= --}}
                    <div class="form-group">
                        <label>Date Of Birth <span style="color:red;">*</span></label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                            value="{{ $child->date_of_birth }}">
                    </div>

                    {{-- ================= Image ================= --}}
                    <div class="form-group">
                        <label>Image <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 636 × 424 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="ImageBtn"
                            onclick="document.getElementById('image').click();">Upload Image</button>
                        <input type="file" id="image" name="image" accept="image/*" style="display:none;"
                            onchange="previewImage(event)">
                        <br>
                        @php
                            $imagePath = $child->image ? public_path('storage/child/' . $child->image) : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $imageUrl = $imageExists
                                ? asset('storage/child/' . $child->image)
                                : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName">
                            {{ $imageExists && !$isDefaultImage ? basename($child->image) : 'No image' }}
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

                    {{-- ================= Adhaar Image ================= --}}
                    <div class="form-group">
                        <label>Child Aadhar Card Image / PDF <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="ImageBtn1"
                            onclick="document.getElementById('child_adhaar_card_image').click();">Upload Image</button>
                        <input type="file" id="child_adhaar_card_image" name="child_adhaar_card_image"
                            accept="image/*,application/pdf" style="display:none;" onchange="previewImage1(event)">
                        <br>
                        @php
                            $imagePath = $child->child_adhaar_card_image
                                ? public_path('storage/child/' . $child->child_adhaar_card_image)
                                : null;
                            $imageExists = $imagePath && File::exists($imagePath);
                            $isPdfFile = $imageExists
                                && strtolower(pathinfo($child->child_adhaar_card_image, PATHINFO_EXTENSION)) === 'pdf';
                            $imageUrl = $imageExists
                                ? ($isPdfFile
                                    ? asset('images/pdf-placeholder.svg')
                                    : asset('storage/child/' . $child->child_adhaar_card_image))
                                : asset('images/Default.jpg');
                            $isDefaultImage = basename($imageUrl) === 'Default.jpg';
                        @endphp
                        <span id="imageName1">
                            {{ $imageExists && !$isDefaultImage ? basename($child->child_adhaar_card_image) : 'No image' }}
                        </span>
                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div">
                        <img id="imagePreview1" src="{{ $imageUrl }}" alt="Image Preview"
                            style="display: block; width: 100px; height: 100px; margin-top: 10px;">
                        {{-- {{basename($imageUrl) !== 'Default.jpg'}} --}}
                        <button type="button" id="removeImageBtn1" class="btn btn-sm"
                            style="display: none; margin-top: 10px; margin-left: 10px;">
                            <i class="fas fa-trash"></i> </button>
                        @if (!$isDefaultImage)
                            <button type="button" id="deleteImageBtn1" class="btn btn-sm"
                                style="margin-top: 10px; margin-left: 10px;">
                                <i class="fas fa-trash"></i> </button>
                        @endif
                    </div>

                    {{-- ================= Class ================= --}}
                    <div class="form-group">
                        <label>Class <span style="color:red;">*</span></label>
                        <input type="number" class="form-control" id="class" name="class" value="{{ $child->class }}">
                    </div>

                    {{-- ================= Section ================= --}}
                    <div class="form-group">
                        <label>Section <span style="color:red;">*</span></label>
                        <input type="text" class="form-control" id="section" name="section" value="{{ $child->section }}">
                    </div>

                    <button type="button" class="btn btn-primary" id="submitBtn">Update</button>
                    <a href="{{ $childIndexRoute }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    {{-- ================= JS ================= --}}
    <script>
        (function () {
        const childEditRoutePoints = @json($routePointOptions);
        const childEditTransportMap = @json($transportRouteMap);
        const childEditCurrentPickup = @json((string) ($child->pickup_name ?? ''));
        const childEditCurrentStop = @json((string) ($child->stop_name ?? ''));
        const childEditInitialRouteId = parseInt(document.getElementById('route_id').value, 10) || 0;
        const routeSelect = document.getElementById('route_id');
        const startPointDisplay = document.getElementById('start_point_display');
        const pickupPointSelect = document.getElementById('pickup_point_select');
        const pickupNameInput = document.getElementById('pickup_name');
        const stopNameDisplay = document.getElementById('stop_name_display');
        const stopNameInput = document.getElementById('stop_name');

        function childEditGetRoutePoints(routeId) {
            const normalizedRouteId = String(parseInt(routeId, 10) || 0);
            return Array.isArray(childEditRoutePoints[normalizedRouteId]) ? childEditRoutePoints[normalizedRouteId] : [];
        }

        function childEditResetTransportFields() {
            startPointDisplay.value = '';
            pickupPointSelect.innerHTML = '<option value="">Select Route first</option>';
            pickupPointSelect.disabled = true;
            stopNameDisplay.value = '';
            pickupNameInput.value = '';
            stopNameInput.value = '';
            startPointDisplay.placeholder = 'Select Route first';
            stopNameDisplay.placeholder = 'Select Route first';
        }

        function childEditSetSelectedPickup() {
            const pickupId = pickupPointSelect.value || '';
            pickupNameInput.value = pickupId;
            stopNameInput.value = pickupId || childEditCurrentStop;
        }

        function childEditRenderTransportDetails(routeId) {
            const normalizedRouteId = parseInt(routeId, 10) || 0;
            const routePoints = childEditGetRoutePoints(normalizedRouteId);
            const transportItems = Array.isArray(childEditTransportMap[String(normalizedRouteId)])
                ? childEditTransportMap[String(normalizedRouteId)]
                : [];
            const shouldUseCurrentValues = normalizedRouteId > 0 && normalizedRouteId === childEditInitialRouteId;
            const pickupTransportId = shouldUseCurrentValues ? childEditCurrentPickup : '';

            childEditResetTransportFields();

            if (normalizedRouteId <= 0) {
                return;
            }

            pickupPointSelect.innerHTML = '<option value="">Select Pickup Point</option>';
            pickupPointSelect.disabled = false;

            transportItems.forEach(item => {
                const pickupName = String(item.pickup_name || '').trim();
                if (!pickupName) {
                    return;
                }

                const option = document.createElement('option');
                option.value = String(item.id || '');
                option.textContent = pickupName;
                option.dataset.stopName = String(item.stop_name || '').trim();
                if (pickupTransportId && String(item.id || '') === String(pickupTransportId)) {
                    option.selected = true;
                }
                pickupPointSelect.appendChild(option);
            });

            if (pickupPointSelect.options.length > 1) {
                if (!pickupPointSelect.value) {
                    pickupPointSelect.selectedIndex = 1;
                }
                childEditSetSelectedPickup();
            } else {
                pickupPointSelect.innerHTML = '<option value="">No pickup points available</option>';
                pickupPointSelect.disabled = true;
                if (shouldUseCurrentValues) {
                    pickupNameInput.value = childEditCurrentPickup;
                    stopNameInput.value = childEditCurrentStop;
                }
            }

            routePoints.forEach(point => {
                const pointType = String(point.type || '').toLowerCase();
                const pointName = String(point.name || '').trim();

                if (!pointName) {
                    return;
                }

                if (pointType === 'start') {
                    startPointDisplay.value = pointName;
                }

                if (pointType === 'end') {
                    stopNameDisplay.value = stopNameDisplay.value || pointName;
                }
            });

            if (!startPointDisplay.value) {
                startPointDisplay.placeholder = 'No start point available';
            }

            if (!stopNameDisplay.value) {
                stopNameDisplay.placeholder = 'No stop point available';
            }
        }

        childEditRenderTransportDetails(routeSelect.value);

        $(document)
            .off('change.childEditTransport', '#route_id')
            .on('change.childEditTransport', '#route_id', function() {
                childEditRenderTransportDetails(this.value);
                $('#stop_name_display, #start_point_display, #pickup_point_select').next('.error-message').remove();
            });

        $(document)
            .off('change.childEditPickup', '#pickup_point_select')
            .on('change.childEditPickup', '#pickup_point_select', function() {
                childEditSetSelectedPickup();
                $('#pickup_point_select, #stop_name_display').next('.error-message').remove();
            });

        $('#submitBtn').on('click', function() {

            let formData = new FormData(document.getElementById('childForm'));
            let isValid = true;
            $('.error-message').remove();

            function showError(el, msg) {
                $(el).after('<span class="error-message" style="color:red;">' + msg + '</span>');
                isValid = false;
            }

            if (!formData.get('child_name')) showError('#child_name', 'Child Name is required');
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
            if (!formData.get('pickup_name')) showError('#pickup_point_select', 'Pickup Point is required');
            if (!stopNameDisplay.value.trim()) showError('#stop_name_display', 'Stop Name is required');
            if (!formData.get('gender')) showError('#genderGroup', 'Gender is required');
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

            fetch('{{ $childUpdateRoute }}', {
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

                        let errorMsg = data.message || 'Something went wrong';

                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join('<br>');
                        }

                        throw errorMsg;
                    }

                    return data;
                })
                .then(data => {
                    notify('success', 'Child updated successfully');
                    setTimeout(() => {
                        window.location.href = '{{ $childIndexRoute }}';
                    }, 1200);
                })
                .catch(error => {
                    notify(
                        'error',
                        typeof error === 'string' ?
                        error :
                        (error.message || 'Unexpected error')
                    );
                });
        });


        document.getElementById('image').addEventListener('change', function() {
            $('#ImageBtn').next('.error-message').remove();
        })

        document.getElementById('child_adhaar_card_image').addEventListener('change', function() {
            $('#ImageBtn1').next('.error-message').remove();
        });

        $(document)
            .off('change.childEdit', 'input[name="gender"]')
            .on('change.childEdit', 'input[name="gender"]', function() {
                $('#genderGroup').next('.error-message').remove();
            });

        const deleteImageBtn = document.getElementById('deleteImageBtn');
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.child.childImage', $child->id) }}',
                    csrfToken: document.querySelector('input[name="_token"]').value,
                    imagePreviewSelector: '#imagePreview',
                    buttonSelector: '#deleteImageBtn',
                    nameSelector: '#imageName',
                    successMessage: 'Image deleted successfully.'
                });
            });
        }
        const deleteImageBtn1 = document.getElementById('deleteImageBtn1');
        if (deleteImageBtn1) {
            deleteImageBtn1.addEventListener('click', function() {
                window.deleteImageWithConfirm({
                    url: '{{ route('api.child.childAdhaarImage', $child->id) }}',
                    csrfToken: document.querySelector('input[name="_token"]').value,
                    imagePreviewSelector: '#imagePreview1',
                    buttonSelector: '#deleteImageBtn1',
                    nameSelector: '#imageName1',
                    successMessage: 'Image deleted successfully.'
                });
            });
        }

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
        })();
    </script>
@endsection
