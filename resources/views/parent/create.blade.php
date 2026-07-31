{{-- @extends('dashboard.index') --}}
@extends('admin_layout.index')

@section('content')
    @include('partials.toaster')

    @php
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $schoolSlug = request()->route('schoolSlug');
        $isSchoolPanel = filled($schoolSlug) && is_string($routeName) && str_starts_with($routeName, 'school.');

        $childCreateUrl = $isSchoolPanel
            ? route('school.child.create', ['schoolSlug' => $schoolSlug])
            : route('child.create');

        $childEditUrlTemplate = $isSchoolPanel
            ? route('school.child.edit', ['schoolSlug' => $schoolSlug, 'child' => '__CHILD__'])
            : route('child.edit', ['child' => '__CHILD__']);
    @endphp

    <div class="section-breadcrumb">
        <div class="breadcrumb-wrapper pb-0">
            <div class="container">
                <nav aria-label="breadcrumb-nav">
                    <ol class="breadcrumb breadcrumb-style-2 my-20">
                        <li class="breadcrumb-item"><a
                                class="breadcrumbLink"href="{{ route('admin_layout.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item breadcrumb-item-style-2 active" aria-current="page">Add Parent
                            Details</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    @include('child.partials.module_tabs', [
        'activeTab' => 'parent',
        'entityIds' => [],
    ])

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="about-us-create-header">Add Parent Details</h4>
            </div>
            <div class="card-body">
                <style>
                    #parentForm .password-input-group {
                        position: relative;
                    }

                    #parentForm .password-input-group .form-control {
                        padding-right: 42px;
                    }

                    #parentForm .password-input-group .input-group-append {
                        position: absolute;
                        right: 14px;
                        top: 50%;
                        transform: translateY(-50%);
                        display: flex;
                        align-items: center;
                        z-index: 3;
                    }

                    #parentForm .password-input-group .input-group-text {
                        border: 0;
                        background: transparent;
                        padding: 0;
                        min-height: auto;
                        color: #2d336b;
                        cursor: pointer;
                    }
                </style>
                <form id="parentForm" enctype="multipart/form-data">
                                        @csrf
                    <input type="hidden" id="child_id" name="child_id" value="{{ request('child_id') }}">
                    <input type="hidden" id="existing_parent_id" name="existing_parent_id" value="">
                    <div class="form-group">
                        <label style="font-weight: bold;">Existing registered parent ?</label>
                        <div class="d-flex align-items-center" style="gap: 18px; flex-wrap: wrap;">
                            <label class="mb-0" for="existing_registered_parent_no">
                                <input type="radio" id="existing_registered_parent_no" name="existing_registered_parent" value="no" checked>
                                No
                            </label>
                            <label class="mb-0" for="existing_registered_parent_yes">
                                <input type="radio" id="existing_registered_parent_yes" name="existing_registered_parent" value="yes">
                                Yes
                            </label>
                        </div>
                        <span id="existingParentHelpText" class="text-muted" style="display: none;"></span>
                        <span id="existingParentLookupMessage" class="error-message" style="display:block;"></span>
                    </div>
                    <div class="form-group">
                        <label for="father_name" style="font-weight: bold;">Father Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="father_name" name="father_name">
                    </div>
                    <div class="form-group">
                        <label for="mother_name" style="font-weight: bold;">Mother Name <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="mother_name" name="mother_name">
                    </div>
                    <div class="form-group">
                        <label for="contact_number" style="font-weight: bold;">
                            Contact Number <span style="color:red;">*</span>
                        </label>
                        <input type="tel" class="form-control" id="contact_number" name="contact_number"
                            placeholder="Enter 10 or 11 digit number" minlength="10" maxlength="11" pattern="[0-9]{10,11}"
                            required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="alternative_contact_number" style="font-weight: bold;">
                            Alternative Contact Number <span style="color:red;">*</span>
                        </label>
                        <input type="tel" class="form-control" id="alternative_contact_number"
                            name="alternative_contact_number" placeholder="Enter 10 or 11 digit number" minlength="10"
                            maxlength="11" pattern="[0-9]{10,11}" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="email" style="font-weight: bold;">Email <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="login_username" style="font-weight: bold;">Login Username <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="login_username" name="login_username">
                    </div>
                    <div class="form-group">
                        <div id="passwordRequiredHint" class="mb-1 text-muted"></div>
                        <label for="password" style="font-weight: bold;">Password <span style="color: red;">*</span></label>
                        <div class="input-group password-input-group">
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePassword('password')">
                                    <i class="fa fa-eye" id="togglePasswordIcon"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation" style="font-weight: bold;">Confirm Password <span style="color: red;">*</span></label>
                        <div class="input-group password-input-group">
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePassword('password_confirmation')">
                                    <i class="fa fa-eye" id="toggleConfirmPasswordIcon"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address_1" style="font-weight: bold;">Address 1 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="address_1" name="address_1">
                    </div>
                    <div class="form-group">
                        <label for="address_2" style="font-weight: bold;">Address 2 <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="address_2" name="address_2">
                    </div>
                    <div class="form-group">
                        <label for="state" style="font-weight: bold;">
                            State <span style="color: red;">*</span>
                        </label>
                        <select class="form-control" id="state" name="state" required>
                            <option value="">Select State</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->name }}">
                                    {{ $state->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="city" style="font-weight: bold;">
                            City <span style="color: red;">*</span>
                        </label>
                        <select class="form-control" id="city" name="city" required>
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pincode" style="font-weight: bold;">Pincode <span
                                style="color: red;">*</span></label>
                        <input type="text" class="form-control" id="pincode" name="pincode">
                    </div>
                    <div class="form-group">
                        <label>Father Aadhar Card Image <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 636 × 424 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="fatherAdherImageBtn"
                            onclick="document.getElementById('father_adhaar_card_image').click();">Upload Image</button>
                        <input type="file" id="father_adhaar_card_image" name="father_adhaar_card_image"
                            accept="image/*" style="display:none;" onchange="previewImage(event)">
                        <span id="imageName"></span>

                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn"><i
                                class="fas fa-trash"></i></button>
                    </div>
                    <div class="form-group">
                        <label>Mother Aadhar Card Image <span style="color:red;">*</span><small style="color:#6c757d;">
                                (Image must be at least 800 × 600 pixels)
                            </small></label><br>
                        <button type="button" class="btn btn-primary" id="motherAdherImageBtn"
                            onclick="document.getElementById('mother_adhaar_card_image').click();">Upload Image</button>
                        <input type="file" id="mother_adhaar_card_image" name="mother_adhaar_card_image"
                            accept="image/*" style="display:none;" onchange="previewImage1(event)">
                        <span id="imageName1"></span>

                    </div>
                    <div id="dlt_btn_div" class="dlt_btn_div" style="display: none;">
                        <img id="imagePreview1" src="#" alt="Image Preview"
                            style="display: none; width: 100px; height: 100px; margin-top: 10px;">
                        <button type="button" class="btn" style="display: none" id="removeImageBtn1"><i
                                class="fas fa-trash"></i></button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" id="submitBtn"
                            style="background-color: #2C9DD4; color: white;">Submit</button>
                        <a href="{{ route('parent.index') }}" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Include common icon picker JS -->
    <script src="{{ asset('js/common-iconpicker.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <script>
        const parentState = {
            existingLookupInFlight: false,
            messageTimeout: null,
            lookupDebounce: null,
        };

        function isExistingParentSelected() {
            const selected = document.querySelector('input[name="existing_registered_parent"]:checked');
            return selected && selected.value === 'yes';
        }

        function setExistingParentMessage(message, isError = false) {
            const messageEl = document.getElementById('existingParentLookupMessage');
            if (!messageEl) {
                return;
            }

            if (parentState.messageTimeout) {
                clearTimeout(parentState.messageTimeout);
                parentState.messageTimeout = null;
            }

            messageEl.textContent = message || '';
            messageEl.style.color = isError ? 'red' : '#2d7a2d';

            if (message && !isError) {
                parentState.messageTimeout = setTimeout(() => {
                    messageEl.textContent = '';
                    parentState.messageTimeout = null;
                }, 10000);
            }
        }

        function clearExistingParentSelection(clearUsername = false) {
            document.getElementById('existing_parent_id').value = '';
            setExistingParentLoginFieldsReadonly(false);
            setExistingParentMessage('');

            if (clearUsername) {
                document.getElementById('login_username').value = '';
                document.getElementById('email').value = '';
            }
        }

        function getExistingParentLookupValue() {
            const loginUsername = document.getElementById('login_username').value.trim();
            const email = document.getElementById('email').value.trim();
            return loginUsername || email;
        }

        function setExistingParentLoginFieldsReadonly(isReadonly) {
            const emailField = document.getElementById('email');
            const usernameField = document.getElementById('login_username');
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('password_confirmation');

            if (emailField) {
                emailField.readOnly = !!isReadonly;
            }

            if (usernameField) {
                usernameField.readOnly = !!isReadonly;
            }

            if (passwordField) {
                passwordField.readOnly = true;
            }

            if (confirmPasswordField) {
                confirmPasswordField.readOnly = true;
            }
        }

        function scheduleExistingParentLookup() {
            if (!isExistingParentSelected()) {
                return;
            }

            if (parentState.lookupDebounce) {
                clearTimeout(parentState.lookupDebounce);
                parentState.lookupDebounce = null;
            }

            parentState.lookupDebounce = setTimeout(() => {
                parentState.lookupDebounce = null;
                lookupExistingParent();
            }, 350);
        }

        function applyExistingParentImagePreview(options) {
            const preview = document.getElementById(options.previewId);
            const imageName = document.getElementById(options.imageNameId);
            const removeBtn = document.getElementById(options.removeBtnId);
            const input = document.getElementById(options.inputId);
            const wrapper = preview ? preview.parentElement : null;

            if (!preview || !imageName || !removeBtn || !input) {
                return;
            }

            if (options.imageUrl) {
                if (wrapper) {
                    wrapper.style.display = 'block';
                }
                preview.src = options.imageUrl;
                preview.style.display = 'block';
                imageName.textContent = options.imageName || 'Existing image';
                removeBtn.style.display = 'none';
                input.value = '';
            } else {
                if (wrapper) {
                    wrapper.style.display = 'none';
                }
                preview.src = '#';
                preview.style.display = 'none';
                imageName.textContent = '';
                removeBtn.style.display = 'none';
                input.value = '';
            }
        }

        function fillExistingParentForm(parent) {
            document.getElementById('existing_parent_id').value = parent.id || '';
            setExistingParentLoginFieldsReadonly(true);
            document.getElementById('father_name').value = parent.father_name || '';
            document.getElementById('mother_name').value = parent.mother_name || '';
            document.getElementById('contact_number').value = parent.contact_number || '';
            document.getElementById('alternative_contact_number').value = parent.alternative_contact_number || '';
            document.getElementById('email').value = parent.email || '';
            document.getElementById('login_username').value = parent.login_username || document.getElementById('login_username').value;
            document.getElementById('address_1').value = parent.address_1 || '';
            document.getElementById('address_2').value = parent.address_2 || '';
            document.getElementById('pincode').value = parent.pincode || '';
            applyExistingParentImagePreview({
                previewId: 'imagePreview',
                imageNameId: 'imageName',
                removeBtnId: 'removeImageBtn',
                inputId: 'father_adhaar_card_image',
                imageUrl: parent.father_adhaar_card_image_url || '',
                imageName: parent.father_adhaar_card_image || ''
            });
            applyExistingParentImagePreview({
                previewId: 'imagePreview1',
                imageNameId: 'imageName1',
                removeBtnId: 'removeImageBtn1',
                inputId: 'mother_adhaar_card_image',
                imageUrl: parent.mother_adhaar_card_image_url || '',
                imageName: parent.mother_adhaar_card_image || ''
            });

            const stateField = document.getElementById('state');
            const cityField = document.getElementById('city');
            const parentStateName = (parent.state || '').trim();
            const parentCityName = (parent.city || '').trim();

            const normalizedParentState = parentStateName.toLowerCase();
            let matchedState = '';
            let matchedOption = null;
            Array.from(stateField.options).forEach(option => {
                const optionValue = (option.value || '').trim();
                const optionLabel = (option.text || '').trim();
                if (!matchedState && (optionValue.toLowerCase() === normalizedParentState || optionLabel.toLowerCase() === normalizedParentState)) {
                    matchedState = optionValue || optionLabel;
                    matchedOption = option;
                }
            });

            if (!matchedState && parentStateName) {
                matchedOption = new Option(parentStateName, parentStateName, true, true);
                stateField.add(matchedOption);
                matchedState = parentStateName;
            }

            stateField.value = matchedState || parentStateName;
            Array.from(stateField.options).forEach(option => {
                option.selected = ((option.value || '').trim() === (stateField.value || '').trim());
            });
            stateField.dispatchEvent(new Event('change'));

            if (!parentStateName) {
                cityField.innerHTML = '<option value="">Select City</option>';
                return;
            }

            cityField.innerHTML = '<option value="">Loading...</option>';

            $.ajax({
                url: "{{ route('api.parent.getCities') }}",
                type: "POST",
                timeout: 15000,
                data: {
                    state: parentStateName,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    let cities = [];
                    if (Array.isArray(response)) {
                        cities = response;
                    } else if (response && Array.isArray(response.cities)) {
                        cities = response.cities;
                    } else if (response && Array.isArray(response.data)) {
                        cities = response.data;
                    }

                    cityField.innerHTML = '<option value="">Select City</option>';
                    cities.forEach(city => {
                        cityField.insertAdjacentHTML('beforeend', `<option value="${city}">${city}</option>`);
                    });

                    if (parentCityName) {
                        if (!cities.includes(parentCityName)) {
                            cityField.insertAdjacentHTML('beforeend', `<option value="${parentCityName}">${parentCityName}</option>`);
                        }
                        cityField.value = parentCityName;
                    }
                },
                error: function() {
                    cityField.innerHTML = '<option value="">Select City</option>';
                    if (parentCityName) {
                        cityField.insertAdjacentHTML('beforeend', `<option value="${parentCityName}">${parentCityName}</option>`);
                        cityField.value = parentCityName;
                    }
                }
            });
        }

        function toggleExistingParentMode() {
            const isExisting = isExistingParentSelected();
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('password_confirmation');
            const passwordLabelRequired = document.querySelector('label[for="password"] span');
            const confirmPasswordLabelRequired = document.querySelector('label[for="password_confirmation"] span');
            const passwordHint = document.getElementById('passwordRequiredHint');
            const helpText = document.getElementById('existingParentHelpText');

            passwordField.required = !isExisting;
            confirmPasswordField.required = !isExisting;
            passwordField.disabled = isExisting;
            confirmPasswordField.disabled = isExisting;
            setExistingParentLoginFieldsReadonly(isExisting && !!document.getElementById('existing_parent_id').value);

            if (isExisting) {
                passwordField.value = '';
                confirmPasswordField.value = '';
                if (passwordLabelRequired) passwordLabelRequired.style.display = 'none';
                if (confirmPasswordLabelRequired) confirmPasswordLabelRequired.style.display = 'none';
                if (passwordHint) passwordHint.textContent = '';
                if (helpText) helpText.style.display = 'block';
                document.getElementById('father_adhaar_card_image').value = '';
                document.getElementById('mother_adhaar_card_image').value = '';
            } else {
                clearExistingParentSelection(false);
                if (passwordLabelRequired) passwordLabelRequired.style.display = 'inline';
                if (confirmPasswordLabelRequired) confirmPasswordLabelRequired.style.display = 'inline';
                if (passwordHint) passwordHint.textContent = '';
                if (helpText) helpText.style.display = 'none';
                applyExistingParentImagePreview({
                    previewId: 'imagePreview',
                    imageNameId: 'imageName',
                    removeBtnId: 'removeImageBtn',
                    inputId: 'father_adhaar_card_image',
                    imageUrl: '',
                    imageName: ''
                });
                applyExistingParentImagePreview({
                    previewId: 'imagePreview1',
                    imageNameId: 'imageName1',
                    removeBtnId: 'removeImageBtn1',
                    inputId: 'mother_adhaar_card_image',
                    imageUrl: '',
                    imageName: ''
                });
            }
        }

        function lookupExistingParent() {
            if (!isExistingParentSelected()) {
                return;
            }

            const lookupValue = getExistingParentLookupValue();
            if (!lookupValue) {
                clearExistingParentSelection(false);
                setExistingParentMessage('');
                return;
            }

            parentState.existingLookupInFlight = true;
            setExistingParentMessage('');

            fetch('{{ route('api.parent.find-existing') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        login_username: lookupValue
                    })
                })
                .then(async response => {
                    const data = await response.json().catch(() => null);
                    if (!response.ok || !data || data.success === false) {
                        throw new Error((data && data.message) ? data.message : 'Existing parent not found.');
                    }
                    return data;
                })
                .then(data => {
                    fillExistingParentForm(data.parent || {});
                    setExistingParentMessage('Existing parent details have been auto-filled successfully.');
                })
                .catch(error => {
                    clearExistingParentSelection(false);
                    setExistingParentMessage('');
                })
                .finally(() => {
                    parentState.existingLookupInFlight = false;
                });
        }

        /* ===============================
                           STATE → CITY DROPDOWN (API)
                        ================================ */
        $(document).ready(function() {

            $('#state').on('change', function() {
                let state = $(this).val();
                $('#city').html('<option>Loading...</option>');
                if (!state) {
                    $('#city').html('<option value="">Select City</option>');
                    return;
                }

                $.ajax({
                    url: "{{ route('api.parent.getCities') }}",
                    type: "POST",
                    timeout: 15000,
                    data: {
                        state: state,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        let cities = [];
                        if (Array.isArray(response)) {
                            cities = response;
                        } else if (response && Array.isArray(response.cities)) {
                            cities = response.cities;
                        } else if (response && Array.isArray(response.data)) {
                            cities = response.data;
                        }

                        $('#city').empty().append('<option value="">Select City</option>');
                        cities.forEach(city => {
                            $('#city').append(
                                `<option value="${city}">${city}</option>`
                            );
                        });

                        if (!cities.length) {
                            $('#city').html('<option value="">No cities found</option>');
                        }
                    },
                    error: function(xhr, status) {
                        console.error('City load failed:', status, xhr && xhr.responseText ? xhr.responseText : '');
                        $('#city').html('<option value="">Error loading cities</option>');
                    }
                });
            });

            $('input[name="existing_registered_parent"]').on('change', function() {
                toggleExistingParentMode();
                if (isExistingParentSelected() && getExistingParentLookupValue()) {
                    scheduleExistingParentLookup();
                }
            });

            $('#login_username, #email').on('input', function() {
                if (isExistingParentSelected()) {
                    document.getElementById('existing_parent_id').value = '';
                    setExistingParentLoginFieldsReadonly(false);
                    setExistingParentMessage('');
                    scheduleExistingParentLookup();
                }
            });

            $('#login_username, #email').on('change blur', function() {
                if (isExistingParentSelected()) {
                    scheduleExistingParentLookup();
                }
            });

            toggleExistingParentMode();
        });

        window.togglePassword = function(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.closest('.password-input-group').querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        };

        /* ===============================
           FORM SUBMIT (YOUR EXISTING CODE)
        ================================ */
        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = new FormData(document.getElementById('parentForm'));
            const params = new URLSearchParams(window.location.search);
            const childIdFromQuery = params.get('child_id') || '';
            const childIdFromStorage = (function () {
                try { return sessionStorage.getItem('childModule.child_id') || ''; } catch (e) { return ''; }
            })();
            const childId = childIdFromQuery || childIdFromStorage;
            if (childId) {
                formData.set('child_id', childId);
                const childIdField = document.getElementById('child_id');
                if (childIdField) {
                    childIdField.value = childId;
                }
            }

            document.querySelectorAll('.error-message').forEach(function(el) {
                if (el.id !== 'existingParentLookupMessage') {
                    el.textContent = '';
                }
            });
            setExistingParentMessage('');

            let isValid = true;
            if (!formData.get('father_name')) {
                document.getElementById('father_name')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Father Name is required.';
                isValid = false;
            }
            if (!formData.get('mother_name')) {
                document.getElementById('mother_name')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Mother Name is required.';
                isValid = false;
            }
            if (!formData.get('contact_number')) {
                document.getElementById('contact_number')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Contact Number is required.';
                isValid = false;
            }
            if (!formData.get('alternative_contact_number')) {
                document.getElementById('alternative_contact_number')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'AlterNative Contact Number is required.';
                isValid = false;
            }
            if (!formData.get('email')) {
                document.getElementById('email')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Email is required.';
                isValid = false;
            }
            if (!formData.get('login_username')) {
                document.getElementById('login_username')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Login Username is required.';
                isValid = false;
            }
            if (isExistingParentSelected() && !formData.get('existing_parent_id')) {
                setExistingParentMessage('');
                isValid = false;
            }
            if (!isExistingParentSelected() && !formData.get('password')) {
                document.getElementById('password')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Password is required.';
                isValid = false;
            }
            if (!isExistingParentSelected() && !formData.get('password_confirmation')) {
                document.getElementById('password_confirmation')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Confirm Password is required.';
                isValid = false;
            }
            if (!isExistingParentSelected() && formData.get('password') && formData.get('password_confirmation') && formData.get('password') !== formData.get('password_confirmation')) {
                document.getElementById('password_confirmation')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Password and Confirm Password must match.';
                isValid = false;
            }
            if (!formData.get('address_1')) {
                document.getElementById('address_1')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Address 1 is required.';
                isValid = false;
            }
            if (!formData.get('address_2')) {
                document.getElementById('address_2')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Address 2 is required.';
                isValid = false;
            }
            if (!formData.get('state')) {
                document.getElementById('state')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'State is required.';
                isValid = false;
            }
            if (!formData.get('city')) {
                document.getElementById('city')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'City is required.';
                isValid = false;
            }
            if (!formData.get('pincode')) {
                document.getElementById('pincode')
                    .closest('.form-group')
                    .querySelector('.error-message').textContent = 'Pincode is required.';
                isValid = false;
            }

            function enforcePhoneDigits(el) {
                el.value = el.value.replace(/\D/g, '').slice(0, 11);
            }

            document.getElementById('contact_number')
                .addEventListener('input', function() {
                    enforcePhoneDigits(this);
                });

            document.getElementById('alternative_contact_number')
                .addEventListener('input', function() {
                    enforcePhoneDigits(this);
                });

            var imageInput = document.getElementById('father_adhaar_card_image');
            var imagePreview = document.getElementById('imagePreview');
            var currentImageSrc = imagePreview.getAttribute('src');
            var isDefaultImage = currentImageSrc.includes('Default.jpg');
            if (!isExistingParentSelected() && (!imageInput.files.length && isDefaultImage || (currentImageSrc == "#" || currentImageSrc == ""))) {
                $('#fatherAdherImageBtn').after(
                    '<span class="error-message" style="color: red;">Father Adhaar Card Image is required.</span>'
                );
                isValid = false;
            }
            var imageInput1 = document.getElementById('mother_adhaar_card_image');
            var imagePreview1 = document.getElementById('imagePreview1');
            var currentImageSrc1 = imagePreview1.getAttribute('src');
            var isDefaultImage1 = currentImageSrc1.includes('Default.jpg');
            if (!isExistingParentSelected() && (!imageInput1.files.length && isDefaultImage1 || (currentImageSrc1 == "#" || currentImageSrc1 == ""))) {
                $('#motherAdherImageBtn').after(
                    '<span class="error-message" style="color: red;">Mother Adhaar Card Image is required.</span>'
                );
                isValid = false;
            }
            if (!isValid) return;

            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('api.parent.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })
                .then(async res => {
                    let data = null;
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
                    Swal.close();

                    notify('success', 'Parent created Successfully!');

                    if (data && data.id) {
                        try {
                            sessionStorage.setItem('childModule.parent_id', String(data.id));
                        } catch (e) {}
                    }

                    if (childId && data && data.id) {
                        fetch('/api/child/' + encodeURIComponent(childId) + '/set-parent', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ parent_id: data.id })
                            })
                            .then(res => res.json())
                            .then(linkRes => {
                                if (!linkRes || !linkRes.success) {
                                    throw (linkRes && linkRes.message) ? linkRes.message : 'Failed to link parent to child';
                                }

                                const editUrl = @json($childEditUrlTemplate).replace('__CHILD__', encodeURIComponent(childId));
                                if (typeof window.__childModuleLoadPage === 'function') {
                                    window.__childModuleLoadPage(editUrl);
                                } else {
                                    window.location.href = editUrl;
                                }
                            })
                            .catch(err => {
                                notify('error', typeof err === 'string' ? err : (err.message || 'Link failed'));
                                const fallbackUrl = @json($childCreateUrl);
                                if (typeof window.__childModuleLoadPage === 'function') {
                                    window.__childModuleLoadPage(fallbackUrl);
                                } else {
                                    window.location.href = fallbackUrl;
                                }
                            });
                        return;
                    }

                    const fallbackUrl = @json($childCreateUrl);
                    setTimeout(() => {
                        if (typeof window.__childModuleLoadPage === 'function') {
                            window.__childModuleLoadPage(fallbackUrl);
                        } else {
                            window.location.href = fallbackUrl;
                        }
                    }, 400);
                })
                .catch(error => {
                    Swal.close();
                    notify('error', typeof error === 'string' ? error : (error.message || 'Something went wrong'));
                });

        });

        document.querySelectorAll('.form-control').forEach(function(input) {
            if (!input.classList.contains('select2-hidden-accessible')) {
                let errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = 'red';
                input.closest('.form-group').appendChild(errorSpan);
            }
        });

        $('#contact_number,#alternative_contact_number,#pincode').on('input paste', function() {
            const value = $(this).val();
            if (value && !/^\d*\.?\d*$/.test(value)) {
                $(this).val(value.slice(0, -1));
            }
        });

        document.getElementById('father_adhaar_card_image').addEventListener('change', function() {
            $('#fatherAdherImageBtn').next('.error-message').remove();
        })

        document.getElementById('mother_adhaar_card_image').addEventListener('change', function() {
            $('#motherAdherImageBtn').next('.error-message').remove();
        });

        $('#father_name, #mother_name, #contact_number, #email, #login_username, #password, #password_confirmation, #state, #city, #pincode, #alternative_contact_number,#address_1,#address_2')
            .on(
                'change input',
                function() {
                    $(this).closest('.form-group').find('.error-message').text('');
                    if ((this.id === 'login_username' || this.id === 'email') && !isExistingParentSelected()) {
                        setExistingParentMessage('');
                    }
                });

        document.getElementById('removeImageBtn').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview',
                imageNameSelector: '#imageName',
                imageInputSelector: '#father_adhaar_card_image',
                removeImageBtnSelector: '#removeImageBtn'
            });
        });
        document.getElementById('removeImageBtn1').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview1',
                imageNameSelector: '#imageName1',
                imageInputSelector: '#mother_adhaar_card_image',
                removeImageBtnSelector: '#removeImageBtn1'
            });
        });
    </script>
@endsection
