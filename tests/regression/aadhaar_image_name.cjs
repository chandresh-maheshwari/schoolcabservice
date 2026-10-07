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
        await page.locator(back ? '#back' : '#card').setInputFiles(process.argv[2]);
        await page.waitForFunction(() => /details found|No reliable|could not|too long/.test(document.querySelector('[data-ocr-status]').textContent), null, {timeout: 150000});
        if (process.env.OCR_DEBUG) console.log(await page.evaluate(() => window.ocrTrace.map(({tsv, ...item}) => item)));
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
