const {chromium}=require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const fs=require('node:fs'), http=require('node:http'), path=require('node:path'), assert=require('node:assert/strict');
const root=path.resolve('public');
const texts={
 aadhaar:['Government of India\nAADHAAR\nAMIT PATEL\nDOB: 01/01/1990\nMale\n2345 6789 0123','UIDAI\nAddress: House 12, Lake Road, Anand, Gujarat 388001\n2345 6789 0123'],
 license:['DRIVING LICENCE\nDL No: GJ0120200012345\nName: AMIT PATEL\nDOB: 01/01/1990\nValid Till: 31/12/2030','GJ0120200012345\nLMV\nMCWG\nPermanent Address\nIssuing Authority Sign\nDTO Example'],
 'vehicle-rc':['CERTIFICATE OF REGISTRATION\nRegistration No: GJ01AB1234\nOwner: AMIT PATEL\nChassis: ABC123\nEngine: XYZ123\nValid Till: 31/12/2030','CERTIFICATE OF REGISTRATION\nRegistration No: GJ01AB1234\nChassis: ABC123\nEngine: XYZ123'],
 'vehicle-insurance':['CERTIFICATE OF INSURANCE\nPolicy Number: 123456789012\nRegistration No: GJ01AB1234\nExpiry: 31/12/2030']
};
const cases=[['driver','aadhaar',['driver_name','adher_no','current_address']],['driver','license',['license_no','license_expiry_date']],['parent-father','aadhaar',['father_name','father_aadhaar_number','address_1']],['parent-mother','aadhaar',['mother_name','mother_aadhaar_number']],['child','aadhaar',['child_name','child_aadhaar_number','home_address']],['vehicle','vehicle-rc',['rc_number','vehicle_number','rc_expiry_date']],['vehicle','vehicle-insurance',['insurance_number','insurance_expiry_date']]];
// Exercise the actual Blade conditions before FileReader has created a preview.
for(const module of ['driver','vehicle','parent'])for(const mode of ['create','edit']){
 const source=fs.readFileSync(`resources/views/${module}/${mode}.blade.php`,'utf8');
 for(const match of source.matchAll(/!(imageInput\d*)\.files\.length && \((isDefaultImage\d*) \|\| (currentImageSrc\d*) == "#" \|\| \3 == ""\)/g)){
  const required=new Function(match[1],match[2],match[3],`return ${match[0]}`);
  assert.equal(required({files:[{}]},false,''),false,`${module}/${mode}: selected file with pending preview`);
  assert.equal(required({files:[]},false,''),true,`${module}/${mode}: deleted file remains required`);
  assert.equal(required({files:[]},false,'saved.jpg'),false,`${module}/${mode}: existing saved image`);
 }
}
const server=http.createServer((req,res)=>{
 if(req.url==='/'){res.setHeader('Content-Type','text/html');return res.end('<!doctype html><html><body></body></html>');}
 if(req.url.endsWith('tesseract.min.js')){res.setHeader('Content-Type','text/javascript');return res.end('window.Tesseract={createWorker:async()=>({setParameters:async()=>{},recognize:async()=>({data:{text:window.testText,confidence:95,tsv:""}}),terminate:async()=>{}})};');}
 const file=path.resolve(root,'.'+req.url);if(!file.startsWith(root+path.sep)||!fs.existsSync(file)){res.statusCode=404;return res.end();}res.setHeader('Content-Type',file.endsWith('.js')?'text/javascript':'application/octet-stream');fs.createReadStream(file).pipe(res);
});
(async()=>{let browser;try{
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 const page=await browser.newPage();await page.goto(`http://127.0.0.1:${server.address().port}/`);
 const common=fs.readFileSync('public/js/common_js.js','utf8');
 const buffer=Buffer.from(await page.evaluate(()=>{const c=document.createElement('canvas');c.width=800;c.height=600;return c.toDataURL().split(',')[1];}),'base64');
 let checks=0;
 for(const [module,type,fields] of cases){
  await page.setContent(`<form><div class="form-group"><input id="front" type="file"></div>${texts[type][1]?'<div class="form-group"><input id="back" type="file"></div>':''}${fields.map(f=>`<input id="${f}" name="${f}">`).join('')}<input name="unrelated" value="Keep"><span class="error-message" id="unrelated-error">Keep unrelated error</span><div class="driver-document-ocr" data-input-id="front" data-extra-input-ids="${texts[type][1]?'back':''}" data-document-type="${type}" data-vendor-base="/vendor/license-ocr/" data-field-map='${JSON.stringify(Object.fromEntries(fields.map(f=>[f,f])))}'><div data-ocr-status></div><div data-ocr-results></div><button type="button" data-ocr-retry></button><button type="button" data-ocr-cancel></button></div><button type="submit">Submit</button></form>`);
  await page.evaluate(()=>{window.fileUploadErrorCleanupBound=false;window.fieldValidationCleanupBound=false;window.errors=[];window.Swal={fire:options=>{window.errors.push(options.text);return Promise.resolve({isConfirmed:true});},close:()=>{}};});
  await page.addScriptTag({content:common.slice(common.indexOf('window.resetFilePreview ='),common.indexOf('window.pdfPreviewPlaceholder ='))});
  await page.addScriptTag({path:'public/js/driver-document-parser.js'});await page.addScriptTag({path:'public/js/driver-document-ocr.js'});
  for(let cycle=0;cycle<2;cycle++){
   if(cycle){
    for(const side of texts[type][1]?['front','back']:['front'])await page.evaluate(side=>window.clearImageSelection({imageInputSelector:'#'+side}),side);
    await page.evaluate(fields=>{for(const key of fields){const field=document.getElementById(key);field.classList.add('is-invalid');field.insertAdjacentHTML('afterend','<span class="error-message">Required</span>');}},fields);
   }
   for(const [index,side] of (texts[type][1]?['front','back']:['front']).entries()){
    await page.evaluate(text=>{window.testText=text;},texts[type][index].replace(/Male/g,module==='parent-mother'?'Female':'Male'));
    await page.locator('#'+side).setInputFiles({name:side+'.png',mimeType:'image/png',buffer});
    await page.waitForFunction(side=>document.getElementById(side).dataset.documentValidation==='verified',side,{timeout:15000});
    assert.equal(await page.locator('[type=submit]').isEnabled(),true,module+'/'+type+'/'+side+' unlock');
    checks++;
   }
   const valid=await page.evaluate(()=>window.validateDriverDocumentPairs(document.querySelector('form')));
   assert.equal(valid.valid,true,module+'/'+type+' cycle '+cycle+': '+valid.message);
   for(const field of fields){if(await page.locator('#'+field).inputValue())assert.equal(await page.locator('#'+field).evaluate(f=>f.nextElementSibling?.classList.contains('error-message')||false),false,field+' stale required');}
   assert.equal(await page.locator('#unrelated-error').count(),1);
  }
  // A wrong document must remain rejected, then the same valid front must recover.
  await page.evaluate(text=>{window.testText=text;},type==='aadhaar'?texts.license[0]:texts.aadhaar[0]);
  await page.locator('#front').setInputFiles({name:'wrong.png',mimeType:'image/png',buffer});
  await page.waitForFunction(()=>document.getElementById('front').dataset.documentValidation==='invalid');
  await page.evaluate(text=>{window.testText=text;},texts[type][0].replace(/Male/g,module==='parent-mother'?'Female':'Male'));
  await page.locator('#front').setInputFiles({name:'front.png',mimeType:'image/png',buffer});
  await page.waitForFunction(()=>document.getElementById('front').dataset.documentValidation==='verified');
  assert.equal(await page.locator('[type=submit]').isEnabled(),true);
 }
 console.log(`PASS: ${checks} front/back uploads across driver/vehicle/parent/child; delete-required-reupload, wrong-to-valid recovery, field errors and controls reset`);
}finally{await browser?.close();server.close();}})().catch(e=>{console.error(e);process.exitCode=1;});
