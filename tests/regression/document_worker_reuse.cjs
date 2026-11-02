// Verify reuse resets document state, and cancellation/errors never retain a worker.
const assert=require('node:assert/strict'),fs=require('node:fs'),http=require('node:http'),path=require('node:path');
const {chromium}=require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const root=path.resolve(__dirname,'../../public');
const html=`<form><input name="insurance_number"><input name="insurance_expiry_date"><input type="file" id="front"><div class="driver-document-ocr" data-input-id="front" data-document-type="vehicle-insurance" data-vendor-base="/vendor/license-ocr/" data-field-map='{"insurance_number":"Policy","insurance_expiry_date":"Expiry"}'><div data-ocr-status></div><div data-ocr-results></div><button type="button" data-ocr-retry></button><button type="button" data-ocr-cancel></button></div></form><script>window.Swal={fire:()=>{}};window.created=0;window.resets=0;window.terminated=[];</script><script src="/js/driver-document-parser.js"></script><script src="/js/driver-document-ocr.js"></script>`;
const server=http.createServer((req,res)=>{
    res.setHeader('Content-Type','text/javascript');
    if(req.url==='/'){res.setHeader('Content-Type','text/html');return res.end(html)}
    if(req.url.endsWith('tesseract.min.js'))return res.end(`window.Tesseract={createWorker:async(lang,oem,options)=>{
        const id=++window.created;return {setParameters:async()=>{},
            reinitialize:async()=>{window.resets++;if(window.failReset){window.failReset=false;throw Error('Reset failed')}options.logger({status:'initializing api',progress:0})},
            terminate:async()=>{window.terminated.push(id)},
            recognize:async()=>{
                const policy=window.policy,invalid=window.invalid;
                window.recognizing=true;
                if(window.gate)await window.gate;
                options.logger({status:'recognizing text',progress:1});
                return {data:{text:invalid?'not an insurance document':'CERTIFICATE OF INSURANCE\\nPolicy No: '+policy+'\\nExpiry Date: 31/12/2030\\nRegistration No: GJ01AB1234',confidence:95,tsv:''}};
            }
        };
    }};`);
    const file=path.resolve(root,'.'+req.url);if(!file.startsWith(root+path.sep)||!fs.existsSync(file)){res.statusCode=404;return res.end()}res.end(fs.readFileSync(file));
});
(async()=>{let browser;try{
    await new Promise(r=>server.listen(0,'127.0.0.1',r));browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
    const page=await browser.newPage();await page.goto('http://127.0.0.1:'+server.address().port);
    const buffer=Buffer.from(await page.evaluate(()=>{const c=document.createElement('canvas');c.width=c.height=100;return c.toDataURL().split(',')[1]}),'base64');
    const upload=async(policy,wait=true)=>{
        await page.evaluate(policy=>{window.policy=policy;window.recognizing=false},policy);
        await page.locator('#front').setInputFiles({name:policy+'.png',mimeType:'image/png',buffer});
        if(wait){
            await page.waitForFunction(()=>['verified','invalid'].includes(document.querySelector('#front').dataset.documentValidation));
            await page.waitForFunction(()=>document.querySelector('[data-ocr-cancel]').hidden);
        }
    };
    await upload('300112345678');assert.equal(await page.evaluate(()=>window.created),1);
    await upload('400112345678');assert.equal(await page.evaluate(()=>window.created),1,'Second document should reuse the loaded engine');
    assert.equal(await page.evaluate(()=>window.resets),1,'Reused engine must reset recognition state');
    assert.equal(await page.locator('[name=insurance_number]').inputValue(),'400112345678','Previous document data must not leak');
    await page.evaluate(()=>{window.failReset=true});await upload('500112345678');
    assert.equal(await page.evaluate(()=>window.created),2,'Failed reset must transparently create a fresh worker');
    await page.evaluate(()=>{window.gate=new Promise(resolve=>{window.releaseGate=resolve})});
    await upload('600112345678',false);await page.waitForFunction(()=>window.recognizing);
    await page.locator('[data-ocr-cancel]').click();
    await page.evaluate(()=>{window.gate=null});await upload('700112345678');
    assert.equal(await page.evaluate(()=>window.created),3,'Cancelled worker must never be reused');
    await page.evaluate(()=>window.releaseGate());
    assert.equal(await page.locator('[name=insurance_number]').inputValue(),'700112345678','Cancelled result must not overwrite replacement');
    await page.evaluate(()=>{window.invalid=true});await upload('800112345678');
    assert.equal(await page.locator('#front').getAttribute('data-document-validation'),'invalid');
    await page.evaluate(()=>{window.invalid=false});await upload('900112345678');
    assert.equal(await page.evaluate(()=>window.created),4,'Failed scan must discard its worker');
    await page.evaluate(()=>window.dispatchEvent(new Event('pagehide')));await upload('200112345678');
    assert.equal(await page.evaluate(()=>window.created),5,'Page hiding must release the idle worker');
    await page.evaluate(()=>{
        window.dispatchEvent(new Event('pagehide'));
        const nativeTimeout=window.setTimeout.bind(window);
        window.setTimeout=(callback,delay,...args)=>nativeTimeout(callback,delay===30000?1:delay,...args);
    });
    await upload('210112345678');
    await page.waitForFunction(()=>window.terminated.includes(window.created));
    const count=await page.evaluate(()=>window.created);await upload('220112345678');
    assert.equal(await page.evaluate(()=>window.created),count+1,'Idle timer must release memory');
    console.log('Worker reuse resets state; failed reset recovers; cancelled/invalid workers discarded; pagehide and idle cleanup passed');
}finally{if(browser)await browser.close();await new Promise(r=>server.close(r))}})().catch(e=>{console.error(e);process.exitCode=1});
