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
    function cropCanvas(source, xRatio, yRatio, widthRatio, heightRatio, scaleMultiplier) {
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
        ctx.filter = 'grayscale(100%) contrast(190%) brightness(115%)';
        ctx.drawImage(source, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
        ctx.filter = 'none';
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
        const input = document.getElementById(panel.dataset.inputId);
        if (!input || panel.dataset.bound) return;
        panel.dataset.bound = 'true';
        const extraInputs = (panel.dataset.extraInputIds || '').split(',').map(id => document.getElementById(id.trim())).filter(Boolean);
        const scanInputs = [input, ...extraInputs];
        let activeInput = input;
        const form = input.form;
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
        let generation = 0, active = null;
        const autoFilled = {};
        const edits = {};
        Object.entries(fields).forEach(([key, field]) => {
            edits[key] = 0;
            if (field) field.addEventListener('input', () => { edits[key]++; });
        });
        const message = text => { status.textContent = text; };
        function stop(clear) {
            generation++;
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
            const isExtraInput = activeInput && activeInput !== input;
            const isAadhaarBackInput = type === 'aadhaar' && isExtraInput;
            const backSideSkipFields = new Set(['driver_name', 'father_name', 'mother_name', 'child_name']);
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
                    message('Aadhaar number could not be read from this file. The file is still selected, but upload a clearer, unmasked Aadhaar ' + expectedSide + ' side image before submitting.');
                    return;
                }
                if (parsed.aadhaar_side !== expectedSide) {
                    delete activeInput.dataset.scannedAadhaarNumber;
                    delete activeInput.dataset.aadhaarSide;
                    delete activeInput.dataset.aadhaarVerified;
                    message(expectedSide === 'front'
                        ? 'This appears to be the Aadhaar back side. The file is still selected; replace it with the Aadhaar front side.'
                        : 'This appears to be the Aadhaar front side. The file is still selected; replace it with the Aadhaar back side.');
                    return;
                }
                activeInput.dataset.scannedAadhaarNumber = scannedAadhaar;
                activeInput.dataset.aadhaarSide = parsed.aadhaar_side;
                activeInput.dataset.aadhaarVerified = 'true';
                activeInput.dispatchEvent(new CustomEvent('aadhaar-number-scanned', { bubbles: true, detail: { aadhaarNumber: scannedAadhaar, side: parsed.aadhaar_side } }));
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
            let detected = 0;
            for (const [key, field] of Object.entries(fields)) {
                if (isAadhaarBackInput && backSideSkipFields.has(key)) {
                    continue;
                }
                const value = parsed[key];
                if (!field || !value) continue;
                detected++;
                const row = document.createElement('div');
                row.className = 'small mb-2';
                const summary = document.createElement('span');
                summary.textContent = `${labels[key]}: ${value} `;
                row.appendChild(summary);
                const apply = () => {
                    if (token !== generation || activeInput.files[0] !== file) return;
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
                            if (!Array.from(field.options).some(option => option.value === value)) {
                                field.add(new Option(value, value, true, true));
                            }
                            field.value = value;
                            field.dispatchEvent(new Event('input', {bubbles: true}));
                            field.dispatchEvent(new Event('change', {bubbles: true}));
                        }, 800);
                    }
                    field.classList.add('border-info');
                    autoFilled[key] = value;
                    summary.textContent = `${labels[key]} filled. Please check the field. `;
                };
                const currentRadio = field.type === 'radio' ? form.querySelector(`[name="${key}"]:checked`) : null;
                if (field.type === 'radio' && !currentRadio) apply();
                else if (!snapshot[key].trim() && field.value === snapshot[key] && edits[key] === capturedEdits[key]) apply();
                else if (autoFilled[key] && field.value === autoFilled[key]) apply();
                else if (field.value !== value) {
                    const button = document.createElement('button');
                    button.type = 'button'; button.className = 'btn btn-sm btn-outline-primary ml-2';
                    button.textContent = 'Use scanned value';
                    button.addEventListener('click', () => { apply(); button.remove(); });
                    row.appendChild(button);
                }
                results.appendChild(row);
            }
            const expected = Object.values(fields).filter(Boolean).length;
            message(detected ? `${detected} of ${expected} details found. Review the fields; existing values were kept.`
                : 'No reliable details found. Try a clearer image, the other side, or enter the details manually.');
            if (type === 'vehicle-rc' && (!parsed.vehicle_number || !parsed.rc_number)) {
                const missingMessage = ' RC number / vehicle number was not found. Please upload the RC book front side; driving licence or other documents will not fill RC details.';
                message(status.textContent + missingMessage);
            }
            if (parsed.ambiguous.length) message(status.textContent + ' Multiple possible values found; check those fields manually.');
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
            if (type === 'vehicle-rc' || type === 'vehicle-insurance') delete sourceInput.dataset.scannedVehicleNumber;
            if (type === 'license' || type === 'vehicle-rc') delete sourceInput.dataset.scannedDocumentNumber;
            retry.hidden = false;
            if (file.size > 20 * 1024 * 1024) { message('Use a file smaller than 20 MB for auto-fill.'); return; }
            if (!/\.(jpe?g|png|webp|bmp|gif|pdf)$/i.test(file.name)) {
                message('Auto-fill supports JPG, PNG, WEBP, BMP, GIF and PDF. Convert other file types first.'); return;
            }
            const token = generation;
            const snapshot = Object.fromEntries(Object.entries(fields).map(([key, field]) => [key, field ? field.value : '']));
            const capturedEdits = {...edits};
            cancel.hidden = false;
            message('Preparing to read file…');
            queue = queue.catch(() => {}).then(async () => {
                if (token !== generation || activeInput.files[0] !== file) return;
                const job = {worker: null, pdfTask: null, cancelled: false};
                const cancelled = new Promise((_, reject) => { job.abort = () => reject(new Error('Reading cancelled.')); });
                active = job;
                let timeout;
                const current = () => !job.cancelled && token === generation && activeInput.files[0] === file;
                const ensureCurrent = () => { if (!current()) throw new Error('Reading cancelled.'); };
                async function read() {
                    let worker;
                    async function recognize(canvas, pageSegMode) {
                        ensureCurrent();
                        if (!worker) {
                            await loadTools(base); ensureCurrent();
                            const workerLanguages = await availableLanguages(base, type);
                            worker = await Tesseract.createWorker(workerLanguages, 1, {
                                workerPath: base + 'tesseract/worker.min.js', corePath: base + 'core', langPath: base + 'lang',
                                logger: progress => { if (current()) message(progress.status === 'recognizing text' ? `Reading document… ${Math.round(progress.progress * 100)}%` : 'Loading reading tools…'); }
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
                            tessedit_pageseg_mode: String(pageSegMode || 3)
                        });
                        const {data} = await worker.recognize(canvas);
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
                            ensureCurrent(); message(`Reading PDF page ${i} of ${pdf.numPages}…`);
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
                        if (type === 'license') {
                            const cleanCanvas = licenseTextCanvas(canvas);
                            try {
                                licenseCleanText = await recognize(cleanCanvas, 11);
                            } finally {
                                cleanCanvas.width = cleanCanvas.height = 0;
                            }
                            if (DriverDocumentParser.parse(licenseCleanText, type).license_no) {
                                return licenseCleanText;
                            }
                        }
                        // Sparse-text mode is substantially better at finding
                        // Aadhaar's isolated 12-digit line when the upload is
                        // a phone screenshot with borders/empty margins.
                        const aadhaarNumberText = type === 'aadhaar' ? await recognize(canvas, 11) : '';
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
                                // UIDAI cards place the English address block
                                // on the right. Read it first and at a higher
                                // scale so Hindi text from the left does not
                                // corrupt Address 1/Address 2.
                                cropCanvas(canvas, 0.42, 0.20, 0.56, 0.48, 3.0),
                                cropCanvas(canvas, 0.00, 0.25, 1.00, 0.75, 2.2),
                                cropCanvas(canvas, 0.28, 0.15, 0.72, 0.85, 2.4),
                                cropCanvas(canvas, 0.00, 0.00, 1.00, 1.00, 1.6)
                            ];

                        for (const crop of crops) {
                            try {
                                cropTexts.push(await recognize(crop, type === 'aadhaar' ? 6 : (type === 'license' ? 11 : 3)));
                            } finally {
                                crop.width = crop.height = 0;
                            }
                        }

                        return cropTexts.join('\n') + '\n' + licenseCleanText + '\n' + licenseSparseText + '\n' + aadhaarNumberText + '\n' + fullText;
                    }
                    finally { canvas.width = canvas.height = 0; }
                }
                try {
                    const text = await Promise.race([read(), cancelled, new Promise((_, reject) => {
                        timeout = setTimeout(() => reject(new Error('Reading took too long. Try a smaller image or enter the details manually.')), 120000);
                    })]);
                    ensureCurrent();
                    showValues(DriverDocumentParser.parse(text, type), snapshot, capturedEdits, file, token);
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
                }
            });
        }
        scanInputs.forEach(scanInput => {
            scanInput.addEventListener('change', function () {
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
    function boot() { document.querySelectorAll('.driver-document-ocr').forEach(init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();

