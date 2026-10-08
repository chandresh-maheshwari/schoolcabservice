(function () {
    'use strict';
    let libraryPromise;
    const languageCache = {};
    let queue = Promise.resolve();
    const script = url => new Promise((resolve, reject) => {
        const element = document.createElement('script');
        element.src = url;
        element.onload = resolve;
        element.onerror = () => { element.remove(); reject(new Error('Reading tools could not load. Please retry or enter the details manually.')); };
        document.head.appendChild(element);
    });
    async function loadTools(base) {
        if (!libraryPromise) libraryPromise = script(base + 'tesseract/tesseract.min.js').catch(error => { libraryPromise = null; throw error; });
        await libraryPromise;
    }
    async function availableLanguages(base, type) {
        if (type !== 'aadhaar') return 'eng';
        if (languageCache[base]) return languageCache[base];
        const candidates = ['eng', 'hin', 'guj', 'mar', 'ben', 'pan', 'tam', 'tel', 'kan', 'mal', 'urd', 'ori', 'asm'];
        const found = [];
        for (const lang of candidates) {
            try {
                const response = await fetch(`${base}lang/${lang}.traineddata.gz`, {method: 'HEAD', cache: 'force-cache'});
                if (response.ok) found.push(lang);
            } catch (error) {}
        }
        languageCache[base] = found.length ? found.join('+') : 'eng';
        return languageCache[base];
    }
    async function imageCanvas(file, documentType) {
        const url = URL.createObjectURL(file);
        try {
            const img = new Image();
            img.src = url;
            await img.decode();
            if (!img.naturalWidth || !img.naturalHeight || img.naturalWidth * img.naturalHeight > 50000000) throw new Error('Image is too large. Upload a smaller, clear image.');
            const enhancedDocument = documentType === 'license' || documentType === 'vehicle-rc' || documentType === 'vehicle-insurance';
            const isLicense = documentType === 'license';
            const maxSide = isLicense ? 3000 : (enhancedDocument ? 3600 : 2600);
            const maxScale = isLicense ? 3 : (enhancedDocument ? 4 : 2);
            const scale = Math.min(maxScale, maxSide / Math.max(img.naturalWidth, img.naturalHeight));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(img.naturalWidth * scale);
            canvas.height = Math.round(img.naturalHeight * scale);
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
            if (isLicense) {
                ctx.filter = 'contrast(150%) brightness(108%)';
            } else if (enhancedDocument) {
                ctx.filter = 'grayscale(100%) contrast(160%) brightness(110%)';
            }
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            ctx.filter = 'none';
            return canvas;
        } catch (error) {
            throw new Error('This image could not be read. Use a clear JPG, PNG, WEBP, BMP, GIF or PDF.');
        } finally { URL.revokeObjectURL(url); }
    }
    function cropCanvas(source, xRatio, yRatio, widthRatio, heightRatio, scaleMultiplier, mildContrast) {
        const sourceWidth = source.width;
        const sourceHeight = source.height;
        const sx = Math.max(0, Math.round(sourceWidth * xRatio));
        const sy = Math.max(0, Math.round(sourceHeight * yRatio));
        const sw = Math.min(sourceWidth - sx, Math.round(sourceWidth * widthRatio));
        const sh = Math.min(sourceHeight - sy, Math.round(sourceHeight * heightRatio));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(sw * scaleMultiplier));
        canvas.height = Math.max(1, Math.round(sh * scaleMultiplier));
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.filter = mildContrast ? 'grayscale(100%) contrast(120%) brightness(105%)' : 'grayscale(100%) contrast(190%) brightness(115%)';
        ctx.drawImage(source, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
        ctx.filter = 'none';
        if (mildContrast && mildContrast !== 'raw') {
            // Otsu threshold separates printed text from a photographed grey background.
            const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const histogram = new Array(256).fill(0);
            for (let i = 0; i < pixels.data.length; i += 4) histogram[pixels.data[i]]++;
            const count = canvas.width * canvas.height;
            const sum = histogram.reduce((total, amount, level) => total + amount * level, 0);
            let backgroundCount = 0, backgroundSum = 0, bestVariance = 0, threshold = 128;
            for (let level = 0; level < 256; level++) {
                backgroundCount += histogram[level];
                backgroundSum += histogram[level] * level;
                const foregroundCount = count - backgroundCount;
                if (!backgroundCount || !foregroundCount) continue;
                const difference = backgroundSum / backgroundCount - (sum - backgroundSum) / foregroundCount;
                const variance = backgroundCount * foregroundCount * difference * difference;
                if (variance > bestVariance) { bestVariance = variance; threshold = level; }
            }
            threshold *= 0.5;
            for (let i = 0; i < pixels.data.length; i += 4) {
                const value = pixels.data[i] <= threshold ? 0 : 255;
                pixels.data[i] = pixels.data[i + 1] = pixels.data[i + 2] = value;
            }
            ctx.putImageData(pixels, 0, 0);
        }
        return canvas;
    }
    function licenseTextCanvas(source) {
        const canvas = document.createElement('canvas');
        canvas.width = source.width;
        canvas.height = source.height;
        const ctx = canvas.getContext('2d', {willReadFrequently: true});
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(source, 0, 0);
        const image = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const pixels = image.data;
        for (let i = 0; i < pixels.length; i += 4) {
            // Security lines are commonly orange/red. Their red channel is
            // light, while the printed licence number remains dark.
            const value = pixels[i] < 165 ? 0 : 255;
            pixels[i] = pixels[i + 1] = pixels[i + 2] = value;
        }
        ctx.putImageData(image, 0, 0);
        return canvas;
    }
    function init(panel) {
        const scope = panel.closest('form') || document;
        const findInput = id => Array.from(scope.querySelectorAll('input[type="file"]')).find(element => element.id === id);
        const input = findInput(panel.dataset.inputId);
        if (!input || panel.dataset.bound) return;
        const form = input.form;
        if (!form) return;
        panel.dataset.bound = 'true';
        const extraInputs = (panel.dataset.extraInputIds || '').split(',').map(id => findInput(id.trim())).filter(Boolean);
        const scanInputs = [input, ...extraInputs];
        let activeInput = input;
        let pendingFrontFile = null;
        const status = panel.querySelector('[data-ocr-status]');
        const results = panel.querySelector('[data-ocr-results]');
        const retry = panel.querySelector('[data-ocr-retry]');
        const cancel = panel.querySelector('[data-ocr-cancel]');
        const base = panel.dataset.vendorBase;
        const type = panel.dataset.documentType;
        let labels = {};
        try {
            labels = panel.dataset.fieldMap ? JSON.parse(panel.dataset.fieldMap) : {};
        } catch (error) {
            labels = {};
        }
        const fields = Object.fromEntries(Object.keys(labels).map(key => [key, form.querySelector(`[name="${key}"]`)]));
        if (type === 'license' && fields.license_no && !panel.dataset.initialDocumentNumber) {
            panel.dataset.initialDocumentNumber = String(fields.license_no.value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        }
        if (type === 'vehicle-rc' && !panel.dataset.initialDocumentNumber) {
            const savedRcNumber = fields.rc_number || fields.vehicle_number;
            panel.dataset.initialDocumentNumber = String(savedRcNumber ? savedRcNumber.value : '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        }
        if (type === 'aadhaar' && !panel.dataset.initialAadhaarNumber) {
            const savedAadhaarNumber = fields.adher_no
                || fields.father_aadhaar_number
                || fields.mother_aadhaar_number
                || fields.child_aadhaar_number;
            panel.dataset.initialAadhaarNumber = String(savedAadhaarNumber ? savedAadhaarNumber.value : '').replace(/\D/g, '');
        }
        if (form && !form.dataset.documentPairValidationBound) {
            form.dataset.documentPairValidationBound = 'true';
            const stopInvalidDocumentPair = event => {
                form.querySelectorAll('.document-pair-error').forEach(error => error.remove());
                const validation = window.validateDriverDocumentPairs(form);
                if (validation.valid) return false;
                event.preventDefault();
                event.stopImmediatePropagation();
                const target = validation.target || form.querySelector(validation.selector);
                if (target) {
                    const error = document.createElement('span');
                    error.className = 'error-message document-pair-error';
                    error.style.color = 'red';
                    error.textContent = validation.message;
                    target.insertAdjacentElement('afterend', error);
                    target.scrollIntoView({behavior: 'smooth', block: 'center'});
                }
                if (typeof window.notify === 'function') window.notify('error', validation.message);
                return true;
            };
            form.addEventListener('click', event => {
                const button = event.target.closest('#submitBtn, #updateBtn');
                if (!button) return;
                stopInvalidDocumentPair(event);
            }, true);
            form.addEventListener('submit', stopInvalidDocumentPair, true);
        }
        let generation = 0, active = null, cityOptionsObserver = null;
        const autoFilled = {};
        const autoFilledAtEdit = {};
        const autoFilledByInput = {};
        const edits = {};
        Object.entries(fields).forEach(([key, field]) => {
            edits[key] = 0;
            if (field) field.addEventListener('input', () => { edits[key]++; });
        });
        const message = text => { status.textContent = text; };
        function placePanelBelow(sourceInput) {
            const fieldGroup = sourceInput && sourceInput.closest('.form-group, .add-child-upload-card');
            if (!fieldGroup) return;

            const followingPreview = fieldGroup.nextElementSibling;
            const anchor = followingPreview
                && followingPreview !== panel
                && followingPreview.classList.contains('dlt_btn_div')
                ? followingPreview
                : fieldGroup;
            anchor.insertAdjacentElement('afterend', panel);
        }
        function stop(clear) {
            generation++;
            if (cityOptionsObserver) { cityOptionsObserver.disconnect(); cityOptionsObserver = null; }
            if (active) {
                active.cancelled = true;
                active.abort();
                if (active.worker) active.worker.terminate().catch(() => {});
                if (active.pdfTask) active.pdfTask.destroy().catch(() => {});
                active = null;
            }
            cancel.hidden = true;
            if (clear) { results.replaceChildren(); message(''); retry.hidden = true; }
        }
        function clearScannedPreview(clearFile) {
            const targetInput = activeInput || input;
            if (clearFile && type === 'aadhaar') {
                // A panel manages both front and back inputs. Always operate
                // on the current input; using the panel's primary input here
                // removed the front preview after a back-side OCR failure.
                const uploadGroup = targetInput.closest('.form-group, .add-child-upload-card');
                const previewGroup = uploadGroup && uploadGroup.nextElementSibling && uploadGroup.nextElementSibling.classList.contains('dlt_btn_div')
                    ? uploadGroup.nextElementSibling
                    : null;

                [uploadGroup, previewGroup].filter(Boolean).forEach(group => {
                    group.querySelectorAll('span[id*="imageName"], .add-child-file-name').forEach(el => {
                        el.textContent = '';
                    });
                    group.querySelectorAll('img').forEach(el => {
                        el.removeAttribute('src');
                        el.style.display = 'none';
                    });
                    group.querySelectorAll('button[id*="removeImageBtn"]').forEach(el => {
                        el.style.display = 'none';
                    });
                });
            }
            if (clearFile) targetInput.value = '';
        }
        function showValues(parsed, snapshot, capturedEdits, file, token) {
            let missingAadhaarNumber = false;
            const isExtraInput = activeInput && activeInput !== input;
            const isAadhaarBackInput = type === 'aadhaar' && isExtraInput;
            const isLicenseBackInput = type === 'license' && isExtraInput;
            const backSideSkipFields = new Set(['driver_name', 'father_name', 'mother_name', 'child_name']);
            const licenseBackSkipFields = new Set(['driver_name', 'license_no', 'license_expiry_date']);
            if (type === 'aadhaar') {
                const expectedSide = isAadhaarBackInput ? 'back' : 'front';
                if (parsed.aadhaar_side !== expectedSide) {
                    const error = parsed.aadhaar_side === 'unknown'
                        ? `Aadhaar ${expectedSide} side could not be verified. Please select a clear ${expectedSide} side image.`
                        : `Only the Aadhaar ${expectedSide} side is allowed here. Please select the ${expectedSide} side image.`;
                    delete activeInput.dataset.scannedAadhaarNumber;
                    delete activeInput.dataset.scannedDriverName;
                    delete activeInput.dataset.aadhaarSide;
                    delete activeInput.dataset.aadhaarVerified;
                    clearScannedPreview(true);
                    results.replaceChildren();
                    retry.hidden = true;
                    message(error);
                    window.alert(error);
                    return;
                }
            }
            const isMotherAadhaar = type === 'aadhaar' && Object.prototype.hasOwnProperty.call(fields, 'mother_aadhaar_number');
            const isFatherAadhaar = type === 'aadhaar' && Object.prototype.hasOwnProperty.call(fields, 'father_aadhaar_number');
            const requiredGender = isMotherAadhaar ? 'Female' : (isFatherAadhaar ? 'Male' : '');
            const parentLabel = isMotherAadhaar ? 'Mother' : 'Father';
            const wrongGender = parsed.gender && parsed.gender !== requiredGender;
            if (requiredGender && (wrongGender || (!isAadhaarBackInput && parsed.gender !== requiredGender))) {
                const error = wrongGender
                    ? `Only a ${requiredGender.toLowerCase()} Aadhaar card is allowed for ${parentLabel}. Please select the correct Aadhaar card.`
                    : `${parentLabel} Aadhaar gender could not be verified as ${requiredGender}. Please select a clear Aadhaar front image showing ${requiredGender}.`;
                delete activeInput.dataset.scannedAadhaarNumber;
                delete activeInput.dataset.scannedDriverName;
                delete activeInput.dataset.aadhaarSide;
                delete activeInput.dataset.aadhaarVerified;
                clearScannedPreview(true);
                results.replaceChildren();
                retry.hidden = true;
                message(error);
                window.alert(error);
                return;
            }
            if ((type === 'aadhaar' || type === 'license') && activeInput && parsed.driver_name) {
                activeInput.dataset.scannedDriverName = parsed.driver_name;
            }
            if (type === 'aadhaar' && activeInput) {
                const scannedAadhaar = window.normalizeAadhaarDigits ? window.normalizeAadhaarDigits(parsed.adher_no || parsed.father_aadhaar_number || parsed.mother_aadhaar_number || '') : String(parsed.adher_no || parsed.father_aadhaar_number || parsed.mother_aadhaar_number || '').replace(/\D/g, '');
                const expectedSide = isAadhaarBackInput ? 'back' : 'front';
                if (scannedAadhaar.length !== 12) {
                    delete activeInput.dataset.scannedAadhaarNumber;
                    delete activeInput.dataset.aadhaarSide;
                    delete activeInput.dataset.aadhaarVerified;
                    // Keep the selected file. Clearing the native input here
                    // caused Laravel to report "back image is required" even
                    // though the user had selected a file. The form validator
                    // will instead stop submission until it can be verified.
                    missingAadhaarNumber = true;
                } else {
                activeInput.dataset.scannedAadhaarNumber = scannedAadhaar;
                activeInput.dataset.aadhaarSide = parsed.aadhaar_side;
                activeInput.dataset.aadhaarVerified = 'true';
                activeInput.dispatchEvent(new CustomEvent('aadhaar-number-scanned', { bubbles: true, detail: { aadhaarNumber: scannedAadhaar, side: parsed.aadhaar_side } }));
                }
            }
            if ((type === 'vehicle-rc' || type === 'vehicle-insurance') && activeInput) {
                const scannedVehicleNumber = String(parsed.document_vehicle_number || parsed.vehicle_number || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
                if (scannedVehicleNumber) {
                    activeInput.dataset.scannedVehicleNumber = scannedVehicleNumber;
                    activeInput.dispatchEvent(new CustomEvent('vehicle-number-scanned', { bubbles: true, detail: { vehicleNumber: scannedVehicleNumber } }));
                }
            }
            if ((type === 'license' || type === 'vehicle-rc') && activeInput) {
                const documentNumber = String(type === 'license' ? parsed.license_no : (parsed.rc_number || parsed.vehicle_number || ''))
                    .replace(/[^A-Za-z0-9]/g, '')
                    .toUpperCase();
                if (documentNumber) {
                    activeInput.dataset.scannedDocumentNumber = documentNumber;
                    activeInput.dispatchEvent(new CustomEvent('document-number-scanned', { bubbles: true, detail: { documentNumber, documentType: type } }));
                }
            }
            results.replaceChildren();
            // Queue the scanned city before state change starts loading cities.
            if (fields.city && fields.city.tagName === 'SELECT' && parsed.city
                && edits.city === capturedEdits.city) {
                fields.city.dataset.ocrPendingCity = parsed.city;
            }
            let detected = 0;
            let filled = 0;
            for (const [key, field] of Object.entries(fields)) {
                if (isAadhaarBackInput && backSideSkipFields.has(key)) {
                    continue;
                }
                if (isLicenseBackInput && licenseBackSkipFields.has(key)) {
                    continue;
                }
                const value = parsed[key];
                if (!field) continue;
                if (!value) {
                    // A newly selected document must not leave a value that
                    // was auto-filled from the previous file. Preserve it
                    // only when the user edited that field after the scan.
                    if (autoFilled[key]
                        && autoFilledByInput[key] === activeInput
                        && edits[key] === autoFilledAtEdit[key]) {
                        field.value = '';
                        field.dispatchEvent(new Event('input', {bubbles: true}));
                        field.dispatchEvent(new Event('change', {bubbles: true}));
                        field.classList.remove('border-info');
                        delete autoFilled[key];
                        delete autoFilledAtEdit[key];
                        delete autoFilledByInput[key];
                    }
                    continue;
                }
                detected++;
                const row = document.createElement('div');
                row.className = 'small mb-2';
                const summary = document.createElement('span');
                summary.textContent = `${labels[key]}: ${value} `;
                row.appendChild(summary);
                const apply = () => {
                    if (token !== generation || activeInput.files[0] !== file) return;
                    filled++;
                    if (field.type === 'radio') {
                        const radios = Array.from(form.querySelectorAll(`[name="${key}"]`));
                        const radio = radios.find(option => String(option.value || '').toLowerCase() === String(value || '').toLowerCase());
                        if (radio) {
                            radio.checked = true;
                            radio.dispatchEvent(new Event('input', {bubbles: true}));
                            radio.dispatchEvent(new Event('change', {bubbles: true}));
                            radio.classList.add('border-info');
                        }
                        autoFilled[key] = value;
                        autoFilledAtEdit[key] = edits[key];
                        autoFilledByInput[key] = activeInput;
                        summary.textContent = `${labels[key]} filled. Please check the field. `;
                        return;
                    }
                    if (field.tagName === 'SELECT' && value && !Array.from(field.options).some(option => option.value === value)) {
                        field.add(new Option(value, value, true, true));
                    }
                    if (key === 'city' && field.tagName === 'SELECT') {
                        field.dataset.ocrPendingCity = value;
                    }
                    field.value = value;
                    field.dispatchEvent(new Event('input', {bubbles: true}));
                    field.dispatchEvent(new Event('change', {bubbles: true}));
                    if (key === 'city' && field.tagName === 'SELECT') {
                        setTimeout(() => {
                            if (token !== generation || activeInput.files[0] !== file
                                || edits[key] !== autoFilledAtEdit[key]) return;
                            if (!Array.from(field.options).some(option => option.value === value)) {
                                field.add(new Option(value, value, true, true));
                            }
                            field.value = value;
                            field.dispatchEvent(new Event('input', {bubbles: true}));
                            field.dispatchEvent(new Event('change', {bubbles: true}));
                            autoFilledAtEdit[key] = edits[key];
                        }, 800);
                    }
                    field.classList.add('border-info');
                    autoFilled[key] = value;
                    autoFilledAtEdit[key] = edits[key];
                    autoFilledByInput[key] = activeInput;
                    if (key === 'city' && field.tagName === 'SELECT' && typeof MutationObserver !== 'undefined') {
                        if (cityOptionsObserver) cityOptionsObserver.disconnect();
                        cityOptionsObserver = new MutationObserver(() => {
                            if (token !== generation || activeInput.files[0] !== file
                                || edits[key] !== autoFilledAtEdit[key] || field.value === value) return;
                            if (!Array.from(field.options).some(option => option.value === value)) {
                                field.add(new Option(value, value));
                            }
                            field.value = value;
                            field.dispatchEvent(new Event('change', {bubbles: true}));
                        });
                        cityOptionsObserver.observe(field, {childList: true, subtree: true});
                    }
                    summary.textContent = `${labels[key]} filled. Please check the field. `;
                };
                const currentRadio = field.type === 'radio' ? form.querySelector(`[name="${key}"]:checked`) : null;
                const replaceSavedDocumentValue = (
                    (type === 'aadhaar'
                        && (field.value === snapshot[key]
                            || (key === 'city' && field.dataset.ocrPendingCity === value)))
                    ||
                    (type === 'license'
                        && !isLicenseBackInput
                        && (key === 'license_no' || key === 'license_expiry_date'))
                    || (type === 'vehicle-insurance'
                        && (key === 'insurance_number' || key === 'insurance_expiry_date'))
                ) && edits[key] === capturedEdits[key];
                if (field.type === 'radio' && !currentRadio) apply();
                else if (!snapshot[key].trim() && field.value === snapshot[key] && edits[key] === capturedEdits[key]) apply();
                else if (autoFilled[key] && edits[key] === autoFilledAtEdit[key]) apply();
                else if (replaceSavedDocumentValue) apply();
                else if (field.value !== value) {
                    const button = document.createElement('button');
                    button.type = 'button'; button.className = 'btn btn-sm btn-outline-primary ml-2';
                    button.textContent = 'Use scanned value';
                    button.addEventListener('click', () => { apply(); button.remove(); });
                    row.appendChild(button);
                }
                results.appendChild(row);
            }
            if (isLicenseBackInput && activeInput && activeInput.dataset.scannedDocumentNumber && !detected) {
                const row = document.createElement('div');
                row.className = 'small mb-2';
                const summary = document.createElement('span');
                summary.textContent = `License Number verified from back side: ${activeInput.dataset.scannedDocumentNumber}`;
                row.appendChild(summary);
                results.appendChild(row);
                detected = 1;
            }
            const expected = Object.entries(fields).filter(([key, field]) => {
                if (!field) return false;
                if (isAadhaarBackInput && backSideSkipFields.has(key)) return false;
                if (isLicenseBackInput && licenseBackSkipFields.has(key)) return false;
                return true;
            }).length;
            if (!detected) {
                message(isLicenseBackInput
                    ? 'License back side could not be verified. Upload a clearer back side showing the license number, or enter/check the details manually.'
                    : 'No reliable details found. Try a clearer image, the other side, or enter the details manually.');
            } else if (isLicenseBackInput) {
                message('License back side verified. Front-side details were kept unchanged.');
            } else if (filled === detected) {
                message(`${detected} of ${expected} details found and filled. Please review the fields.`);
            } else if (filled > 0) {
                message(`${detected} of ${expected} details found. New scanned values were filled; manually edited values were kept.`);
            } else {
                message(`${detected} of ${expected} details found. Review the fields; existing values were kept.`);
            }
            if (type === 'vehicle-rc' && (!parsed.vehicle_number || !parsed.rc_number)) {
                const missingMessage = ' RC number / vehicle number was not found. Please upload the RC book front side; driving licence or other documents will not fill RC details.';
                message(status.textContent + missingMessage);
            }
            if (parsed.ambiguous.length) message(status.textContent + ' Multiple possible values found; check those fields manually.');
            if (missingAadhaarNumber) message(status.textContent + ' Aadhaar number could not be read. Upload a clearer, unmasked image before submitting.');
            if (type === 'aadhaar' && parsed.address_requires_manual_review) {
                message(status.textContent + ' Address text was unclear, so it was not auto-filled. Upload a clearer Aadhaar back side or enter the address manually.');
            }
            // OCR can fail on a valid uploaded document. Keep both selected
            // files and their previews so the user can retry/replace only the
            // relevant side; never remove the front when scanning the back.
        }
        function start() {
            stop(true);
            const sourceInput = activeInput || input;
            const file = sourceInput.files && sourceInput.files[0];
            if (!file) return;
            placePanelBelow(sourceInput);
            if (type === 'vehicle-rc' || type === 'vehicle-insurance') delete sourceInput.dataset.scannedVehicleNumber;
            if (type === 'license' || type === 'vehicle-rc') delete sourceInput.dataset.scannedDocumentNumber;
            if (type === 'aadhaar' || type === 'license') delete sourceInput.dataset.scannedDriverName;
            if (type === 'aadhaar') {
                delete sourceInput.dataset.scannedAadhaarNumber;
                delete sourceInput.dataset.aadhaarSide;
                delete sourceInput.dataset.aadhaarVerified;
            }
            retry.hidden = false;
            if (file.size > 20 * 1024 * 1024) { message('Use a file smaller than 20 MB for auto-fill.'); return; }
            if (!/\.(jpe?g|png|webp|bmp|gif|pdf)$/i.test(file.name)) {
                message('Auto-fill supports JPG, PNG, WEBP, BMP, GIF and PDF. Convert other file types first.'); return;
            }
            const token = generation;
            const snapshot = Object.fromEntries(Object.entries(fields).map(([key, field]) => [key, field ? field.value : '']));
            const capturedEdits = {...edits};
            cancel.hidden = false;
            message(`Preparing to read ${file.name}...`);
            queue = queue.catch(() => {}).then(async () => {
                if (token !== generation || activeInput.files[0] !== file) return;
                const job = {worker: null, pdfTask: null, cancelled: false};
                const cancelled = new Promise((_, reject) => { job.abort = () => reject(new Error('Reading cancelled.')); });
                active = job;
                let timeout;
                const current = () => !job.cancelled && token === generation && activeInput.files[0] === file;
                const ensureCurrent = () => { if (!current()) throw new Error('Reading cancelled.'); };
                let confirmedAadhaarNumber = '';
                let focusedAddress = null;
                let addressRegionAttempted = false;
                async function read() {
                    let worker;
                    let aadhaarLayout = '';
                    let lastConfidence = 0;
                    let addressConfidence = -1;
                    let parseAddress = text => DriverDocumentParser.parse(text, 'aadhaar');
                    function considerAddress(text) {
                        const candidate = parseAddress(text);
                        if (candidate.current_address && candidate.pincode && candidate.state
                            && !candidate.address_requires_manual_review && lastConfidence > addressConfidence
                            && text.toLowerCase().includes(candidate.state.toLowerCase())) {
                            focusedAddress = candidate;
                            addressConfidence = lastConfidence;
                        }
                    }
                    async function recognize(canvas, pageSegMode, numbersOnly) {
                        ensureCurrent();
                        if (!worker) {
                            await loadTools(base); ensureCurrent();
                            const workerLanguages = await availableLanguages(base, type);
                            worker = await Tesseract.createWorker(workerLanguages, 1, {
                                workerPath: base + 'tesseract/worker.min.js', corePath: base + 'core', langPath: base + 'lang',
                                logger: progress => {
                                    if (current()) {
                                        message(progress.status === 'recognizing text'
                                            ? `Reading ${file.name}... ${Math.round(progress.progress * 100)}%`
                                            : `Loading reading tools for ${file.name}...`);
                                    }
                                }
                            });
                            job.worker = worker;
                            if (!current()) { await worker.terminate(); ensureCurrent(); }
                            await worker.setParameters({preserve_interword_spaces: '1'});
                        }
                        // Aadhaar address crops are a compact text block.
                        // PSM 6 reads each line in that block more accurately
                        // than the automatic whole-document layout mode.
                        await worker.setParameters({
                            preserve_interword_spaces: '1',
                            tessedit_char_whitelist: numbersOnly === 'address'
                                ? '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz /,:.-#'
                                : (numbersOnly ? '0123456789 ' : ''),
                            tessedit_pageseg_mode: String(pageSegMode || 3)
                        });
                        const {data} = await worker.recognize(canvas, {}, {text: true, tsv: type === 'aadhaar'});
                        lastConfidence = data.confidence;
                        if (type === 'aadhaar' && pageSegMode === 11) aadhaarLayout = data.tsv || '';
                        ensureCurrent();
                        return data.confidence >= 35 ? data.text : '';
                    }
                    if (/\.pdf$/i.test(file.name)) {
                        const pdfjs = await import(base + 'pdf/pdf.min.mjs'); ensureCurrent();
                        pdfjs.GlobalWorkerOptions.workerSrc = base + 'pdf/pdf.worker.min.mjs';
                        job.pdfTask = pdfjs.getDocument({data: new Uint8Array(await file.arrayBuffer()),
                            cMapUrl: base + 'pdf/cmaps/', cMapPacked: true, standardFontDataUrl: base + 'pdf/standard_fonts/',
                            wasmUrl: base + 'pdf/wasm/', isEvalSupported: false});
                        const pdf = await job.pdfTask.promise;
                        const maxPages = Math.min(pdf.numPages, type === 'vehicle-insurance' ? 8 : 4);
                        let text = '';
                        for (let i = 1; i <= maxPages; i++) {
                            ensureCurrent(); message(`Reading ${file.name}, PDF page ${i} of ${pdf.numPages}...`);
                            const page = await pdf.getPage(i);
                            const content = await page.getTextContent();
                            let pageText = '', lastY = null;
                            for (const item of content.items) {
                                if (typeof item.str !== 'string') continue;
                                const y = item.transform[5];
                                if (lastY !== null && Math.abs(y - lastY) > 4) pageText += '\n';
                                pageText += item.str + (item.hasEOL ? '\n' : ' '); lastY = y;
                            }
                            const extracted = DriverDocumentParser.parse(pageText, type);
                            if (!Object.keys(fields).every(key => extracted[key])) {
                                const viewport = page.getViewport({scale: 1});
                                const scaled = page.getViewport({scale: Math.min(3, 2600 / Math.max(viewport.width, viewport.height))});
                                const canvas = document.createElement('canvas');
                                canvas.width = Math.ceil(scaled.width); canvas.height = Math.ceil(scaled.height);
                                await page.render({canvasContext: canvas.getContext('2d'), viewport: scaled}).promise;
                                const scanned = await recognize(canvas);
                                pageText += '\n' + scanned;
                                if (type === 'aadhaar') pageText += '\n' + await recognize(canvas, 11);
                                canvas.width = canvas.height = 0;
                            }
                            text += '\n' + pageText; page.cleanup();
                            const collected = DriverDocumentParser.parse(text, type);
                            if (Object.keys(fields).every(key => collected[key])) {
                                break;
                            }
                        }
                        return text;
                    }
                    const canvas = await imageCanvas(file, type);
                    try {
                        const fullText = await recognize(canvas);
                        const licenseSparseText = type === 'license' ? await recognize(canvas, 11) : '';
                        let licenseCleanText = '';
                        let cleanLicenseNumber = '';
                        if (type === 'license') {
                            const cleanCanvas = licenseTextCanvas(canvas);
                            try {
                                licenseCleanText = await recognize(cleanCanvas, 11);
                            } finally {
                                cleanCanvas.width = cleanCanvas.height = 0;
                            }
                            cleanLicenseNumber = DriverDocumentParser.parse(licenseCleanText, type).license_no;
                        }
                        // Sparse-text mode is substantially better at finding
                        // Aadhaar's isolated 12-digit line when the upload is
                        // a phone screenshot with borders/empty margins.
                        const aadhaarNumberText = type === 'aadhaar' ? await recognize(canvas, 11) : '';
                        let focusedNameText = '';
                        let focusedBackText = '';
                        if (aadhaarLayout) {
                            const layoutWords = aadhaarLayout.trim().split('\n').slice(1).map(row => row.split('\t'))
                                .filter(row => row[0] === '5' && row[11] && Number(row[10]) >= 0);
                            const words = layoutWords.filter(row => Number(row[10]) >= 30);
                            function parseFocusedAddress(text) {
                                text = text.replace(/\b([SCDW])\s*\/\s*0\s*:/gi, '$1/O:');
                                const tokens = [...text.matchAll(/[A-Za-z]+/g)];
                                const reliable = layoutWords.filter(word => Number(word[10]) >= 85
                                    && /^[A-Za-z]+[,.:]?$/.test(word[11])).map(word => word[11].replace(/[,.:]$/, ''));
                                const oneEditApart = (left, right) => {
                                    if (Math.abs(left.length - right.length) > 1) return false;
                                    let i = 0, j = 0, differences = 0;
                                    while (i < left.length && j < right.length) {
                                        if (left[i] === right[j]) { i++; j++; continue; }
                                        if (++differences > 1) return false;
                                        if (left.length >= right.length) i++;
                                        if (right.length >= left.length) j++;
                                    }
                                    return differences + (left.length - i) + (right.length - j) === 1;
                                };
                                // Correct a letter only when an independent high-confidence
                                // pass agrees on both surrounding words.
                                for (let index = tokens.length - 2; index > 0; index--) {
                                    const token = tokens[index];
                                    const candidate = reliable.find((word, position) => position > 0 && position < reliable.length - 1
                                        && reliable[position - 1].toLowerCase() === tokens[index - 1][0].toLowerCase()
                                        && reliable[position + 1].toLowerCase() === tokens[index + 1][0].toLowerCase()
                                        && oneEditApart(word.toLowerCase(), token[0].toLowerCase()));
                                    if (candidate) text = text.slice(0, token.index) + candidate + text.slice(token.index + token[0].length);
                                }
                                text = text.replace(/([A-Z]{2,})\r?\n(?=[A-Z]{2,}\b)/g, '$1 ');
                                return DriverDocumentParser.parse(text, 'aadhaar');
                            }
                            parseAddress = parseFocusedAddress;
                            const rows = [];
                            layoutWords.forEach(word => {
                                const center = Number(word[7]) + Number(word[9]) / 2;
                                const row = rows.find(group => Math.abs(group.center - center)
                                    <= Math.max(group.height, Number(word[9])) * 0.5);
                                if (row) row.words.push(word);
                                else rows.push({center, height: Number(word[9]), words: [word]});
                            });
                            async function readRegion(regionWords, mode, numbersOnly, addressRegion) {
                                const left = Math.min(...regionWords.map(row => Number(row[6])));
                                const top = Math.min(...regionWords.map(row => Number(row[7])));
                                const right = Math.max(...regionWords.map(row => Number(row[6]) + Number(row[8])));
                                const bottom = Math.max(...regionWords.map(row => Number(row[7]) + Number(row[9])));
                                const margin = addressRegion ? 8 : Math.max(8, (bottom - top) * 0.15);
                                const letterHeights = regionWords.map(row => Number(row[9])).filter(height => height > 0).sort((a, b) => a - b);
                                const letterHeight = letterHeights[Math.floor(letterHeights.length / 2)];
                                const scale = addressRegion ? Math.max(1, Math.min(3, 40 / letterHeight)) : 2;
                                const region = cropCanvas(canvas, Math.max(0, left - margin) / canvas.width,
                                    Math.max(0, top - margin) / canvas.height,
                                    (right - left + margin * 2) / canvas.width,
                                    (bottom - top + margin * 2) / canvas.height, scale, addressRegion);
                                try { return await recognize(region, mode, addressRegion ? 'address' : numbersOnly); }
                                finally { region.width = region.height = 0; }
                            }
                            const confirmedNumbers = new Set();
                            for (const group of rows) {
                                const row = group.words;
                                const numericWords = row.filter(word => /^[0-9OoIl|]{4,16}$/.test(word[11]));
                                const length = numericWords.reduce((total, word) => total + word[11].length, 0);
                                if (length === 12 && !row.some(word => /VID/i.test(word[11]))) {
                                    const firstRead = await readRegion(numericWords, 7, true);
                                    const secondRead = await readRegion(numericWords, 6, true);
                                    focusedBackText += '\n' + firstRead + '\n' + secondRead;
                                    const firstNumber = DriverDocumentParser.parse(firstRead, 'aadhaar').adher_no;
                                    const secondNumber = DriverDocumentParser.parse(secondRead, 'aadhaar').adher_no;
                                    if (firstNumber && firstNumber === secondNumber) confirmedNumbers.add(firstNumber);
                                }
                            }
                            if (confirmedNumbers.size === 1) confirmedAadhaarNumber = [...confirmedNumbers][0];
                            const addressLabel = layoutWords.find(word => /^Address[:.]?$/i.test(word[11]));
                            if (!addressLabel) {
                                const state = DriverDocumentParser.parse(layoutWords.map(word => word[11]).join('\n'), 'aadhaar').state;
                                const stateWord = words.find(word => state && word[11].toLowerCase() === state.toLowerCase());
                                if (stateWord) {
                                    addressRegionAttempted = true;
                                    const left = Math.max(0, Number(stateWord[6]) - 30);
                                    const height = Number(stateWord[9]);
                                    const top = Math.max(0, Number(stateWord[7]) - height * 3);
                                    const right = left < canvas.width * 0.3 ? canvas.width * 0.58 : canvas.width;
                                    const region = cropCanvas(canvas, left / canvas.width, top / canvas.height,
                                        (right - left) / canvas.width, height * 5.5 / canvas.height, 1.5, true);
                                    try {
                                        const addressRead = await recognize(region, 6, 'address');
                                        considerAddress(addressRead);
                                    } finally { region.width = region.height = 0; }
                                }
                            }
                            if (addressLabel) {
                                addressRegionAttempted = true;
                                const left = Number(addressLabel[6]);
                                const top = Number(addressLabel[7]);
                                const rightLimit = left < canvas.width * 0.3 ? canvas.width * 0.58 : canvas.width;
                                const belowLabel = layoutWords.filter(word => Number(word[7]) >= top
                                    && Number(word[6]) >= left - 25
                                    && Number(word[6]) + Number(word[8]) <= rightLimit);
                                const pin = belowLabel.find(word => /\b[1-9]\d{5}\b/.test(word[11]));
                                {
                                    const bottom = pin ? Number(pin[7]) + Number(pin[9])
                                        : top + Number(addressLabel[9]) * 5.5;
                                    const addressWords = belowLabel.filter(word => Number(word[7]) < bottom);
                                    // Clear scans retain their original letter strokes;
                                    // thresholding is only a fallback for grey backgrounds.
                                    for (const preprocessing of ['raw', true]) {
                                        const addressRead = await readRegion(addressWords, 6, false, preprocessing);
                                        considerAddress(addressRead);
                                        if (focusedAddress && addressConfidence >= 70) {
                                            focusedBackText += '\n' + addressRead;
                                            break;
                                        }
                                    }
                                }
                            }
                            const dobWord = words.find(row => /DOB|\d{2}\/\d{2}\/\d{4}/i.test(row[11]));
                            if (dobWord) {
                                const dobY = Number(dobWord[7]);
                                const height = Number(dobWord[9]);
                                const nameWords = words.filter(row => {
                                    const y = Number(row[7]);
                                    return y < dobY && y > dobY - height * 3
                                        && /^[A-Za-z][A-Za-z.'-]*$/.test(row[11]);
                                });
                                if (nameWords.length >= 2) {
                                    const left = Math.min(...nameWords.map(row => Number(row[6])));
                                    const top = Math.min(...nameWords.map(row => Number(row[7])));
                                    const right = Math.max(...nameWords.map(row => Number(row[6]) + Number(row[8])));
                                    const bottom = Math.max(...nameWords.map(row => Number(row[7]) + Number(row[9])));
                                    const margin = Math.max(8, height * 0.4);
                                    const nameCanvas = cropCanvas(canvas, Math.max(0, left - margin) / canvas.width,
                                        Math.max(0, top - margin) / canvas.height,
                                        (right - left + margin * 2) / canvas.width,
                                        (bottom - top + margin * 2) / canvas.height, 2);
                                    try {
                                        focusedNameText = await recognize(nameCanvas, 7);
                                    } finally { nameCanvas.width = nameCanvas.height = 0; }
                                }
                            }
                        }
                        if (type !== 'license' && type !== 'vehicle-rc' && type !== 'vehicle-insurance' && type !== 'aadhaar') {
                            return fullText;
                        }

                        const cropTexts = [];
                        const crops = type === 'license'
                            ? [
                                cropCanvas(canvas, 0.00, 0.00, 1.00, 1.00, 1.35),
                                cropCanvas(canvas, 0.00, 0.00, 1.00, 0.65, 1.7),
                                cropCanvas(canvas, 0.15, 0.08, 0.80, 0.84, 1.9)
                            ]
                            : type === 'vehicle-rc'
                            ? [
                                cropCanvas(canvas, 0.24, 0.18, 0.34, 0.20, 2.8),
                                cropCanvas(canvas, 0.18, 0.14, 0.50, 0.26, 2.4),
                                cropCanvas(canvas, 0.00, 0.00, 1.00, 0.45, 1.7)
                            ]
                            : type === 'vehicle-insurance' ? [
                                cropCanvas(canvas, 0.00, 0.00, 1.00, 0.45, 1.8),
                                cropCanvas(canvas, 0.00, 0.20, 1.00, 0.45, 1.8),
                                cropCanvas(canvas, 0.45, 0.00, 0.55, 0.70, 2.0)
                            ] : [
                                // Aadhaar fronts vary: some place the English
                                // name closer to the photo than the address
                                // block. Read a wider upper band before the
                                // right-side crop so full names are not clipped.
                                cropCanvas(canvas, 0.24, 0.14, 0.74, 0.34, 3.0),
                                // Read the large number above the VID/footer,
                                // and back layouts with address left of QR.
                                cropCanvas(canvas, 0.20, 0.70, 0.60, 0.13, 2.4),
                                cropCanvas(canvas, 0.00, 0.28, 0.58, 0.25, 2.4),
                                // UIDAI cards place the English address block
                                // on the right. Read it at a higher scale so
                                // Hindi text from the left does not corrupt
                                // Address 1/Address 2.
                                cropCanvas(canvas, 0.42, 0.20, 0.56, 0.48, 3.0),
                                cropCanvas(canvas, 0.00, 0.25, 1.00, 0.75, 2.2),
                                cropCanvas(canvas, 0.28, 0.15, 0.72, 0.85, 2.4),
                                cropCanvas(canvas, 0.00, 0.00, 1.00, 1.00, 1.6)
                            ];

                        for (const crop of crops) {
                            try {
                                const cropText = await recognize(crop, type === 'aadhaar' ? 6 : (type === 'license' ? 11 : 3));
                                cropTexts.push(cropText);
                                if (type === 'aadhaar' && /\bAddress\s*:/i.test(cropText)) considerAddress(cropText);
                            } finally {
                                crop.width = crop.height = 0;
                            }
                        }

                        let combinedText = cropTexts.join('\n') + '\n' + licenseSparseText + '\n' + aadhaarNumberText + '\n' + fullText;
                        if (cleanLicenseNumber) {
                            combinedText = combinedText.replace(/\b[A-Z]{2}[A-Z0-9]{8,16}\b/gi, candidate => {
                                return candidate.toUpperCase() === cleanLicenseNumber ? candidate : '';
                            });
                        }
                        return focusedNameText + '\n' + focusedBackText + '\n' + licenseCleanText + '\n' + combinedText;
                    }
                    finally { canvas.width = canvas.height = 0; }
                }
                try {
                    const text = await Promise.race([read(), cancelled, new Promise((_, reject) => {
                        timeout = setTimeout(() => reject(new Error('Reading took too long. Try a smaller image or enter the details manually.')), 120000);
                    })]);
                    ensureCurrent();
                    const parsed = DriverDocumentParser.parse(text, type);
                    if (type === 'aadhaar' && focusedAddress) {
                        ['current_address', 'home_address', 'address_1', 'address_2', 'state', 'city', 'pincode'].forEach(key => {
                            parsed[key] = focusedAddress[key];
                        });
                        parsed.address_requires_manual_review = false;
                    } else if (type === 'aadhaar' && addressRegionAttempted) {
                        ['current_address', 'home_address', 'address_1', 'address_2'].forEach(key => { parsed[key] = ''; });
                        parsed.address_requires_manual_review = true;
                    }
                    if (type === 'aadhaar' && confirmedAadhaarNumber) {
                        const numberFields = ['adher_no', 'child_aadhaar_number', 'father_aadhaar_number', 'mother_aadhaar_number'];
                        numberFields.forEach(key => { parsed[key] = confirmedAadhaarNumber; });
                        parsed.ambiguous = parsed.ambiguous.filter(key => !numberFields.includes(key));
                    }
                    showValues(parsed, snapshot, capturedEdits, file, token);
                } catch (error) {
                    if (current()) message(error.name === 'PasswordException' ? 'Unlock the PDF before uploading, or enter the details manually.' : (error.message || 'Could not read the file. Please enter the details manually.'));
                } finally {
                    clearTimeout(timeout);
                    const wasCurrent = current();
                    job.cancelled = true;
                    if (job.worker) await job.worker.terminate().catch(() => {});
                    if (job.pdfTask) await job.pdfTask.destroy().catch(() => {});
                    if (active === job) active = null;
                    if (wasCurrent) cancel.hidden = true;
                    if (wasCurrent && sourceInput === input && pendingFrontFile === file) pendingFrontFile = null;
                    // Selecting the back must not lose a front scan still needed for the name.
                    if (wasCurrent && type === 'aadhaar' && sourceInput !== input
                        && pendingFrontFile && input.files[0] === pendingFrontFile) {
                        activeInput = input;
                        start();
                    }
                }
            });
        }
        scanInputs.forEach(scanInput => {
            scanInput.addEventListener('change', function () {
                if (type === 'aadhaar' && scanInput === input) {
                    pendingFrontFile = input.files[0] || null;
                    if (pendingFrontFile) {
                        for (const key of ['father_name', 'mother_name', 'child_name', 'driver_name']) {
                            const field = fields[key];
                            if (!field) continue;
                            field.value = '';
                            field.dispatchEvent(new Event('input', {bubbles: true}));
                            field.dispatchEvent(new Event('change', {bubbles: true}));
                            delete autoFilled[key];
                            delete autoFilledAtEdit[key];
                            delete autoFilledByInput[key];
                        }
                    }
                }
                activeInput = scanInput;
                start();
            });
        });
        retry.addEventListener('click', start);
        cancel.addEventListener('click', () => { stop(false); message('Reading cancelled. You can enter the details manually.'); });
        const remove = document.getElementById(type === 'license' ? 'removeImageBtn1' : 'removeImageBtn2');
        if (remove) remove.addEventListener('click', () => stop(true));
        form.addEventListener('reset', () => stop(true));
        window.addEventListener('pagehide', () => stop(true));
    }
    window.validateDriverDocumentPairs = function (form) {
        const root = form || document;
        const selected = input => Boolean(input && input.files && input.files.length);
        const normalizeDocument = value => String(value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        const normalizeAadhaar = value => String(value || '').replace(/\D/g, '');

        for (const panel of root.querySelectorAll('.driver-document-ocr')) {
            const type = panel.dataset.documentType;
            if (type !== 'license' && type !== 'aadhaar' && type !== 'vehicle-rc') continue;
            const panelForm = panel.closest('form') || root;
            const findFile = id => Array.from(panelForm.querySelectorAll('input[type="file"]')).find(element => element.id === id);
            const front = findFile(panel.dataset.inputId);
            const backId = String(panel.dataset.extraInputIds || '').split(',').map(value => value.trim()).find(Boolean);
            const back = backId ? findFile(backId) : null;
            const frontSelected = selected(front);
            const backSelected = selected(back);
            if (!frontSelected && !backSelected) continue;

            const isAadhaar = type === 'aadhaar';
            const normalize = isAadhaar ? normalizeAadhaar : normalizeDocument;
            const datasetKey = isAadhaar ? 'scannedAadhaarNumber' : 'scannedDocumentNumber';
            const initial = normalize(isAadhaar ? panel.dataset.initialAadhaarNumber : panel.dataset.initialDocumentNumber);
            const frontNumber = normalize(front && front.dataset[datasetKey]);
            const backNumber = normalize(back && back.dataset[datasetKey]);
            const fieldMap = String(panel.dataset.fieldMap || '');
            const owner = fieldMap.includes('father_name') ? 'Father'
                : fieldMap.includes('mother_name') ? 'Mother'
                : fieldMap.includes('child_name') ? 'Child' : 'Driver';
            const label = type === 'license' ? 'Driving license' : (type === 'vehicle-rc' ? 'RC book' : owner + ' Aadhaar');
            const frontUploadButton = front && root.querySelector(`button[onclick*="${front.id}"]`);
            const backUploadButton = back && root.querySelector(`button[onclick*="${back.id}"]`);
            const displayNumber = value => {
                if (!value) return 'not detected';
                if (isAadhaar) return `XXXX XXXX ${value.slice(-4)}`;
                return value;
            };
            const invalid = (message, side) => {
                const target = side === 'front'
                    ? (frontUploadButton || front)
                    : (backUploadButton || back);
                const selector = target && target.id ? `#${target.id}` : '';
                return {valid: false, selector, target, message};
            };

            if (frontSelected && !frontNumber) {
                return invalid(
                    `${label} front side is invalid: document number could not be read. Upload a clear front image of the correct ${label}.`,
                    'front'
                );
            }
            if (backSelected && !backNumber) {
                return invalid(
                    `${label} back side is invalid: document number could not be read. Upload a clear back image showing the same document number as the front side.`,
                    'back'
                );
            }
            if (isAadhaar && frontSelected && (front.dataset.aadhaarSide !== 'front' || front.dataset.aadhaarVerified !== 'true')) {
                return invalid(`Please upload a verified ${label} front side image in the ${owner} front side field.`, 'front');
            }
            if (isAadhaar && backSelected && (back.dataset.aadhaarSide !== 'back' || back.dataset.aadhaarVerified !== 'true')) {
                return invalid(`Please upload a verified ${label} back side image in the ${owner} back side field.`, 'back');
            }
            if (frontSelected && backSelected && frontNumber !== backNumber) {
                return invalid(
                    `${label} front and back do not match. Front number: ${displayNumber(frontNumber)}. Back number: ${displayNumber(backNumber)}. Upload both sides of the same document.`,
                    'back'
                );
            }
            const replacementNumber = frontSelected ? frontNumber : backNumber;
            if (!(frontSelected && backSelected) && initial && replacementNumber !== initial) {
                const replacementSide = frontSelected ? 'front' : 'back';
                return invalid(
                    `${label} ${replacementSide} side does not match the saved document. Saved number: ${displayNumber(initial)}. Uploaded ${replacementSide} number: ${displayNumber(replacementNumber)}. Upload the ${replacementSide} side of the same document.`,
                    replacementSide
                );
            }
        }
        return {valid: true};
    };
    function boot() {
        document.querySelectorAll('.driver-document-ocr').forEach(init);
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(records => {
                records.forEach(record => record.addedNodes.forEach(node => {
                    if (node.nodeType !== 1) return;
                    if (node.matches('.driver-document-ocr')) init(node);
                    node.querySelectorAll('.driver-document-ocr').forEach(init);
                }));
            });
            observer.observe(document.body, {childList: true, subtree: true});
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();

