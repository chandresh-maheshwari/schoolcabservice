const {chromium} = require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../..');
const source = fs.readFileSync(path.join(root, 'resources/views/routes/partials/form.blade.php'), 'utf8');
const functions = source.slice(source.indexOf('function bindRouteSchoolSelection('), source.indexOf('function cloneOption('));
(async () => {
    const browser = await chromium.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true});
    try {
        const page = await browser.newPage();
        await page.setContent('<select id="school_id"><option value="">School</option><option value="48">School A</option><option value="49">School B</option></select><select id="driver_id"></select><select id="bus_id"><option value="73">Old vehicle</option></select>');
        await page.addScriptTag({path: path.join(root, 'public/js/jquery-3.2.1.min.js')});
        await page.addScriptTag({content: `
            const initialDriverId = '', initialSchoolId = '';
            const cachedDriverOptions = ['48', '49'].map((school, index) => {
                const option = new Option('Driver ' + school, String(69 + index));
                option.dataset.schoolId = school;
                return option;
            });
            const destroyNiceSelect = () => {};
            const syncNiceSelect = () => {};
            const cloneOption = option => option.cloneNode(true);
            const renderVehicleOptionsFromList = () => { document.getElementById('bus_id').innerHTML = ''; };
            ${functions}
            bindRouteSchoolSelection(document.getElementById('school_id'));
            renderDriverOptionsBySchool('', '');
        `});
        assert.equal(await page.locator('#driver_id').isDisabled(), true);
        // Matches the custom dropdown's actual input/change event sequence.
        await page.evaluate(() => window.jQuery('#school_id').val('48').trigger('input').trigger('change'));
        assert.equal(await page.locator('#driver_id').isEnabled(), true);
        assert.deepEqual(await page.locator('#driver_id option').evaluateAll(options => options.map(option => option.value)), ['', '69']);
        assert.equal(await page.locator('#bus_id option').count(), 0);
        await page.selectOption('#school_id', '49');
        assert.deepEqual(await page.locator('#driver_id option').evaluateAll(options => options.map(option => option.value)), ['', '70']);
        await page.evaluate(() => window.jQuery('#school_id').val('').trigger('change'));
        assert.equal(await page.locator('#driver_id').isDisabled(), true);
        assert.equal(await page.locator('#driver_id option').count(), 1);
        console.log('PASS: custom jQuery and native school changes refresh drivers; clearing school locks dropdown');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
