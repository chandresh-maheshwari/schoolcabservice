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
        'entityIds' => [
            'child' => request('child_id'),
            'parent' => request('parent_id'),
        ],
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
                    <input type="hidden" id="existing_registered_parent_hidden" value="no">
                    <script>
                        window.existingParents = @json($existingParents ?? []);
                    </script>
                    <div class="form-group">
                        <label style="font-weight: bold;">Existing registered parent ?</label>
                        <div class="d-flex align-items-center" style="gap: 18px; flex-wrap: wrap;">
                            <label class="mb-0" for="existing_registered_parent_no">
                                <input type="radio" id="existing_registered_parent_no" name="existing_registered_parent" value="no" checked onchange="toggleExistingParentMode()">
                                No
                            </label>
                            <label class="mb-0" for="existing_registered_parent_yes">
                                <input type="radio" id="existing_registered_parent_yes" name="existing_registered_parent" value="yes" onchange="toggleExistingParentMode()">
                                Yes
                            </label>
                        </div>
                        <span id="existingParentHelpText" class="text-muted" style="display: none;"></span>
                        <span id="existingParentLookupMessage" class="error-message" style="display:block;"></span>
                    </div>
                    <div class="form-group">
                        <label for="email" style="font-weight: bold;">Email <span style="color: red;">*</span></label>
                        <input type="hidden" id="existing_parent_email_selected_hidden" name="existing_parent_email_selected_hidden" value="">
                        <div id="email_text_wrapper">
                            <input type="text" class="form-control" id="email" name="email" autocomplete="off">
                        </div>
                        <div id="existing_parent_email_select_wrapper" style="display: none;">
                            <select class="form-control mt-2" id="existing_parent_email_select" disabled>
                                <option value="">Select Email</option>
                                @foreach (($existingParents ?? []) as $existingParent)
                                    <option value="{{ $existingParent['email'] }}">
                                        {{ trim(($existingParent['father_name'] ?? '') . ' - ' . ($existingParent['email'] ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
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
                        <select class="form-control" id="city" name="city" data-select2-off="true" required>
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
            isRestoringDraft: false,
        };
        const existingParents = Array.isArray(window.existingParents) ? window.existingParents : [];

        function isExistingParentSelected() {
            const selected = document.querySelector('input[name="existing_registered_parent"]:checked');
            if (selected) {
                return selected.value === 'yes';
            }

            const hiddenExistingParentField = document.getElementById('existing_registered_parent_hidden');
            return hiddenExistingParentField && hiddenExistingParentField.value === 'yes';
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
            const hiddenExistingParentField = document.getElementById('existing_registered_parent_hidden');
            if (hiddenExistingParentField) {
                hiddenExistingParentField.value = 'no';
            }
            setExistingParentLoginFieldsReadonly(false);
            setExistingParentMessage('');

            if (clearUsername) {
                document.getElementById('login_username').value = '';
                document.getElementById('email').value = '';
            }
        }

        function clearExistingParentAutofillFields() {
            document.getElementById('father_name').value = '';
            document.getElementById('mother_name').value = '';
            document.getElementById('contact_number').value = '';
            document.getElementById('alternative_contact_number').value = '';
            document.getElementById('email').value = '';
            document.getElementById('login_username').value = '';
            document.getElementById('address_1').value = '';
            document.getElementById('address_2').value = '';
            document.getElementById('pincode').value = '';
            document.getElementById('state').value = '';
            document.getElementById('city').innerHTML = '<option value="">Select City</option>';
            syncExistingParentEmailSelection('');

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

            patchParentDraftState({
                father_image_name_preview: '',
                father_image_url_preview: '',
                father_image_visible_preview: '0',
                mother_image_name_preview: '',
                mother_image_url_preview: '',
                mother_image_visible_preview: '0',
            });
        }

        function getExistingParentLookupValue() {
            const loginUsername = document.getElementById('login_username').value.trim();
            const email = document.getElementById('email').value.trim();
            return loginUsername || email;
        }

        function syncCustomSelectRenderedText(selectField) {
            if (!selectField) {
                return;
            }

            const wrapper = selectField.previousElementSibling;
            if (!wrapper || !wrapper.classList.contains('common-select2')) {
                return;
            }

            const rendered = wrapper.querySelector('.select2-selection__rendered');
            if (!rendered) {
                return;
            }

            const selectedOption = selectField.options[selectField.selectedIndex] || null;
            const selectedText = selectedOption ? String(selectedOption.text || '').trim() : '';
            const placeholder = String(selectField.getAttribute('data-placeholder') || '').trim()
                || (selectField.options[0] ? String(selectField.options[0].text || '').trim() : 'Select an option');
            const hasValue = String(selectField.value || '').trim() !== '';

            rendered.textContent = selectedText || placeholder;
            rendered.classList.toggle('select2-selection__placeholder', !hasValue);
        }

        function syncExistingParentEmailSelection(email, options = {}) {
            const selectField = document.getElementById('existing_parent_email_select');
            const emailHiddenField = document.getElementById('existing_parent_email_selected_hidden');
            if (selectField) {
                const normalizedEmail = String(email || '').trim();
                const normalizedEmailLower = normalizedEmail.toLowerCase();
                let hasMatch = false;

                Array.from(selectField.options).forEach((option) => {
                    const optionValue = String(option.value || '').trim();
                    const isMatch = optionValue.toLowerCase() === normalizedEmailLower && normalizedEmailLower !== '';
                    option.selected = isMatch;
                    if (isMatch) {
                        hasMatch = true;
                    }
                });

                if (normalizedEmail && !hasMatch) {
                    selectField.insertAdjacentHTML('beforeend', `<option value="${normalizedEmail}">${normalizedEmail}</option>`);
                    hasMatch = true;
                }

                selectField.value = hasMatch ? normalizedEmail : '';
                if (options.triggerChange === true) {
                    selectField.dispatchEvent(new Event('change', { bubbles: true }));
                }
                syncCustomSelectRenderedText(selectField);
                if (window.jQuery) {
                    window.jQuery(selectField).trigger('change.select2');
                }

                if (emailHiddenField) {
                    emailHiddenField.value = hasMatch ? normalizedEmail : '';
                }

                patchParentSpecialState({
                    existing_parent_email_selected: hasMatch ? normalizedEmail : '',
                });
            }
        }

        function forceRestoreExistingParentEmailSelection(email) {
            const normalizedEmail = String(email || '').trim();
            if (!normalizedEmail) {
                return;
            }

            syncExistingParentEmailSelection(normalizedEmail);

            const emailField = document.getElementById('email');
            const emailHiddenField = document.getElementById('existing_parent_email_selected_hidden');
            if (emailField) {
                emailField.value = normalizedEmail;
            }
            if (emailHiddenField) {
                emailHiddenField.value = normalizedEmail;
            }

            patchParentDraftState({
                existing_registered_parent_mode: 'yes',
                existing_parent_email_selected: normalizedEmail,
            });
            patchParentSpecialState({
                existing_registered_parent_mode: 'yes',
                existing_parent_email_selected: normalizedEmail,
            });
        }

        function syncCitySelection(city) {
            const cityField = document.getElementById('city');
            const normalizedCity = String(city || '').trim();
            if (!cityField) {
                return;
            }

            if (!normalizedCity) {
                cityField.value = '';
                syncCustomSelectRenderedText(cityField);
                if (window.jQuery) {
                    window.jQuery(cityField).trigger('change.select2');
                }
                return;
            }

            let hasMatch = false;
            Array.from(cityField.options).forEach((option) => {
                const isMatch = String(option.value || '').trim() === normalizedCity;
                option.selected = isMatch;
                if (isMatch) {
                    hasMatch = true;
                }
            });

            if (!hasMatch) {
                cityField.insertAdjacentHTML('beforeend', `<option value="${normalizedCity}">${normalizedCity}</option>`);
                cityField.value = normalizedCity;
            } else {
                cityField.value = normalizedCity;
            }

            cityField.dispatchEvent(new Event('change', { bubbles: true }));
            syncCustomSelectRenderedText(cityField);
            if (window.jQuery) {
                window.jQuery(cityField).trigger('change.select2');
            }
        }

        function toggleEmailMode(isExisting) {
            const emailInput = document.getElementById('email');
            const emailSelect = document.getElementById('existing_parent_email_select');
            const emailInputWrapper = document.getElementById('email_text_wrapper');
            const emailSelectWrapper = document.getElementById('existing_parent_email_select_wrapper');

            if (!emailInput || !emailSelect || !emailInputWrapper || !emailSelectWrapper) {
                return;
            }

            emailSelectWrapper.style.display = isExisting ? 'block' : 'none';
            emailInputWrapper.style.display = isExisting ? 'none' : 'block';
            emailSelect.disabled = !isExisting;
            emailInput.readOnly = !!isExisting;

            if (!isExisting) {
            syncExistingParentEmailSelection('', { triggerChange: false });
            }
        }

        function findExistingParentByEmail(email) {
            const normalizedEmail = String(email || '').trim().toLowerCase();
            return existingParents.find(parent => String(parent.email || '').trim().toLowerCase() === normalizedEmail) || null;
        }

        function resolveExistingParentFromSelect(selectField) {
            const selectValue = String(selectField?.value || '').trim();
            const selectedOption = selectField && selectField.selectedIndex >= 0
                ? selectField.options[selectField.selectedIndex]
                : null;
            const optionText = String(selectedOption?.text || '').trim();

            let parent = findExistingParentByEmail(selectValue);
            if (parent) {
                return parent;
            }

            const normalizedText = optionText.toLowerCase();
            return existingParents.find((candidate) => {
                const email = String(candidate.email || '').trim().toLowerCase();
                return email !== '' && normalizedText.includes(email);
            }) || null;
        }

        function ensureExistingParentAutofillFromSelection() {
            const selectedEmail = String(document.getElementById('existing_parent_email_select')?.value || '').trim();
            if (!selectedEmail) {
                return false;
            }

            const selectedParent = findExistingParentByEmail(selectedEmail);
            if (!selectedParent) {
                return false;
            }

            fillExistingParentForm(selectedParent);
            setExistingParentMessage('Existing parent details have been auto-filled successfully.');
            return true;
        }

        function getParentDraftState() {
            if (typeof window.__childModuleGetDraftState === 'function') {
                return window.__childModuleGetDraftState() || {};
            }

            return {};
        }

        function getParentSpecialState() {
            try {
                const raw = sessionStorage.getItem('childModuleParentSpecial');
                return raw ? (JSON.parse(raw) || {}) : {};
            } catch (e) {
                return {};
            }
        }

        function patchParentDraftState(patch) {
            if (typeof window.__childModulePatchDraftState === 'function') {
                window.__childModulePatchDraftState(patch || {});
            }
        }

        function patchParentSpecialState(patch) {
            if (!patch || typeof patch !== 'object') {
                return;
            }

            try {
                const nextState = Object.assign({}, getParentSpecialState(), patch);
                sessionStorage.setItem('childModuleParentSpecial', JSON.stringify(nextState));
            } catch (e) {}
        }

        function persistExistingParentMode() {
            const selectedEmailValue = String(document.getElementById('existing_parent_email_select')?.value || document.getElementById('existing_parent_email_selected_hidden')?.value || '');
            const emailHiddenField = document.getElementById('existing_parent_email_selected_hidden');
            if (emailHiddenField) {
                emailHiddenField.value = selectedEmailValue;
            }
            const selected = document.querySelector('input[name="existing_registered_parent"]:checked');
            patchParentDraftState({
                existing_registered_parent_mode: selected ? String(selected.value || 'no') : 'no',
                existing_parent_email_selected: selectedEmailValue,
                existing_parent_id: String(document.getElementById('existing_parent_id')?.value || ''),
            });
            patchParentSpecialState({
                existing_registered_parent_mode: selected ? String(selected.value || 'no') : 'no',
                existing_parent_email_selected: selectedEmailValue,
                existing_parent_id: String(document.getElementById('existing_parent_id')?.value || ''),
            });
        }

        function persistParentImageDraft(prefix) {
            const preview = document.getElementById(prefix === 'father' ? 'imagePreview' : 'imagePreview1');
            const imageName = document.getElementById(prefix === 'father' ? 'imageName' : 'imageName1');
            const wrapper = preview ? preview.parentElement : null;
            const imageUrl = preview ? String(preview.getAttribute('src') || '') : '';
            const visible = preview ? preview.style.display !== 'none' : (wrapper ? wrapper.style.display !== 'none' : false);

            patchParentDraftState({
                [`${prefix}_image_name_preview`]: imageName ? String(imageName.textContent || '') : '',
                [`${prefix}_image_url_preview`]: imageUrl && imageUrl !== '#' ? imageUrl : '',
                [`${prefix}_image_visible_preview`]: visible ? '1' : '0',
            });
            patchParentSpecialState({
                [`${prefix}_image_name_preview`]: imageName ? String(imageName.textContent || '') : '',
                [`${prefix}_image_url_preview`]: imageUrl && imageUrl !== '#' ? imageUrl : '',
                [`${prefix}_image_visible_preview`]: visible ? '1' : '0',
            });
        }

        function persistExistingParentPreview(prefix) {
            persistParentImageDraft(prefix);
        }

        function serializeParentPreview(prefix, file) {
            return new Promise((resolve) => {
                if (!file) {
                    persistExistingParentPreview(prefix);
                    resolve();
                    return;
                }

                if (file.type === 'application/pdf' || String(file.name || '').toLowerCase().endsWith('.pdf')) {
                    patchParentDraftState({
                        [`${prefix}_image_name_preview`]: String(file.name || ''),
                        [`${prefix}_image_url_preview`]: window.pdfPreviewPlaceholder || '',
                        [`${prefix}_image_visible_preview`]: '1',
                    });
                    patchParentSpecialState({
                        [`${prefix}_image_name_preview`]: String(file.name || ''),
                        [`${prefix}_image_url_preview`]: window.pdfPreviewPlaceholder || '',
                        [`${prefix}_image_visible_preview`]: '1',
                    });
                    resolve();
                    return;
                }

                const reader = new FileReader();
                reader.onload = function () {
                    patchParentDraftState({
                        [`${prefix}_image_name_preview`]: String(file.name || ''),
                        [`${prefix}_image_url_preview`]: String(reader.result || ''),
                        [`${prefix}_image_visible_preview`]: '1',
                    });
                    patchParentSpecialState({
                        [`${prefix}_image_name_preview`]: String(file.name || ''),
                        [`${prefix}_image_url_preview`]: String(reader.result || ''),
                        [`${prefix}_image_visible_preview`]: '1',
                    });
                    resolve();
                };
                reader.onerror = function () {
                    resolve();
                };
                reader.readAsDataURL(file);
            });
        }

        function persistParentImageDraftFromInput(prefix, input) {
            const file = input && input.files && input.files[0] ? input.files[0] : null;
            const preview = document.getElementById(prefix === 'father' ? 'imagePreview' : 'imagePreview1');
            const wrapper = preview ? preview.parentElement : null;

            if (!file) {
                persistParentImageDraft(prefix);
                return;
            }

            if (wrapper) {
                wrapper.style.display = 'block';
            }

            serializeParentPreview(prefix, file);
        }

        function restoreParentImageDraft(prefix) {
            const draft = Object.assign({}, getParentSpecialState(), getParentDraftState());
            const preview = document.getElementById(prefix === 'father' ? 'imagePreview' : 'imagePreview1');
            const imageNameNode = document.getElementById(prefix === 'father' ? 'imageName' : 'imageName1');
            const imageName = String(draft[`${prefix}_image_name_preview`] || '').trim();
            const imageUrl = String(draft[`${prefix}_image_url_preview`] || '').trim();
            const isVisible = String(draft[`${prefix}_image_visible_preview`] || '') === '1';
            const removeBtn = document.getElementById(prefix === 'father' ? 'removeImageBtn' : 'removeImageBtn1');
            const wrapper = preview ? preview.parentElement : null;

            if (!preview || !imageNameNode || (!imageName && !imageUrl)) {
                return;
            }

            preview.src = imageUrl || '#';
            preview.style.display = isVisible ? 'block' : 'none';
            preview.setAttribute('data-file-type', imageUrl === window.pdfPreviewPlaceholder ? 'pdf' : 'image');
            imageNameNode.textContent = imageName;

            if (wrapper) {
                wrapper.style.display = isVisible ? 'block' : 'none';
            }

            if (removeBtn) {
                removeBtn.style.display = isVisible ? 'inline-block' : 'none';
            }
        }

        function restoreExistingParentModeFromDraft() {
            const draft = Object.assign({}, getParentSpecialState(), getParentDraftState());
            const mode = String(draft.existing_registered_parent_mode || '').trim().toLowerCase();
            const selectedEmail = String(
                draft.existing_parent_email_selected
                || document.getElementById('existing_parent_email_selected_hidden')?.value
                || ''
            ).trim();
            const existingParentId = String(draft.existing_parent_id || '').trim();

            if (mode === 'yes') {
                const yesRadio = document.getElementById('existing_registered_parent_yes');
                if (yesRadio) {
                    yesRadio.checked = true;
                }
            } else if (mode === 'no') {
                const noRadio = document.getElementById('existing_registered_parent_no');
                if (noRadio) {
                    noRadio.checked = true;
                }
            }

            if (existingParentId) {
                document.getElementById('existing_parent_id').value = existingParentId;
            }

            parentState.isRestoringDraft = true;
            toggleExistingParentMode();

            if (mode === 'yes' && selectedEmail) {
                forceRestoreExistingParentEmailSelection(selectedEmail);
                ensureExistingParentAutofillFromSelection();
            }

            parentState.isRestoringDraft = false;
            persistExistingParentMode();
        }

        function restoreParentDraftUi() {
            restoreExistingParentModeFromDraft();
            const draft = Object.assign({}, getParentSpecialState(), getParentDraftState());
            const hiddenSelectedEmail = String(document.getElementById('existing_parent_email_selected_hidden')?.value || '').trim();
            const draftCity = String(draft.city || '').trim();
            const cityOptionsHtml = String(draft.city_options_html || '').trim();
            if (cityOptionsHtml) {
                document.getElementById('city').innerHTML = cityOptionsHtml;
            }
            if (draftCity) {
                setTimeout(() => syncCitySelection(draftCity), 0);
                setTimeout(() => syncCitySelection(draftCity), 120);
            }
            restoreParentImageDraft('father');
            restoreParentImageDraft('mother');
            setTimeout(() => {
                restoreParentImageDraft('father');
                restoreParentImageDraft('mother');
                const draftEmail = String(draft.existing_parent_email_selected || hiddenSelectedEmail || '').trim();
                if (draftEmail) {
                    forceRestoreExistingParentEmailSelection(draftEmail);
                    ensureExistingParentAutofillFromSelection();
                }
            }, 120);
            setTimeout(() => {
                const draftEmail = String(draft.existing_parent_email_selected || hiddenSelectedEmail || '').trim();
                if (draftEmail && isExistingParentSelected()) {
                    forceRestoreExistingParentEmailSelection(draftEmail);
                }
            }, 280);
        }

        window.__childModuleAfterDraftRestore = function () {
            restoreParentDraftUi();
        };

        window.__childModuleBeforeNavigate = function () {
            const fatherFile = document.getElementById('father_adhaar_card_image')?.files?.[0] || null;
            const motherFile = document.getElementById('mother_adhaar_card_image')?.files?.[0] || null;
            const draftCity = String(document.getElementById('city')?.value || '').trim();
            const selectedMode = document.querySelector('input[name="existing_registered_parent"]:checked');
            const selectedEmail = String(
                document.getElementById('existing_parent_email_select')?.value
                || document.getElementById('existing_parent_email_selected_hidden')?.value
                || ''
            ).trim();

            patchParentDraftState({
                city: draftCity,
                existing_registered_parent_mode: selectedMode ? String(selectedMode.value || 'no') : 'no',
                existing_parent_email_selected: selectedEmail,
                existing_parent_id: String(document.getElementById('existing_parent_id')?.value || ''),
            });
            patchParentSpecialState({
                city: draftCity,
                city_options_html: String(document.getElementById('city')?.innerHTML || ''),
                existing_registered_parent_mode: selectedMode ? String(selectedMode.value || 'no') : 'no',
                existing_parent_email_selected: selectedEmail,
                existing_parent_id: String(document.getElementById('existing_parent_id')?.value || ''),
            });

            return Promise.all([
                serializeParentPreview('father', fatherFile),
                serializeParentPreview('mother', motherFile),
            ]);
        };

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
                passwordField.readOnly = !!isReadonly;
            }

            if (confirmPasswordField) {
                confirmPasswordField.readOnly = !!isReadonly;
            }
        }

        function scheduleExistingParentLookup(showPopup = false) {
            if (!isExistingParentSelected()) {
                return;
            }

            if (parentState.lookupDebounce) {
                clearTimeout(parentState.lookupDebounce);
                parentState.lookupDebounce = null;
            }

            parentState.lookupDebounce = setTimeout(() => {
                parentState.lookupDebounce = null;
                lookupExistingParent(showPopup);
            }, 350);
        }

        function applyExistingParentImagePreview(options) {
            const preview = document.getElementById(options.previewId);
            const imageName = document.getElementById(options.imageNameId);
            const removeBtn = document.getElementById(options.removeBtnId);
            const input = document.getElementById(options.inputId);
            const wrapper = preview ? preview.parentElement : null;
            const hasImageName = !!String(options.imageName || '').trim();
            const hasImageUrl = !!String(options.imageUrl || '').trim();

            if (!preview || !imageName || !removeBtn || !input) {
                return;
            }

            if (hasImageUrl) {
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
                    wrapper.style.display = hasImageName ? 'block' : 'none';
                }
                preview.src = '#';
                preview.style.display = 'none';
                imageName.textContent = options.imageName || '';
                removeBtn.style.display = 'none';
                input.value = '';
            }
        }

        function fillExistingParentForm(parent) {
            document.getElementById('existing_parent_id').value = parent.id || '';
            const hiddenExistingParentField = document.getElementById('existing_registered_parent_hidden');
            if (hiddenExistingParentField) {
                hiddenExistingParentField.value = 'yes';
            }
            setExistingParentLoginFieldsReadonly(true);
            document.getElementById('father_name').value = parent.father_name || '';
            document.getElementById('mother_name').value = parent.mother_name || '';
            document.getElementById('contact_number').value = parent.contact_number || '';
            document.getElementById('alternative_contact_number').value = parent.alternative_contact_number || '';
            document.getElementById('email').value = parent.email || '';
            syncExistingParentEmailSelection(parent.email || '');
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
                        syncCitySelection(parentCityName);
                    }
                },
                error: function() {
                    cityField.innerHTML = '<option value="">Select City</option>';
                    if (parentCityName) {
                        cityField.insertAdjacentHTML('beforeend', `<option value="${parentCityName}">${parentCityName}</option>`);
                        syncCitySelection(parentCityName);
                    }
                }
            });

            patchParentDraftState({
                existing_registered_parent_mode: 'yes',
                existing_parent_email_selected: String(parent.email || ''),
                existing_parent_id: String(parent.id || ''),
                state: parentStateName,
                city: parentCityName,
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

            toggleEmailMode(isExisting);
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
                if (helpText) {
                    helpText.style.display = 'block';
                    helpText.textContent = 'Select an existing parent email from the dropdown to auto-fill the form.';
                }
                document.getElementById('father_adhaar_card_image').value = '';
                document.getElementById('mother_adhaar_card_image').value = '';
            } else {
                clearExistingParentAutofillFields();
                clearExistingParentSelection(true);
                if (passwordLabelRequired) passwordLabelRequired.style.display = 'inline';
                if (confirmPasswordLabelRequired) confirmPasswordLabelRequired.style.display = 'inline';
                if (passwordHint) passwordHint.textContent = '';
                if (helpText) {
                    helpText.style.display = 'none';
                    helpText.textContent = '';
                }
            }

            if (!parentState.isRestoringDraft) {
                persistExistingParentMode();
            }
        }

        function lookupExistingParent(showPopup = false) {
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
                    if (showPopup) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Parent Not Found',
                            text: 'The entered email address or username is not associated with an existing parent account.',
                            confirmButtonText: 'OK'
                        });
                    }
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
                patchParentSpecialState({
                    state: String(state || ''),
                });
                $('#city').html('<option>Loading...</option>');
                if (!state) {
                    $('#city').html('<option value="">Select City</option>');
                    patchParentSpecialState({
                        city: '',
                        city_options_html: String(document.getElementById('city')?.innerHTML || ''),
                    });
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

                        const draftCity = String(getParentDraftState().city || '').trim();
                        if (draftCity) {
                            syncCitySelection(draftCity);
                        }
                        patchParentSpecialState({
                            city_options_html: String(document.getElementById('city')?.innerHTML || ''),
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
                if (isExistingParentSelected()) {
                    ensureExistingParentAutofillFromSelection();
                }
            });

            $('#login_username, #email').on('input', function() {
                if (isExistingParentSelected()) {
                    document.getElementById('existing_parent_id').value = '';
                    setExistingParentLoginFieldsReadonly(false);
                    setExistingParentMessage('');
                    scheduleExistingParentLookup(false);
                }
            });

            $('#login_username, #email').on('change blur', function() {
                if (isExistingParentSelected()) {
                    scheduleExistingParentLookup(true);
                }
            });

            $('#existing_parent_email_select').on('change', function() {
                const selectedEmail = String(this.value || '').trim();
                document.getElementById('email').value = selectedEmail;
                patchParentDraftState({
                    existing_parent_email_selected: selectedEmail,
                    existing_registered_parent_mode: isExistingParentSelected() ? 'yes' : 'no',
                });
                const selectedParent = resolveExistingParentFromSelect(this);

                if (selectedParent) {
                    fillExistingParentForm(selectedParent);
                    setExistingParentLoginFieldsReadonly(true);
                    persistExistingParentMode();
                    setExistingParentMessage('Existing parent details have been auto-filled successfully.');
                } else {
                    clearExistingParentAutofillFields();
                    clearExistingParentSelection(false);
                }
            });

            $('#father_adhaar_card_image').on('change', function() {
                setTimeout(() => persistParentImageDraftFromInput('father', this), 0);
            });

            $('#mother_adhaar_card_image').on('change', function() {
                setTimeout(() => persistParentImageDraftFromInput('mother', this), 0);
            });

            $('#city').on('change', function() {
                patchParentSpecialState({
                    city: String(this.value || ''),
                    city_options_html: String(this.innerHTML || ''),
                });
            });

            restoreParentDraftUi();
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

                    if (typeof window.__childModuleClearAllState === 'function') {
                        window.__childModuleClearAllState();
                    } else if (typeof window.__childModuleClearDraft === 'function') {
                        window.__childModuleClearDraft();
                    }
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
            patchParentDraftState({
                father_image_name_preview: '',
                father_image_url_preview: '',
                father_image_visible_preview: '0',
            });
            patchParentSpecialState({
                father_image_name_preview: '',
                father_image_url_preview: '',
                father_image_visible_preview: '0',
            });
        });
        document.getElementById('removeImageBtn1').addEventListener('click', function() {
            window.clearImageSelection({
                imagePreviewSelector: '#imagePreview1',
                imageNameSelector: '#imageName1',
                imageInputSelector: '#mother_adhaar_card_image',
                removeImageBtnSelector: '#removeImageBtn1'
            });
            patchParentDraftState({
                mother_image_name_preview: '',
                mother_image_url_preview: '',
                mother_image_visible_preview: '0',
            });
            patchParentSpecialState({
                mother_image_name_preview: '',
                mother_image_url_preview: '',
                mother_image_visible_preview: '0',
            });
        });
    </script>
@endsection
