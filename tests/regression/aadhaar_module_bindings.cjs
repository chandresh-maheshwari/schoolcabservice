const {chromium} = require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../../public');
const views = path.resolve(__dirname, '../../resources/views');
const configurations = [];
const realImage = process.argv[2];
for (const module of ['parent', 'child', 'driver']) {
    for (const mode of ['create', 'edit']) {
        const source = fs.readFileSync(path.join(views, module, `${mode}.blade.php`), 'utf8');
        const maps = [...source.matchAll(/'fieldMap'\s*=>\s*\[([^\]]+)\]/g)].map(match =>
            Object.fromEntries([...match[1].matchAll(/'([^']+)'\s*=>\s*'([^']+)'/g)].map(pair => [pair[1], pair[2]])));
        if (module === 'driver') maps.push({driver_name: 'Name', adher_no: 'Number', current_address: 'Address'});
        maps.forEach((map, index) => configurations.push({id: `${module}-${mode}-${index}`, map}));
    }
}
const modalSource = fs.readFileSync(path.join(views, 'child/partials/add_child_modal.blade.php'), 'utf8');
const modalMap = modalSource.match(/'fieldMap'\s*=>\s*\[([^\]]+)\]/);
configurations.push({id: 'child-modal', map: Object.fromEntries([...modalMap[1].matchAll(/'([^']+)'\s*=>\s*'([^']+)'/g)].map(pair => [pair[1], pair[2]]))});
const html = configurations.map(({id, map}) => `<form id="${id}">${Object.keys(map).map(key => ['city', 'state'].includes(key) ? `<select name="${key}"><option value="">Select</option><option selected value="Saved value">Saved value</option></select>` : `<input name="${key}" value="Saved value">`).join('')}<input name="untouched_address" value="Saved address"><input id="front" type="file"><input id="back" type="file"><div class="driver-document-ocr" data-input-id="front" data-extra-input-ids="back" data-document-type="aadhaar" data-vendor-base="/vendor/license-ocr/" data-field-map='${JSON.stringify(map)}'><div data-ocr-status></div><div data-ocr-results></div><button type="button" data-ocr-retry></button><button type="button" data-ocr-cancel></button></div></form>`).join('') + '<script src="/js/driver-document-parser.js"></script><script src="/js/driver-document-ocr.js"></script>';
const server = http.createServer((req, res) => {
    if (req.url === '/') { res.setHeader('Content-Type', 'text/html'); return res.end(html); }
    if (!realImage && req.url.endsWith('/tesseract.min.js')) {
        res.setHeader('Content-Type', 'text/javascript');
        return res.end('window.Tesseract={createWorker:async()=>({setParameters:async()=>{},recognize:async()=>({data:{text:window.testText,confidence:95,tsv:""}}),terminate:async()=>{}})};');
    }
    const file = path.resolve(root, '.' + req.url);
    if (!file.startsWith(root + path.sep) || !fs.existsSync(file)) { res.statusCode = 404; return res.end(); }
    res.setHeader('Content-Type', file.endsWith('.js') ? 'text/javascript' : 'application/octet-stream');
    fs.createReadStream(file).pipe(res);
});
(async () => {
    let browser;
    try {
        await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
        browser = await chromium.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true});
        const page = await browser.newPage();
        await page.goto(`http://127.0.0.1:${server.address().port}`);
        const buffer = Buffer.from(await page.evaluate(() => { const canvas = document.createElement('canvas'); canvas.width = 100; canvas.height = 100; return canvas.toDataURL().split(',')[1]; }), 'base64');
        for (const {id, map} of configurations) {
            const form = page.locator(`form[id="${id}"]`);
            if (realImage) {
                await form.locator('input[type=file]').last().setInputFiles(realImage);
                await page.waitForFunction(id => document.getElementById(id).querySelectorAll('input[type=file]')[1].dataset.aadhaarVerified === 'true', id, {timeout: 150000});
                if (map.address_1) {
                    const address = [await form.locator('[name=address_1]').inputValue(), await form.locator('[name=address_2]').inputValue()].join(', ');
                    assert.equal(address, process.argv[3], id);
                    assert.equal(await form.locator('[name=city]').inputValue(), process.argv[4], id);
                }
                for (const key of ['home_address', 'current_address']) {
                    if (map[key]) assert.equal(await form.locator(`[name="${key}"]`).inputValue(), process.argv[3], id);
                }
                assert.equal(await form.locator('[name=untouched_address]').inputValue(), 'Saved address');
                console.log(`${id}: real image address/city passed`);
                continue;
            }
            const gender = map.mother_name ? 'FEMALE' : 'MALE';
            await page.evaluate(text => { window.testText = text; }, `Government of India\nNEW PERSON NAME\nDOB: 01/01/1990\n${gender}\n2345 6789 0123`);
            await form.locator('input[type=file]').first().setInputFiles({name: 'front.png', mimeType: 'image/png', buffer});
            await page.waitForFunction(id => document.getElementById(id).querySelector('input[type=file]').dataset.aadhaarVerified === 'true', id);
            const nameKey = Object.keys(map).find(key => key.endsWith('_name'));
            assert.equal(await form.locator(`[name="${nameKey}"]`).inputValue(), 'NEW PERSON NAME', id);
            await page.evaluate(() => { window.testText = 'Address: House 12, Lake Road, Anand, Gujarat 388001\n2345 6789 0123'; });
            await form.locator('input[type=file]').last().setInputFiles({name: 'back.png', mimeType: 'image/png', buffer});
            await page.waitForFunction(id => document.getElementById(id).querySelectorAll('input[type=file]')[1].dataset.aadhaarVerified === 'true', id);
            const addressKey = Object.keys(map).find(key => ['address_1', 'home_address', 'current_address'].includes(key));
            if (addressKey) assert.match(await form.locator(`[name="${addressKey}"]`).inputValue(), /House 12/);
            if (map.city) assert.equal(await form.locator('[name=city]').inputValue(), 'Anand');
            assert.equal(await form.locator('[name=untouched_address]').inputValue(), 'Saved address');
            const validation = await form.evaluate(form => window.validateDriverDocumentPairs(form));
            assert.equal(validation.valid, true, `${id}: pair validation must use this form's inputs`);
            await page.evaluate(({id, gender}) => {
                window.Tesseract.createWorker = async () => ({
                    setParameters: async () => {}, terminate: async () => {},
                    recognize: async () => {
                        await new Promise(resolve => setTimeout(resolve, 30));
                        const readingBack = document.getElementById(id).querySelector('[data-ocr-status]').textContent.includes('replacement-back.png');
                        return {data: {confidence: 95, tsv: '', text: readingBack
                            ? 'Address: House 25, Lake Road, Anand, Gujarat 388001\n3456 7890 1234'
                            : `Government of India\nREPLACEMENT PERSON NAME\nDOB: 01/01/1990\n${gender}\n3456 7890 1234`}};
                    }
                });
            }, {id, gender});
            await form.locator('input[type=file]').first().setInputFiles({name: 'replacement-front.png', mimeType: 'image/png', buffer});
            await form.locator('input[type=file]').last().setInputFiles({name: 'replacement-back.png', mimeType: 'image/png', buffer});
            await page.waitForFunction(({id, nameKey}) => document.getElementById(id).querySelector(`[name="${nameKey}"]`).value === 'REPLACEMENT PERSON NAME', {id, nameKey});
            assert.equal((await form.evaluate(form => window.validateDriverDocumentPairs(form))).valid, true, `${id}: interrupted replacement front must finish`);
            await page.evaluate(() => {
                window.Tesseract.createWorker = async () => ({setParameters: async () => {},
                    recognize: async () => ({data: {text: window.testText, confidence: 95, tsv: ''}}), terminate: async () => {}});
            });
        }
        await page.evaluate(() => {
            const original = document.querySelector('form');
            const copy = original.cloneNode(true); copy.id = 'dynamic-panel';
            copy.querySelector('.driver-document-ocr').removeAttribute('data-bound');
            document.body.appendChild(copy);
        });
        await page.waitForFunction(() => document.querySelector('#dynamic-panel .driver-document-ocr').dataset.bound === 'true');
        console.log(`${configurations.length} module/form mappings and dynamic initialization passed`);
    } finally {
        if (browser) await browser.close();
        await new Promise(resolve => server.close(resolve));
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
