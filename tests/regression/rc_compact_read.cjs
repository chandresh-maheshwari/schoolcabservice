// A faster second RC read must never change registration/chassis/engine evidence.
const assert=require('node:assert/strict'),fs=require('node:fs'),http=require('node:http'),path=require('node:path');
const {chromium}=require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const root=path.resolve(__dirname,'../../public');
const lines=['CERTIFICATE OF REGISTRATION','Registration No: GJ01AB1234','Owner Name: TEST PERSON','Chassis No: ABC123456789','Engine No: XYZ123456789','Registration Valid Upto: 31/12/2030'];
const html=`<form><input name="vehicle_number"><input name="rc_number"><input name="rc_expiry_date"><input type="file" id="front"><div class="driver-document-ocr" data-input-id="front" data-document-type="vehicle-rc" data-vendor-base="/vendor/license-ocr/" data-field-map='{"vehicle_number":"Vehicle","rc_number":"RC","rc_expiry_date":"Expiry"}'><div data-ocr-status></div><div data-ocr-results></div><button type="button" data-ocr-retry></button><button type="button" data-ocr-cancel></button></div></form><script>window.Swal={fire:()=>{}};window.lines=${JSON.stringify(lines)};</script><script src="/js/driver-document-parser.js"></script><script src="/js/driver-document-ocr.js"></script>`;
const server=http.createServer((req,res)=>{
    res.setHeader('Content-Type','text/javascript');if(req.url==='/'){res.setHeader('Content-Type','text/html');return res.end(html)}
    if(req.url.endsWith('tesseract.min.js'))return res.end(`window.Tesseract={createWorker:async()=>({setParameters:async()=>{},terminate:async()=>{},recognize:async c=>{
        window.passes=(window.passes||0)+1;
        const lines=window.lines.filter(line=>!window.noEngine||!line.startsWith('Engine'));
        const compact=c.width!==3600;
        const selected=lines.map(line=>compact&&window.changed?line.replace('ABC123456789','OTHER1234567'):line);
        const tsv='header\\n'+selected.map((text,i)=>['5','1','1','1',String(i+1),'1','200',String(150+i*80),'1000','40','95',text].join('\\t')).join('\\n');
        return {data:{text:selected.join('\\n'),confidence:95,tsv}};
    }})};`);
    const file=path.resolve(root,'.'+req.url);if(!file.startsWith(root+path.sep)||!fs.existsSync(file)){res.statusCode=404;return res.end()}res.end(fs.readFileSync(file));
});
(async()=>{let browser;try{
    await new Promise(r=>server.listen(0,'127.0.0.1',r));browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
    for(const scenario of [{changed:false,noEngine:false,passes:2},{changed:true,noEngine:false,passes:3},{changed:false,noEngine:true,passes:2}]){
        const page=await browser.newPage();await page.goto('http://127.0.0.1:'+server.address().port);await page.evaluate(({changed,noEngine})=>Object.assign(window,{changed,noEngine}),scenario);
        const buffer=Buffer.from(await page.evaluate(()=>{const c=document.createElement('canvas');c.width=1600;c.height=1000;return c.toDataURL().split(',')[1]}),'base64');
        await page.locator('#front').setInputFiles({name:'rc.png',mimeType:'image/png',buffer});await page.waitForFunction(()=>['verified','invalid'].includes(document.querySelector('#front').dataset.documentValidation));
        assert.equal(await page.locator('#front').getAttribute('data-document-validation'),'verified');
        assert.equal(await page.evaluate(()=>window.passes),scenario.passes);
        assert.equal(await page.locator('[name=rc_number]').inputValue(),'GJ01AB1234');
        const identity=await page.locator('#front').evaluate(input=>JSON.parse(input.dataset.scannedRcIdentity));
        assert.equal(identity.chassis,'ABC123456789','A conflicting compact read must not replace the full-frame identity');
        assert.equal(identity.engine,scenario.noEngine?'':'XYZ123456789');
        await page.close();
    }
    console.log('Compact RC reads preserve identity; changed/missing identity uses full-frame confirmation');
}finally{if(browser)await browser.close();await new Promise(r=>server.close(r))}})().catch(e=>{console.error(e);process.exitCode=1});
