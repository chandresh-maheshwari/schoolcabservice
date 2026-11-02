// Supply a back image path; no personal values/images are stored in this test.
const assert=require('node:assert/strict'),fs=require('node:fs'),http=require('node:http'),path=require('node:path');
const {chromium}=require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const root=path.resolve(__dirname,'../../public');
const map={father_name:'Father Name',father_aadhaar_number:'Father Number',address_1:'Address 1',address_2:'Address 2',state:'State',city:'City',pincode:'Pincode'};
const html=`<form>${Object.keys(map).map(key=>['state','city'].includes(key)?`<select name="${key}"><option value=""></option><option value="${key==='state'?'Gujarat':'Ahmedabad'}">${key==='state'?'Gujarat':'Ahmedabad'}</option></select>`:`<input name="${key}" value="${key==='father_name'?'SAVED PARENT NAME':''}">`).join('')}<input type="file" id="front"><input type="file" id="back"><div class="driver-document-ocr" data-input-id="front" data-extra-input-ids="back" data-document-type="aadhaar" data-vendor-base="/vendor/license-ocr/" data-field-map='${JSON.stringify(map)}'><div data-ocr-status></div><div data-ocr-results></div><button type="button" data-ocr-retry></button><button type="button" data-ocr-cancel></button></div></form><script>window.popups=[];window.Swal={fire:o=>window.popups.push(o.text)};</script><script src="/js/driver-document-parser.js"></script><script src="/js/driver-document-ocr.js"></script>`;
const server=http.createServer((req,res)=>{
    if(req.url==='/'){res.setHeader('Content-Type','text/html');return res.end(html)}
    const file=path.resolve(root,'.'+req.url);if(!file.startsWith(root+path.sep)||!fs.existsSync(file)){res.statusCode=404;return res.end()}
    res.setHeader('Content-Type',({'.js':'text/javascript','.mjs':'text/javascript','.wasm':'application/wasm'})[path.extname(file)]||'application/octet-stream');
    fs.createReadStream(file).pipe(res);
});
(async()=>{let browser;try{
    assert.ok(process.argv[2],'Supply the back image path');
    await new Promise(r=>server.listen(0,'127.0.0.1',r));browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
    const page=await browser.newPage();await page.goto('http://127.0.0.1:'+server.address().port);
    await page.evaluate(()=>{
        const validate=DriverDocumentParser.validateDocument;
        DriverDocumentParser.validateDocument=(text,type,side,parsed)=>{window.validationText=text;return validate(text,type,side,parsed)};
    });
    const started=Date.now();await page.locator('#back').setInputFiles(process.argv[2]);
    await page.waitForFunction(()=>['verified','invalid'].includes(document.querySelector('#back').dataset.documentValidation),{timeout:150000});
    console.log(JSON.stringify(await page.evaluate(()=>({state:document.querySelector('#back').dataset.documentValidation,popups:window.popups,issuerLines:(window.validationText||'').split('\n').filter(line=>/uid|ident|auth|aad|india/i.test(line)).map(line=>line.replace(/[0-9]/g,'#')),filled:Object.fromEntries([...document.querySelectorAll('input[name],select[name]')].map(field=>[field.name,Boolean(field.value)]))}))));
    assert.equal(await page.locator('#back').getAttribute('data-document-validation'),'verified');
    assert.equal(await page.locator('[name=city]').inputValue(),'Ahmedabad','Canonical city option must be selected');
    assert.equal(await page.locator('[name=father_name]').inputValue(),'SAVED PARENT NAME','Back must preserve the saved name');
    assert.ok(await page.locator('[name=father_aadhaar_number]').inputValue(),'Back must extract its Aadhaar number');
    for (const key of ['address_1','address_2','city']) assert.ok(await page.locator('[name='+key+']').inputValue(),key+' must be populated');
    // Low-confidence street text must remain editable rather than be guessed.
    assert.equal(await page.locator('[name=address_1]').isEnabled(),true);
    assert.ok(await page.locator('[name=state]').inputValue(),'State must be read');
    assert.ok(await page.locator('[name=pincode]').inputValue(),'Pincode must be read');
    assert.equal(await page.evaluate(()=>window.popups.length),0);
    const pair = await page.evaluate(()=>{
        const front=document.querySelector('#front'),back=document.querySelector('#back');
        const transfer=new DataTransfer();transfer.items.add(new File(['synthetic front'],'front.png',{type:'image/png'}));front.files=transfer.files;
        Object.assign(front.dataset,{documentValidation:'verified',aadhaarSide:'front',aadhaarVerified:'true',scannedAadhaarNumber:back.dataset.scannedAadhaarNumber});
        const matching=validateDriverDocumentPairs(document.querySelector('form')).valid;
        const value=front.dataset.scannedAadhaarNumber;front.dataset.scannedAadhaarNumber=value.slice(0,-1)+(Number(value.slice(-1))+1)%10;
        return {matching,mismatch:validateDriverDocumentPairs(document.querySelector('form')).valid};
    });
    assert.equal(pair.matching,true,'Matching front and back must remain valid');
    assert.equal(pair.mismatch,false,'A different front must remain rejected');
    console.log(`Parent back filled both address fields and city dropdown; name preserved, unclear street editable, mismatches rejected (${((Date.now()-started)/1000).toFixed(2)}s)`);
}finally{if(browser)await browser.close();await new Promise(r=>server.close(r))}})().catch(e=>{console.error(e);process.exitCode=1});
