const {chromium} = require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const fs = require('node:fs');
const assert = require('node:assert/strict');
const source = fs.readFileSync(require.resolve('../../resources/views/admin_layout/index.blade.php'), 'utf8');
const shared = source.slice(source.indexOf('    window.clearDocumentFileUi ='), source.indexOf('    window.bindDifferentDocumentFiles ='));
const pairs = [
    ['rc_image','rc_back_image'], ['license_image','license_back_image'],
    ['adher_card_iamge','adher_card_back_image'], ['father_adhaar_card_image','father_adhaar_card_back_image'],
    ['mother_adhaar_card_image','mother_adhaar_card_back_image'], ['child_adhaar_card_image','child_adhaar_card_back_image'],
    ['modal_child_adhaar_card_image','modal_child_adhaar_card_back_image']
];
(async()=>{
    const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
    try {
        const page=await browser.newPage();
        await page.setContent(pairs.map(([front,back])=>`<form><input id="${front}" type="file"><div class="add-child-upload-card"><input id="${back}" type="file"><div class="add-child-file-name">old.png</div><div class="dlt_btn_div"><img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"></div></div><div data-saved-document-preview-for="${back}" style="display:flex">saved</div><div data-document-preview-for="${back}">selected</div></form>`).join(''));
        await page.addScriptTag({content:shared});
        await page.evaluate(() => {
            for (const frontId of ['adher_card_iamge', 'license_image']) {
                const front = document.getElementById(frontId);
                const panel = document.createElement('div');
                panel.className = 'driver-document-ocr';
                panel.dataset.bound = 'true';
                panel.dataset.inputId = frontId;
                panel.dataset.extraInputIds = frontId === 'license_image' ? 'license_back_image' : 'adher_card_back_image';
                front.form.appendChild(panel);
            }
        });
        const file={name:'new.png',mimeType:'image/png',buffer:Buffer.from('test')};
        for(const [front,back] of pairs) {
            await page.locator('#'+back).setInputFiles(file);
            await page.locator('#'+back).evaluate(input=>{input.dataset.scannedDocumentNumber='OLD';input.dataset.documentValidation='verified';});
            await page.locator('#'+front).setInputFiles(file);
            const result=await page.locator('#'+back).evaluate(input=>({count:input.files.length,number:input.dataset.scannedDocumentNumber || '',state:input.dataset.documentValidation || '',name:input.closest('.add-child-upload-card').querySelector('.add-child-file-name').textContent,custom:getComputedStyle(input.closest('.add-child-upload-card').querySelector('.dlt_btn_div')).display,saved:getComputedStyle(input.form.querySelector('[data-saved-document-preview-for]')).display,selected:input.form.querySelector('[data-document-preview-for]')}));
            assert.deepEqual(result,{count:0,number:'',state:'',name:'',custom:'none',saved:'none',selected:null},back);
            await page.locator('#'+back).setInputFiles(file);
            await page.locator('#'+front).evaluate(input => {
                input.addEventListener('click', event=>event.preventDefault(), {once:true});
                input.dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true}));
            });
            assert.equal(await page.locator('#'+front).evaluate(input=>input.files.length),0);
            await page.locator('#'+front).dispatchEvent('cancel');
            assert.equal(await page.locator('#'+front).evaluate(input=>input.files.length),1,'Cancel retains original front');
            assert.equal(await page.locator('#'+back).evaluate(input=>input.files.length),1,'Cancel retains paired back');
            const chooserPromise = page.waitForEvent('filechooser');
            await page.locator('#'+front).click();
            const chooser = await chooserPromise;
            await chooser.setFiles(file);
            assert.equal(await page.locator('#'+back).evaluate(input=>input.files.length),0,'Selecting the same front again must clear the back');
        }
        console.log('All 7 mappings passed, including managed driver fields, same-file selection, cancellation and preview cleanup');
    } finally {await browser.close();}
})().catch(error=>{console.error(error);process.exitCode=1;});
