@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

    @php
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $schoolSlug = request()->route('schoolSlug');
        $isSchoolPanel = filled($schoolSlug) && is_string($routeName) && str_starts_with($routeName, 'school.');
        $parentCreateUrl = $isSchoolPanel
            ? route('school.parent.create', ['schoolSlug' => $schoolSlug])
            : route('parent.create');
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
            ->groupBy('route_id')
            ->map(function ($items) {
                return $items
                    ->groupBy(function ($item) {
                        return strtolower(trim((string) ($item['pickup_name'] ?? ''))) . '|' .
                            strtolower(trim((string) ($item['stop_name'] ?? '')));
                    })
                    ->map(function ($duplicateItems) {
                        return $duplicateItems->first();
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
                            Add Child Detail
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @include('child.partials.module_tabs', [
        'activeTab' => 'child',
        'entityIds' => [],
    ])

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
                    {{-- Parent will be created/linked via the Parents tab after saving this Child. --}}
                    <div class="form-group">
                        <label>School Name <span style="color:red;">*</span></label>
                        @if (!empty($isSchoolUser) && !empty($defaultSchoolId))
                            <input type="hidden" name="school_id" id="school_id" value="{{ $defaultSchoolId }}">
                            <input type="text" class="form-control" value="{{ $defaultSchoolName ?? 'School' }}" disabled>
                        @else
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
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Route Name <span style="color:red;">*</span></label>
                        <select class="form-control" name="route_id" id="route_id">
                            <option value="">Select Route Name</option>
                            @foreach ($routeData as $type)
                                <option value="{{ $type->id }}">
                                    {{ $type->name }}
                                </option>
                            @endforeach

                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Point</label>
                        <input type="text" class="form-control" id="start_point_display" readonly
                            placeholder="Select Route first">
                    </div>
                    <div class="form-group">
                        <label>Pickup Point <span style="color:red;">*</span></label>
                        <select class="form-control" name="pickup_name" id="pickup_point_select">
                            <option value="">Select Route first</option>
                        </select>
                    </div>
                    <input type="hidden" id="pickup_name">
                    <div class="form-group">
                        <label>Stop Name</label>
                        <input type="text" class="form-control" id="stop_name_display" readonly
                            placeholder="Select Route first">
                        <input type="hidden" name="stop_name" id="stop_name">
                    </div>
                    <div class="form-group">
                        <label>Gender<span style="color:red;">*</span></label>
                        <div id="genderGroup" style="display:flex; gap:20px; flex-wrap:wrap; margin-top:8px;">
                            <label style="display:flex; align-items:center; gap:6px; margin-bottom:0;">
                                <input type="radio" name="gender" value="Male">
                                <span>Male</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:6px; margin-bottom:0;">
                                <input type="radio" name="gender" value="Female">
                                <span>Female</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:6px; margin-bottom:0;">
                                <input type="radio" name="gender" value="Other">
                                <span>Other</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Date Of Birth <span style="color:red;">*</span></label>
                        <input type="date" class="form-control" name="date_of_birth" id="date_of_birth" max="{{ now()->toDateString() }}">
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
                        <label> Child Aadhar Card Image / PDF <span style="color:red;">*</span> <small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="ImageBtn1"
                            onclick="document.getElementById('child_adhaar_card_image').click();"> Upload Image</button>
                        <input type="file" id="child_adhaar_card_image" name="child_adhaar_card_image"
                            accept="image/*,application/pdf" style="display:none;" onchange="previewImage1(event)">
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
        (function () {
        const childCreateRoutePoints = @json($routePointOptions);
        const childCreateTransportMap = @json($transportRouteMap);
        const routeSelect = document.getElementById('route_id');
        const startPointDisplay = document.getElementById('start_point_display');
        const pickupPointSelect = document.getElementById('pickup_point_select');
        const pickupNameInput = document.getElementById('pickup_name');
        const stopNameDisplay = document.getElementById('stop_name_display');
        const stopNameInput = document.getElementById('stop_name');
        const childForm = document.getElementById('childForm');

        function getChildDraftState() {
            if (typeof window.__childModuleGetDraftState === 'function') {
                return window.__childModuleGetDraftState() || {};
            }

            return {};
        }

        function getChildSpecialState() {
            try {
                const raw = sessionStorage.getItem('childModuleChildSpecial');
                return raw ? (JSON.parse(raw) || {}) : {};
            } catch (e) {
                return {};
            }
        }

        function patchChildDraftState(patch) {
            if (typeof window.__childModulePatchDraftState === 'function') {
                window.__childModulePatchDraftState(patch || {});
            }
        }

        function patchChildSpecialState(patch) {
            if (!patch || typeof patch !== 'object') {
                return;
            }

            try {
                const nextState = Object.assign({}, getChildSpecialState(), patch);
                sessionStorage.setItem('childModuleChildSpecial', JSON.stringify(nextState));
            } catch (e) {}
        }

        function persistExistingChildPreview(prefix) {
            const preview = document.getElementById(prefix === 'child_main' ? 'imagePreview' : 'imagePreview1');
            const imageName = document.getElementById(prefix === 'child_main' ? 'imageName' : 'imageName1');
            const wrapper = preview ? preview.parentElement : null;
            const imageUrl = preview ? String(preview.getAttribute('src') || '') : '';
            const visible = preview ? preview.style.display !== 'none' : (wrapper ? wrapper.style.display !== 'none' : false);
            const payload = {
                [`${prefix}_image_name_preview`]: imageName ? String(imageName.textContent || '') : '',
                [`${prefix}_image_url_preview`]: imageUrl && imageUrl !== '#' ? imageUrl : '',
                [`${prefix}_image_visible_preview`]: visible ? '1' : '0',
            };

            patchChildDraftState(payload);
            patchChildSpecialState(payload);
        }

        function serializeChildPreview(prefix, file) {
            return new Promise((resolve) => {
                if (!file) {
                    persistExistingChildPreview(prefix);
                    resolve();
                    return;
                }

                const reader = new FileReader();
                reader.onload = function () {
                    const isPdf = file.type === 'application/pdf' || String(file.name || '').toLowerCase().endsWith('.pdf');
                    const previewUrl = isPdf ? (window.pdfPreviewPlaceholder || '') : String(reader.result || '');
                    patchChildDraftState({
                        [`${prefix}_image_name_preview`]: String(file.name || ''),
                        [`${prefix}_image_url_preview`]: previewUrl,
                        [`${prefix}_image_visible_preview`]: '1',
                        [`${prefix}_file_data`]: String(reader.result || ''),
                        [`${prefix}_file_mime`]: String(file.type || ''),
                    });
                    patchChildSpecialState({
                        [`${prefix}_image_name_preview`]: String(file.name || ''),
                        [`${prefix}_image_url_preview`]: previewUrl,
                        [`${prefix}_image_visible_preview`]: '1',
                        [`${prefix}_file_data`]: String(reader.result || ''),
                        [`${prefix}_file_mime`]: String(file.type || ''),
                    });
                    resolve();
                };
                reader.onerror = function () {
                    resolve();
                };
                reader.readAsDataURL(file);
            });
        }

        function persistChildPreview(prefix, file) {
            serializeChildPreview(prefix, file);
        }

        function getChildFileDraft(prefix) {
            const draft = Object.assign({}, getChildSpecialState(), getChildDraftState());
            return {
                fileData: String(draft[`${prefix}_file_data`] || '').trim(),
                fileMime: String(draft[`${prefix}_file_mime`] || '').trim(),
                fileName: String(draft[`${prefix}_image_name_preview`] || '').trim(),
                isVisible: String(draft[`${prefix}_image_visible_preview`] || '') === '1',
            };
        }

        function hasChildFileSelection(prefix, inputId) {
            const input = document.getElementById(inputId);
            if (input && input.files && input.files.length) {
                return true;
            }

            const fileDraft = getChildFileDraft(prefix);
            return !!(fileDraft.isVisible && fileDraft.fileData && fileDraft.fileName);
        }

        function dataUrlToFile(dataUrl, fileName, mimeType) {
            const parts = String(dataUrl || '').split(',');
            if (parts.length < 2) {
                return null;
            }

            const match = parts[0].match(/data:(.*?);base64/);
            const detectedMime = mimeType || (match && match[1]) || 'application/octet-stream';
            const binary = atob(parts[1]);
            const len = binary.length;
            const bytes = new Uint8Array(len);

            for (let i = 0; i < len; i += 1) {
                bytes[i] = binary.charCodeAt(i);
            }

            return new File([bytes], fileName || 'draft-upload', { type: detectedMime });
        }

        function appendDraftFileToFormData(formData, prefix, fieldName, inputId) {
            const input = document.getElementById(inputId);
            if (input && input.files && input.files.length) {
                return;
            }

            const fileDraft = getChildFileDraft(prefix);
            if (!fileDraft.isVisible || !fileDraft.fileData || !fileDraft.fileName) {
                return;
            }

            const restoredFile = dataUrlToFile(fileDraft.fileData, fileDraft.fileName, fileDraft.fileMime);
            if (!restoredFile) {
                return;
            }

            formData.set(fieldName, restoredFile, fileDraft.fileName);
        }

        function restoreChildPreview(prefix, previewId, nameId) {
            const draft = Object.assign({}, getChildSpecialState(), getChildDraftState());
            const imageName = String(draft[`${prefix}_image_name_preview`] || '').trim();
            const imageUrl = String(draft[`${prefix}_image_url_preview`] || '').trim();
            const isVisible = String(draft[`${prefix}_image_visible_preview`] || '') === '1';
            const preview = document.getElementById(previewId);
            const nameNode = document.getElementById(nameId);
            const removeBtn = document.getElementById(prefix === 'child_main' ? 'removeImageBtn' : 'removeImageBtn1');
            const wrapper = preview ? preview.parentElement : null;

            if (!preview || !nameNode || (!imageName && !imageUrl)) {
                return;
            }

            preview.src = imageUrl || '#';
            preview.style.display = isVisible ? 'block' : 'none';
            nameNode.textContent = imageName;
            preview.setAttribute('data-file-type', imageUrl === window.pdfPreviewPlaceholder ? 'pdf' : 'image');

            if (wrapper) {
                wrapper.style.display = isVisible ? 'block' : 'none';
            }

            if (removeBtn) {
                removeBtn.style.display = isVisible ? 'inline-block' : 'none';
            }
        }

        function childCreateGetRoutePoints(routeId) {
            const normalizedRouteId = String(parseInt(routeId, 10) || 0);
            return Array.isArray(childCreateRoutePoints[normalizedRouteId]) ? childCreateRoutePoints[normalizedRouteId] : [];
        }

        function childCreateResetTransportFields() {
            startPointDisplay.value = '';
            pickupPointSelect.innerHTML = '<option value="">Select Route first</option>';
            pickupPointSelect.disabled = true;
            stopNameDisplay.value = '';
            pickupNameInput.value = '';
            stopNameInput.value = '';
            startPointDisplay.placeholder = 'Select Route first';
            stopNameDisplay.placeholder = 'Select Route first';
        }

        function childCreateSetSelectedPickup() {
            const selectedOption = pickupPointSelect.options[pickupPointSelect.selectedIndex];
            const pickupId = pickupPointSelect.value || '';
            const selectedStopName = selectedOption && selectedOption.dataset
                ? String(selectedOption.dataset.stopName || '').trim()
                : '';

            pickupNameInput.value = pickupId;
            stopNameInput.value = pickupId;
            stopNameDisplay.value = selectedStopName;
        }

        function childCreateRenderTransportDetails(routeId) {
            const normalizedRouteId = parseInt(routeId, 10) || 0;
            const routePoints = childCreateGetRoutePoints(normalizedRouteId);
            const transportItems = Array.isArray(childCreateTransportMap[String(normalizedRouteId)])
                ? childCreateTransportMap[String(normalizedRouteId)]
                : [];

            childCreateResetTransportFields();

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
                pickupPointSelect.appendChild(option);
            });

            if (pickupPointSelect.options.length > 1) {
                pickupPointSelect.selectedIndex = 1;
                childCreateSetSelectedPickup();
            } else {
                pickupPointSelect.innerHTML = '<option value="">No pickup points available</option>';
                pickupPointSelect.disabled = true;
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

                if (pointType === 'end' && !stopNameDisplay.value) {
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

        function restoreChildCreateDraftUi() {
            childCreateRenderTransportDetails(routeSelect.value);
            restoreChildPreview('child_main', 'imagePreview', 'imageName');
            restoreChildPreview('child_adhaar', 'imagePreview1', 'imageName1');
            setTimeout(() => {
                restoreChildPreview('child_main', 'imagePreview', 'imageName');
                restoreChildPreview('child_adhaar', 'imagePreview1', 'imageName1');
            }, 120);
        }

        restoreChildCreateDraftUi();

        window.__childModuleAfterDraftRestore = function () {
            restoreChildCreateDraftUi();
        };

        window.__childModuleBeforeNavigate = function () {
            const imageFile = document.getElementById('image')?.files?.[0] || null;
            const adhaarFile = document.getElementById('child_adhaar_card_image')?.files?.[0] || null;

            return Promise.all([
                serializeChildPreview('child_main', imageFile),
                serializeChildPreview('child_adhaar', adhaarFile),
            ]);
        };

        $(document)
            .off('change.childCreateTransport', '#route_id')
            .on('change.childCreateTransport', '#route_id', function() {
                childCreateRenderTransportDetails(this.value);
                $('#stop_name_display, #start_point_display, #pickup_point_select').next('.error-message').remove();
            });

        $(document)
            .off('change.childCreatePickup', '#pickup_point_select')
            .on('change.childCreatePickup', '#pickup_point_select', function() {
                childCreateSetSelectedPickup();
                $('#pickup_point_select, #stop_name_display').next('.error-message').remove();
            });

        $('#submitBtn').on('click', function() {

            $('.error-message').remove();
            let formData = new FormData(document.getElementById('childForm'));
            appendDraftFileToFormData(formData, 'child_main', 'image', 'image');
            appendDraftFileToFormData(formData, 'child_adhaar', 'child_adhaar_card_image', 'child_adhaar_card_image');
            let isValid = true;

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
            if (!hasChildFileSelection('child_main', 'image') || (currentImageSrc == "#" || currentImageSrc == "")) {
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
            if (!hasChildFileSelection('child_adhaar', 'child_adhaar_card_image') || (currentImageSrc1 == "#" || currentImageSrc1 ==
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

                    if (typeof window.__childModuleClearAllState === 'function') {
                        window.__childModuleClearAllState();
                    } else if (typeof window.__childModuleClearDraft === 'function') {
                        window.__childModuleClearDraft();
                    }
                    notify('success', 'Child created successfully!');
                    setTimeout(() => {
                        if (data && data.id) {
                            try { sessionStorage.setItem('childModule.child_id', String(data.id)); } catch (e) {}
                            const nextUrl = @json($parentCreateUrl) + '?child_id=' + encodeURIComponent(data.id);
                            if (typeof window.__childModuleLoadPage === 'function') {
                                window.__childModuleLoadPage(nextUrl);
                            } else {
                                window.location.href = nextUrl;
                            }
                            return;
                        }

                        if (typeof window.__childModuleLoadPage === 'function') {
                            window.__childModuleLoadPage(@json($parentCreateUrl));
                        } else {
                            window.location.href = @json($parentCreateUrl);
                        }
                    }, 400);
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
        $(document)
            .off('input.childCreate change.childCreate', 'input, select')
            .on('input.childCreate change.childCreate', 'input, select', function() {
                $(this).next('.error-message').remove();
            });

        $(document)
            .off('change.childCreate', '#school_id')
            .on('change.childCreate', '#school_id', function() {
                $(this).next('.error-message').remove();
            });

        $(document)
            .off('change.childCreate', '#route_id')
            .on('change.childCreate', '#route_id', function() {
                $(this).next('.error-message').remove();
            });

        $(document)
            .off('change.childCreate', 'input[name="gender"]')
            .on('change.childCreate', 'input[name="gender"]', function() {
                $('#genderGroup').next('.error-message').remove();
            });

        document.getElementById('image').addEventListener('change', function() {
            $('#ImageBtn').next('.error-message').remove();
            const file = this.files && this.files[0] ? this.files[0] : null;
            persistChildPreview('child_main', file);
        })

        document.getElementById('child_adhaar_card_image').addEventListener('change', function() {
            $('#ImageBtn1').next('.error-message').remove();
            const file = this.files && this.files[0] ? this.files[0] : null;
            persistChildPreview('child_adhaar', file);
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
            patchChildDraftState({
                child_main_image_name_preview: '',
                child_main_image_url_preview: '',
                child_main_image_visible_preview: '0',
                child_main_file_data: '',
                child_main_file_mime: '',
            });
            patchChildSpecialState({
                child_main_image_name_preview: '',
                child_main_image_url_preview: '',
                child_main_image_visible_preview: '0',
                child_main_file_data: '',
                child_main_file_mime: '',
            });
        });

        document.getElementById('removeImageBtn1').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview1',
                imageNameSelector: '#imageName1',
                imageInputSelector: '#child_adhaar_card_image',
                removeImageBtnSelector: '#removeImageBtn1'
            });
            patchChildDraftState({
                child_adhaar_image_name_preview: '',
                child_adhaar_image_url_preview: '',
                child_adhaar_image_visible_preview: '0',
                child_adhaar_file_data: '',
                child_adhaar_file_mime: '',
            });
            patchChildSpecialState({
                child_adhaar_image_name_preview: '',
                child_adhaar_image_url_preview: '',
                child_adhaar_image_visible_preview: '0',
                child_adhaar_file_data: '',
                child_adhaar_file_mime: '',
            });
        });
        })();
    </script>
@endsection
