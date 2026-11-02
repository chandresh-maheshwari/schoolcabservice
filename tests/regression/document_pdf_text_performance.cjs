// Text split over pages must not trigger OCR; scanned/mixed pages must still be read.
const assert=require('node:assert/strict'),fs=require('node:fs'),http=require('node:http'),path=require('node:path');
const {chromium}=require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const root=path.resolve(__dirname,'../../public');
const html=`<form><input name="insurance_number"><input name="insurance_expiry_date"><input type="file" id="proof"><div class="driver-document-ocr" data-input-id="proof" data-document-type="vehicle-insurance" data-vendor-base="/vendor/license-ocr/" data-field-map='{"insurance_number":"Policy","insurance_expiry_date":"Expiry"}'><div data-ocr-status></div><div data-ocr-results></div><button type="button" data-ocr-retry></button><button type="button" data-ocr-cancel></button></div></form><script>window.Swal={fire:()=>{}};</script><script src="/js/driver-document-parser.js"></script><script src="/js/driver-document-ocr.js"></script>`;
const server=http.createServer((req,res)=>{
    res.setHeader('Content-Type','text/javascript');
    if(req.url==='/'){res.setHeader('Content-Type','text/html');return res.end(html)}
    if(req.url.endsWith('pdf.min.mjs'))return res.end(`export const GlobalWorkerOptions={},OPS={paintImageXObject:85};
        export function getDocument(){return {destroy:async()=>{},promise:Promise.resolve({numPages:3,async getPage(number){
            const lines=number===1?['CERTIFICATE OF INSURANCE','Policy No: 300112345678']:number===2?['Expiry Date: 31/12/2030','Registration No: GJ01AB1234']:window.scenario==='scanned'?[]:['Policy terms and conditions'];
            return {cleanup(){},getTextContent:async()=>{window.textPages=(window.textPages||[]).concat(number);return {items:lines.map((str,i)=>({str,transform:[0,0,0,0,0,100-i*20],hasEOL:true}))}},
                getOperatorList:async()=>({fnArray:number===3&&window.scenario==='mixed'?[85]:[]}),
                getViewport:({scale})=>({width:600*scale,height:800*scale}),
                render:()=>{window.rasterPages=(window.rasterPages||[]).concat(number);return {promise:Promise.resolve()}}
            };
        }})}}
    `);
    if(req.url.endsWith('tesseract.min.js'))return res.end(`window.Tesseract={createWorker:async()=>({setParameters:async()=>{},terminate:async()=>{},recognize:async()=>{window.ocrPasses=(window.ocrPasses||0)+1;return {data:{text:'CERTIFICATE OF INSURANCE\\nPolicy No: 99999888887766665555\\nExpiry Date: 31/12/2031\\nRegistration No: GJ01AB1234',confidence:95,tsv:''}}}})};`);
    const file=path.resolve(root,'.'+req.url);
    if(!file.startsWith(root+path.sep)||!fs.existsSync(file)){res.statusCode=404;return res.end()}
    res.end(fs.readFileSync(file));
});
(async()=>{let browser;try{
    await new Promise(r=>server.listen(0,'127.0.0.1',r));
    browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
    for(const scenario of ['text','scanned','mixed']){
        const page=await browser.newPage();await page.goto('http://127.0.0.1:'+server.address().port);
        await page.evaluate(value=>{window.scenario=value},scenario);
        await page.locator('#proof').setInputFiles({name:'policy.pdf',mimeType:'application/pdf',buffer:Buffer.from('%PDF synthetic test fixture')});
        await page.waitForFunction(()=>['verified','invalid'].includes(document.querySelector('#proof').dataset.documentValidation));
        assert.deepEqual(await page.evaluate(()=>window.textPages),[1,2,3],'All pages must be read before autofill');
        if(scenario==='text'){
            assert.equal(await page.locator('#proof').getAttribute('data-document-validation'),'verified');
            assert.equal(await page.locator('[name=insurance_number]').inputValue(),'300112345678');
            assert.equal(await page.evaluate(()=>window.ocrPasses||0),0,'Complete PDF text must avoid OCR');
            assert.deepEqual(await page.evaluate(()=>window.rasterPages||[]),[]);
        }else{
            assert.equal(await page.evaluate(()=>window.ocrPasses),1,'Scanned pages must not be skipped');
            assert.deepEqual(await page.evaluate(()=>window.rasterPages),[3]);
            assert.equal(await page.locator('[name=insurance_number]').inputValue(),'99999888887766665555','Scanned-page content must participate in the normal parser result');
        }
        await page.close();
    }
    console.log('Multi-page PDF text avoids OCR; scanned/mixed-page contents still participate in parsing');
}finally{if(browser)await browser.close();await new Promise(r=>server.close(r))}})().catch(e=>{console.error(e);process.exitCode=1});
