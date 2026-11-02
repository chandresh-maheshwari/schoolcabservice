// Mock OCR verifies that weak/conflicting evidence cannot use the fast path.
const assert=require('node:assert/strict'),fs=require('node:fs'),http=require('node:http'),path=require('node:path');
const {chromium}=require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const root=path.resolve(__dirname,'../../public');
const text='CERTIFICATE OF INSURANCE\nPolicy No: 300112345678\nExpiry Date: 31/12/2030\nRegistration No: GJ01AB1234';
const html=`<form><input name="insurance_number"><input name="insurance_expiry_date"><input type="file" id="front"><div class="driver-document-ocr" data-input-id="front" data-document-type="vehicle-insurance" data-vendor-base="/vendor/license-ocr/" data-field-map='{"insurance_number":"Policy","insurance_expiry_date":"Expiry"}'><div data-ocr-status></div><div data-ocr-results></div><button type="button" data-ocr-retry></button><button type="button" data-ocr-cancel></button></div></form><script>window.Swal={fire:()=>{}};window.baseText=${JSON.stringify(text)};</script><script src="/js/driver-document-parser.js"></script><script src="/js/driver-document-ocr.js"></script>`;
const server=http.createServer((req,res)=>{
    res.setHeader('Content-Type','text/javascript');
    if(req.url==='/'){res.setHeader('Content-Type','text/html');return res.end(html)}
    if(req.url.endsWith('tesseract.min.js'))return res.end(`window.Tesseract={createWorker:async()=>({setParameters:async()=>{},terminate:async()=>{},recognize:async()=>{
        window.passes=(window.passes||0)+1;
        let text=window.baseText,confidence=95;
        if(window.mode==='weak')confidence=65;
        if(window.mode==='conflict'&&window.passes===2)text=text.replace('300112345678','999998888877');
        if(window.mode==='expired')text=text.replace('31/12/2030','31/12/2000');
        if(window.mode==='wrong')text='DRIVING LICENCE\\nDL No: GJ0120200012345\\nName: TEST PERSON\\nDOB: 01/01/1990\\nValid Till: 31/12/2030';
        return {data:{text,confidence,tsv:''}};
    }})};`);
    const file=path.resolve(root,'.'+req.url);if(!file.startsWith(root+path.sep)||!fs.existsSync(file)){res.statusCode=404;return res.end()}res.end(fs.readFileSync(file));
});
(async()=>{let browser;try{
    await new Promise(r=>server.listen(0,'127.0.0.1',r));browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
    for(const mode of ['clear','weak','conflict','wrong','expired','mismatch']){
        const page=await browser.newPage();await page.goto('http://127.0.0.1:'+server.address().port);await page.evaluate(mode=>{window.mode=mode},mode);
        await page.evaluate(mode=>{
            window.isDisplayDateBeforeToday=value=>value.endsWith('/2000');
            window.getTodayDisplayDate=()=> '02/11/2026';
            if(mode==='mismatch'){
                const rc=document.createElement('input');rc.type='file';rc.id='rc_image';rc.dataset.scannedVehicleNumber='MH01CD5678';document.querySelector('form').appendChild(rc);
            }
        },mode);
        const buffer=Buffer.from(await page.evaluate(()=>{const c=document.createElement('canvas');c.width=100;c.height=100;return c.toDataURL().split(',')[1]}),'base64');
        await page.locator('#front').setInputFiles({name:'proof.png',mimeType:'image/png',buffer});
        await page.waitForFunction(()=>['verified','invalid'].includes(document.querySelector('#front').dataset.documentValidation));
        const passes=await page.evaluate(()=>window.passes);
        if(['clear','expired','mismatch'].includes(mode))assert.equal(passes,2,'Two agreeing clear reads must avoid fallback crops');
        else assert.ok(passes>2,`${mode} evidence must retain fallback scans`);
        if(['wrong','expired','mismatch'].includes(mode)){
            assert.equal(await page.locator('#front').getAttribute('data-document-validation'),'invalid');
            assert.equal(await page.locator('[name=insurance_number]').inputValue(),'');
        }
        await page.close();
    }
    console.log('Two clear reads finish early; weak/conflicting evidence retains fallback; wrong, expired and vehicle-mismatched documents stay rejected');
}finally{if(browser)await browser.close();await new Promise(r=>server.close(r))}})().catch(e=>{console.error(e);process.exitCode=1});
