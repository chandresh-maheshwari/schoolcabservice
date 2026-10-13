const {chromium} = require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../../public');
const html = `<form><input name="child_name"><input name="child_aadhaar_number"><input name="home_address"><select name="state"><option value="">Select State</option></select><select name="city"><option value="">Select City</option></select><input type="file" id="card"><input type="file" id="back"><div class="driver-document-ocr" data-document-type="aadhaar" data-input-id="card" data-extra-input-ids="back" data-vendor-base="/vendor/license-ocr/" data-field-map='{"child_name":"Name","child_aadhaar_number":"Number","home_address":"Address","state":"State","city":"City"}'><div data-ocr-status></div><div data-ocr-results></div><button data-ocr-retry type="button"></button><button data-ocr-cancel type="button"></button></div></form><script src="/js/driver-document-parser.js"></script><script src="/js/driver-document-ocr.js"></script>`;
const server = http.createServer((req, res) => {
    if (req.url === '/') { res.setHeader('Content-Type', 'text/html'); return res.end(html); }
    const file = path.resolve(root, '.' + req.url.split('?')[0]);
    if (!file.startsWith(root + path.sep) || !fs.existsSync(file)) { res.statusCode = 404; return res.end(); }
    res.setHeader('Content-Type', path.extname(file) === '.js' ? 'text/javascript' : 'application/octet-stream');
    fs.createReadStream(file).pipe(res);
});
(async () => {
    let browser;
    try {
        await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
        browser = await chromium.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true});
        const page = await browser.newPage();
        const origin = `http://127.0.0.1:${server.address().port}`;
        await page.route('**/*', route => route.request().url().startsWith(origin) || route.request().url().startsWith('blob:') ? route.continue() : route.abort());
        if (process.env.OCR_DEBUG) {
            await page.route('**/js/driver-document-ocr.js', route => {
                const script = fs.readFileSync(path.join(root, 'js/driver-document-ocr.js'), 'utf8');
                return route.fulfill({contentType: 'text/javascript', body: script.replace('return data.confidence >= 35', '(window.ocrTrace ||= []).push({mode:pageSegMode,confidence:data.confidence,text:data.text,width:canvas.width,height:canvas.height,tsv:data.tsv}); return data.confidence >= 35')});
            });
        }
        await page.goto(origin);
        const back = process.argv[4] === 'back';
        const unreadable = process.argv[4] === 'unreadable';
        if (process.argv[4] === 'clear') {
            const buffer = Buffer.from(await page.evaluate(() => {
                const canvas = document.createElement('canvas'); canvas.width = 1400; canvas.height = 900;
                const context = canvas.getContext('2d'); context.fillStyle = 'white'; context.fillRect(0, 0, 1400, 900);
                context.fillStyle = 'black'; context.font = '48px Arial';
                ['Government of India', 'TEST PERSON', 'DOB: 01/01/2010', 'MALE', '2345 6789 0123'].forEach((line, index) => context.fillText(line, 350, 120 + index * 140));
                return canvas.toDataURL().split(',')[1];
            }), 'base64');
            await page.locator('#card').setInputFiles({name: 'clear-aadhaar.png', mimeType: 'image/png', buffer});
        } else if (unreadable) {
            const imageData = fs.readFileSync(process.argv[2]).toString('base64');
            const buffer = Buffer.from(await page.evaluate(async imageData => {
                const image = new Image(); image.src = 'data:image/jpeg;base64,' + imageData; await image.decode();
                const canvas = document.createElement('canvas');
                canvas.width = image.naturalWidth; canvas.height = Math.round(image.naturalHeight * 0.72);
                const context = canvas.getContext('2d'); context.drawImage(image, 0, 0);
                const small = document.createElement('canvas'); small.width = 45; small.height = 18;
                small.getContext('2d').drawImage(image, image.naturalWidth * 0.32, image.naturalHeight * 0.2,
                    image.naturalWidth * 0.5, image.naturalHeight * 0.23, 0, 0, 45, 18);
                context.imageSmoothingEnabled = false;
                context.drawImage(small, image.naturalWidth * 0.32, image.naturalHeight * 0.2,
                    image.naturalWidth * 0.5, image.naturalHeight * 0.23);
                return canvas.toDataURL('image/png').split(',')[1];
            }, imageData), 'base64');
            await page.locator('#card').setInputFiles({name: 'unreadable-aadhaar.png', mimeType: 'image/png', buffer});
        } else {
            await page.locator(back ? '#back' : '#card').setInputFiles(process.argv[2]);
        }
        await page.waitForFunction(() => /details found|No reliable|could not|too long|blurred or not clearly readable/.test(document.querySelector('[data-ocr-status]').textContent), null, {timeout: 150000});
        if (unreadable) {
            assert.match(await page.locator('[data-ocr-status]').textContent(), /blurred or not clearly readable/);
            for (const name of ['child_name', 'child_aadhaar_number', 'home_address']) {
                assert.equal(await page.locator(`[name="${name}"]`).inputValue(), '', `${name}: unreadable image must not fill any field`);
            }
            assert.equal(await page.locator('#card').evaluate(input => input.dataset.aadhaarVerified), undefined);
            console.log('Unreadable image rejected without filling fields');
            return;
        }
        if (process.argv[4] === 'strict' && !await page.locator('[name=child_name]').inputValue()) {
            assert.match(await page.locator('[data-ocr-status]').textContent(), /blurred or not clearly readable/);
            assert.equal(await page.locator('#card').evaluate(input => input.dataset.aadhaarVerified), undefined);
            assert.equal(await page.locator('[data-ocr-results]').textContent(), '');
            console.log('Uncertain attached image rejected without an incorrect name');
            return;
        }
        if (process.env.OCR_DEBUG) console.log(await page.evaluate(() => window.ocrTrace.map(({mode, confidence, text, width, height}) => {
            const parsed = DriverDocumentParser.parse(text, 'aadhaar');
            return {mode, confidence, width, height, numberSuffix: parsed.adher_no.slice(-4), ambiguous: parsed.ambiguous.includes('adher_no')};
        })));
        if (back) {
            assert.equal(await page.locator('[name=child_aadhaar_number]').inputValue(), process.argv[3]);
            const address = await page.locator('[name=home_address]').inputValue();
            if (process.argv[5]) assert.equal(address, process.argv[5]);
            else {
                assert.match(address, /Sohan Lal Ji/i);
                assert.match(address, /Pali.*Rajasthan.*306703/i);
            }
        } else {
            assert.equal(await page.locator('[name=child_name]').inputValue(), process.argv[3]);
            if (process.argv[5]) {
                const number = await page.locator('[name=child_aadhaar_number]').inputValue();
                assert.ok(number.replace(/\D/g, '') === process.argv[5].replace(/\D/g, ''), 'Front must read the printed Aadhaar number');
            }
        }
        const verification = await page.locator(back ? '#back' : '#card').evaluate(input => ({
            verified: input.dataset.aadhaarVerified,
            side: input.dataset.aadhaarSide,
            hasNumber: /^\d{12}$/.test(input.dataset.scannedAadhaarNumber || '')
        }));
        assert.equal(verification.verified, 'true', 'Selected image must complete Aadhaar verification');
        assert.equal(verification.side, back ? 'back' : 'front');
        assert.equal(verification.hasNumber, true, 'Selected image must retain its scanned number');
        if (!back && process.env.OCR_BACK_IMAGE) {
            await page.locator('#back').setInputFiles(process.env.OCR_BACK_IMAGE);
            await page.waitForFunction(() => document.querySelector('#back').dataset.aadhaarVerified === 'true'
                && document.querySelector('[data-ocr-cancel]').hidden, null, {timeout: 150000});
            const validation = await page.evaluate(() => window.validateDriverDocumentPairs(document.querySelector('form')));
            assert.equal(validation.valid, true, 'Both original images must pass pair validation');
        }
        if (process.argv[6]) {
            assert.equal(await page.locator('[name=city]').inputValue(), process.argv[6]);
            await page.waitForTimeout(1000);
            await page.evaluate(() => {
                document.querySelector('[name=city]').replaceChildren(new Option('Select City', ''), new Option('Other city', 'Other city'));
            });
            await page.waitForFunction(expected => document.querySelector('[name=city]').value === expected, process.argv[6]);
            await page.evaluate(() => {
                const city = document.querySelector('[name=city]');
                city.value = '';
                city.dispatchEvent(new Event('input', {bubbles: true}));
                city.replaceChildren(new Option('Select City', ''));
            });
            await page.waitForTimeout(100);
            assert.equal(await page.locator('[name=city]').inputValue(), '', 'Manual city edits must be preserved');
        }
        console.log('Attached image OCR passed');
    } finally {
        if (browser) await browser.close();
        await new Promise(resolve => server.close(resolve));
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
