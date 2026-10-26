const {chromium} = require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../..');
const pairs = [
    ['adher_card_iamge', 'adher_card_back_image', 'aadhaar'],
    ['license_image', 'license_back_image', 'license'],
    ['father_adhaar_card_image', 'father_adhaar_card_back_image', 'aadhaar'],
    ['mother_adhaar_card_image', 'mother_adhaar_card_back_image', 'aadhaar'],
    ['child_adhaar_card_image', 'child_adhaar_card_back_image', 'aadhaar'],
    ['modal_child_adhaar_card_image', 'modal_child_adhaar_card_back_image', 'aadhaar'],
    ['rc_image', 'rc_back_image', 'vehicle-rc'],
    ['insurance_image', '', 'vehicle-insurance'],
];
(async () => {
    const browser = await chromium.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true});
    try {
        const page = await browser.newPage();
        await page.setContent(pairs.map(([front, back, type], index) => `<form id="form${index}">
            <input id="${front}" type="file">${back ? `<input id="${back}" type="file">` : ''}
            <input name="kept_value" value="Keep manual value">
            <div class="driver-document-ocr" data-input-id="${front}" data-extra-input-ids="${back}" data-document-type="${type}" data-field-map='{}'>
                <div data-ocr-status></div><div data-ocr-results></div><button type="button" data-ocr-retry hidden>Read file again</button><button type="button" data-ocr-cancel hidden>Cancel</button>
            </div></form>`).join(''));
        const common = fs.readFileSync(path.join(root, 'public/js/common_js.js'), 'utf8');
        await page.addScriptTag({content: common.slice(common.indexOf('window.resetFilePreview ='), common.indexOf('window.pdfPreviewPlaceholder ='))});
        const layout = fs.readFileSync(path.join(root, 'resources/views/admin_layout/index.blade.php'), 'utf8');
        await page.addScriptTag({content: layout.slice(layout.indexOf('    window.clearDocumentFileUi ='), layout.indexOf('    window.showSelectedDocumentPreview ='))});
        await page.addScriptTag({path: path.join(root, 'public/js/driver-document-ocr.js')});
        for (const [front, back] of pairs) {
            for (const id of [front, back].filter(Boolean)) {
                for (const method of ['image', 'document', 'change']) {
                    await page.evaluate(({id, method}) => {
                        const input = document.getElementById(id), form = input.form;
                        const panel = form.querySelector('.driver-document-ocr');
                        panel.querySelector('[data-ocr-status]').textContent = 'Old OCR success/error';
                        panel.querySelector('[data-ocr-results]').textContent = 'Number filled';
                        panel.querySelector('[data-ocr-retry]').hidden = false;
                        panel.querySelector('[data-ocr-cancel]').hidden = false;
                        input.dataset.scannedAadhaarNumber = '123456789012';
                        input.dataset.documentValidation = 'verified';
                        if (method === 'image') window.clearImageSelection({imageInputSelector: '#' + id});
                        else if (method === 'document') window.clearDocumentFileUi(input);
                        else input.dispatchEvent(new Event('change', {bubbles: true}));
                    }, {id, method});
                    const result = await page.locator('#' + id).evaluate(input => {
                        const panel = input.form.querySelector('.driver-document-ocr');
                        return {status: panel.querySelector('[data-ocr-status]').textContent,
                            results: panel.querySelector('[data-ocr-results]').textContent,
                            retry: panel.querySelector('[data-ocr-retry]').hidden,
                            cancel: panel.querySelector('[data-ocr-cancel]').hidden,
                            scanned: input.dataset.scannedAadhaarNumber || '',
                            validation: input.dataset.documentValidation || '',
                            value: input.form.querySelector('[name=kept_value]').value};
                    });
                    assert.deepEqual(result, {status:'', results:'', retry:true, cancel:true, scanned:'', validation:'', value:'Keep manual value'}, id + '/' + method);
                }
            }
        }
        console.log('PASS: OCR messages/retry reset for all 15 document inputs through all 3 deletion paths; form values preserved');
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
