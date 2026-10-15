const {chromium} = require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../../public');
const html = `<form><input name="driver_name"><input name="adher_no"><input name="current_address"><input type="file" id="front"><input type="file" id="back"><div class="driver-document-ocr" data-input-id="front" data-extra-input-ids="back" data-document-type="aadhaar" data-vendor-base="/vendor/license-ocr/" data-field-map='{"driver_name":"Name","adher_no":"Number","current_address":"Address"}'><div data-ocr-status></div><div data-ocr-results></div><button data-ocr-retry type="button"></button><button data-ocr-cancel type="button" hidden></button></div></form><script>window.errors=[];window.Swal={fire:()=>window.errors.push('invalid')};</script><script src="/js/driver-document-parser.js"></script><script src="/js/driver-document-ocr.js"></script>`;
const server = http.createServer((req,res) => {
    if(req.url === '/') {
        res.setHeader('Content-Type','text/html');
        let markup = html;
        if (process.env.DOCUMENT_TYPE === 'license') markup = html.replaceAll('adher_no','license_no').replaceAll('current_address','license_expiry_date').replace('data-document-type="aadhaar"','data-document-type="license"');
        if (process.env.DOCUMENT_TYPE === 'vehicle-rc') markup = html.replaceAll('driver_name','vehicle_number').replaceAll('adher_no','rc_number').replaceAll('current_address','rc_expiry_date').replace('data-document-type="aadhaar"','data-document-type="vehicle-rc"');
        if (process.env.DOCUMENT_TYPE === 'child') markup = html.replaceAll('driver_name','child_name').replaceAll('adher_no','child_aadhaar_number').replaceAll('current_address','home_address').replace('"home_address":"Address"','"home_address":"Address","gender":"Gender","date_of_birth":"DOB"').replace('</form>','<input name="gender"><input name="date_of_birth"></form>');
        if (process.env.DOCUMENT_TYPE === 'father') markup = html.replaceAll('driver_name','father_name').replaceAll('adher_no','father_aadhaar_number');
        return res.end(markup);
    }
    const file = path.resolve(root, '.' + req.url.split('?')[0]);
    if(!file.startsWith(root + path.sep) || !fs.existsSync(file)) {res.statusCode=404;return res.end();}
    res.setHeader('Content-Type', file.endsWith('.js') || file.endsWith('.mjs') ? 'text/javascript' : 'application/octet-stream');
    fs.createReadStream(file).pipe(res);
});
(async()=>{
    let browser;
    try {
        await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
        browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
        const page=await browser.newPage();
        await page.goto(`http://127.0.0.1:${server.address().port}`);
        await page.evaluate(name => { window.expectedName = name; }, process.env.EXPECTED_DRIVER_NAME || '');
        await page.evaluate(()=>{
            const findRegions = DriverDocumentParser.licenseValidityRegions;
            DriverDocumentParser.licenseValidityRegions = tsv => {
                const regions = findRegions(tsv);
                window.regionDiagnostics = {count:regions.length,labels:String(tsv).split('\n').map(row=>row.split('\t')[11] || '').filter(word=>/^(?:va|ex|nt)/i.test(word))};
                return regions;
            };
            const original=DriverDocumentParser.validateDocument;
            DriverDocumentParser.validateDocument=(text,type,side,parsed)=>{
                const result=original(text,type,side,parsed);
                window.issuerFragments = text.split('\n').filter(line=>/gov|india|indla|aad|aadh|uid|ident|gover|of /i.test(line)).map(line=>line.replace(/[0-9]/g,'#'));
                if (window.expectedName) window.nameSuffixes = text.split('\n').map(line=>line.replace(/\s+/g,' ').trim()).filter(line=>line.toLowerCase().startsWith(window.expectedName.toLowerCase())).map(line=>line.slice(window.expectedName.length));
                if (type === 'license') window.licenseEvidence = text.split('\n').filter(line=>/valid|[0-9]{2}[-/][0-9]{2}[-/]|LMV|MCWG|signature|authority|holder|licen|name|form|rule/i.test(line)).map(line=>line.replace(/[0-9]/g,'#'));
                window.lastValidation={valid:result.valid,side,detectedSide:parsed.aadhaar_side,numberFound:Boolean(parsed.adher_no || parsed.license_no),expiry:parsed.license_expiry_date,government:/government\s+of\s+india/i.test(text),aadhaar:/aadhaar|uidai/i.test(text),dob:/DOB/i.test(text),numericLines:text.split('\n').filter(line=>/\d{4}[ -]?\d{4}|valid|expiry|\bNT\b/i.test(line)).map(line=>line.replace(/[0-9]/g,'#'))};
                return result;
            };
        });
        for(const [index,file] of process.argv.slice(2).entries()) {
            const id=index===1?'back':'front';
            await page.locator('#'+id).setInputFiles(file);
            await page.waitForFunction(id=>['verified','invalid'].includes(document.getElementById(id).dataset.documentValidation),id,{timeout:150000});
            console.log(JSON.stringify(await page.evaluate(id=>({step:id,validation:window.lastValidation,issuer:window.issuerFragments,nameSuffixes:window.nameSuffixes,evidence:window.licenseEvidence,regions:window.regionDiagnostics,state:document.getElementById(id).dataset.documentValidation,popups:window.errors.length}),id)));
        }
        const replacingPair = !['license','vehicle-rc'].includes(process.env.DOCUMENT_TYPE) && process.argv.length > 4;
        assert.equal(await page.locator('#front').getAttribute('data-document-validation'),'verified');
        assert.equal(await page.evaluate(()=>window.errors.length),0);
        if (process.env.DOCUMENT_TYPE === 'vehicle-rc') {
            assert.equal(await page.locator('#back').getAttribute('data-document-validation'), 'verified');
            assert.equal(await page.evaluate(()=>validateDriverDocumentPairs(document.querySelector('form')).valid), process.env.EXPECT_RC_PAIR_MATCH === '1', 'RC pair must match only when a common vehicle identifier is read');
        }
        if (process.env.DOCUMENT_TYPE === 'child') {
            assert.ok(await page.locator('[name=child_name]').inputValue(), 'Child name must autofill');
            assert.ok(await page.locator('[name=gender]').inputValue(), 'Gender must autofill');
            assert.ok(await page.locator('[name=date_of_birth]').inputValue(), 'DOB must autofill');
        }
        if (process.env.DOCUMENT_TYPE === 'father') {
            assert.equal(await page.locator('#back').getAttribute('data-document-validation'), 'verified');
            assert.equal(await page.evaluate(()=>validateDriverDocumentPairs(document.querySelector('form')).valid), true);
            assert.ok(await page.locator('[name=current_address]').inputValue(), 'Father back address must autofill');
        }
        if (process.env.EXPECTED_DRIVER_NAME) {
            assert.equal(await page.locator('[name=driver_name]').inputValue(), process.env.EXPECTED_DRIVER_NAME, 'Driver name must match the printed Aadhaar name without trailing OCR noise');
        }
        if (replacingPair) assert.equal(await page.locator('#back').evaluate(input => input.files.length), 0);
        if (process.env.EXPECTED_REPLACEMENT_NUMBER) {
            const matches = await page.locator('#front').evaluate((input, expected) => input.dataset.scannedAadhaarNumber === expected, process.env.EXPECTED_REPLACEMENT_NUMBER);
            assert.equal(matches, true, 'Replacement number must equal the number printed on the supplied front');
        }
        if (process.env.DOCUMENT_TYPE === 'license') {
            if (process.argv.length > 3) assert.equal(await page.locator('#back').getAttribute('data-document-validation'), 'verified');
            assert.equal(await page.locator('[name=license_expiry_date]').inputValue(), '21/03/2043');
            assert.equal(await page.evaluate(()=>validateDriverDocumentPairs(document.querySelector('form')).valid),true);
        } else if (process.argv.length > 4) assert.equal(await page.evaluate(()=>validateDriverDocumentPairs(document.querySelector('form')).valid),false);
    } finally {if(browser) await browser.close(); await new Promise(resolve=>server.close(resolve));}
})().catch(error=>{console.error(error);process.exitCode=1;});
