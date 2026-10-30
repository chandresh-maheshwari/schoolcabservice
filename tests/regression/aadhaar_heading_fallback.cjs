// Synthetic OCR: verify native-size first read and recover a missed issuer.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const http = require('node:http');
const path = require('node:path');
const {chromium} = require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const root = path.resolve(__dirname, '../../public');
const html = `<form><input name="driver_name"><input name="adher_no"><input type="file" id="front"><div class="driver-document-ocr" data-input-id="front" data-document-type="aadhaar" data-vendor-base="/vendor/license-ocr/" data-field-map='{"driver_name":"Name","adher_no":"Number"}'><div data-ocr-status></div><div data-ocr-results></div><button type="button" data-ocr-retry></button><button type="button" data-ocr-cancel></button></div></form><script>window.reads=[];window.popups=[];window.Swal={fire:o=>window.popups.push(o.text)};</script><script src="/js/driver-document-parser.js"></script><script src="/js/driver-document-ocr.js"></script>`;
const server = http.createServer((req, res) => {
    res.setHeader('Content-Type', 'text/javascript');
    if (req.url === '/') { res.setHeader('Content-Type', 'text/html'); return res.end(html); }
    if (req.url.endsWith('/tesseract.min.js')) return res.end(`window.Tesseract={createWorker:async()=>({setParameters:async p=>{window.parameters=p},terminate:async()=>{},recognize:async c=>{
        window.reads.push([c.width,c.height]);
        const heading=c.width===468&&c.height===27&&window.parameters.tessedit_pageseg_mode==='7'&&window.parameters.tessedit_char_whitelist==='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz ';
        return {data:{text:heading?(window.missingHeading?'':'GOVERNMENT OF INDIA'):'TEST PERSON\\nDOB: 01/01/1990\\nFEMALE\\n2345 6789 0123',confidence:95,tsv:''}};
    }})};`);
    const file = path.resolve(root, '.' + req.url);
    if (!file.startsWith(root + path.sep) || !fs.existsSync(file)) { res.statusCode = 404; return res.end(); }
    res.end(fs.readFileSync(file));
});
(async () => {
    let browser;
    try {
        await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
        browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
        const page = await browser.newPage();
        for (const missingHeading of [false, true]) {
            await page.goto(`http://127.0.0.1:${server.address().port}`);
            await page.evaluate(value => { window.missingHeading = value; }, missingHeading);
            const buffer = Buffer.from(await page.evaluate(() => {
                const canvas = document.createElement('canvas'); canvas.width = 200; canvas.height = 100;
                return canvas.toDataURL('image/png').split(',')[1];
            }), 'base64');
            await page.locator('#front').setInputFiles({name:'synthetic.png',mimeType:'image/png',buffer});
            await page.waitForFunction(() => ['verified','invalid'].includes(document.querySelector('#front').dataset.documentValidation));
            assert.deepEqual(await page.evaluate(() => window.reads[0]), [200,100], 'First read must keep original dimensions');
            assert.ok(await page.evaluate(() => window.reads.some(([w,h]) => w === 468 && h === 27)), 'Missed issuer must trigger single-line English fallback');
            assert.equal(await page.locator('#front').getAttribute('data-document-validation'), missingHeading ? 'invalid' : 'verified');
            const uploaded = await page.locator('#front').evaluate(async input => Array.from(new Uint8Array(await input.files[0].arrayBuffer())));
            assert.deepEqual(Buffer.from(uploaded), buffer, 'Uploaded original bytes must remain intact');
            if (missingHeading) {
                assert.match(await page.evaluate(() => window.popups[0]), /heading could not be read/);
                assert.equal(await page.locator('[data-ocr-retry]').isVisible(), true);
            }
        }
        console.log('Native-size OCR, issuer fallback, original upload and invalid-document rejection passed');
    } finally { if (browser) await browser.close(); await new Promise(resolve => server.close(resolve)); }
})().catch(error => { console.error(error); process.exitCode = 1; });
