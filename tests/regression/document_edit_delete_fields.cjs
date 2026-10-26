const {chromium}=require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const fs=require('node:fs');
const assert=require('node:assert/strict');
const cases=[
 ['driver-aadhaar','aadhaar',['driver_name','adher_no','current_address'],true],
 ['driver-licence','license',['license_no','license_expiry_date'],true],
 ['parent-father','aadhaar',['father_name','father_aadhaar_number','address_1','address_2','state','city','pincode'],true],
 ['parent-mother','aadhaar',['mother_name','mother_aadhaar_number'],true],
 ['child','aadhaar',['child_name','child_aadhaar_number','gender','date_of_birth','home_address'],true],
 ['modal-child','aadhaar',['child_name','child_aadhaar_number','gender','date_of_birth','home_address'],true],
 ['vehicle-rc','vehicle-rc',['vehicle_number','rc_number','rc_expiry_date'],true],
 ['vehicle-insurance','vehicle-insurance',['insurance_number','insurance_expiry_date'],false],
];
(async()=>{
 const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 try{
  const page=await browser.newPage();

  const common=fs.readFileSync('public/js/common_js.js','utf8');
  let checks=0;
  for(const [id,type,fields,back] of cases){
   const owners=Object.fromEntries(fields.map(key=>[key,id+(back&&/^(current_address|home_address|address_1|address_2|state|city|pincode)$/.test(key)?'-back':'-front')]));
   for(const side of back?['front','back']:['front']){
    for(const method of ['saved','selected']){
     await page.setContent('<form id="test"><input type="file" id="'+id+'-front">'+(back?'<input type="file" id="'+id+'-back">':'')+'<input name="unrelated" value="Keep unrelated data">'+fields.map(key=>'<input name="'+key+'" value="Saved value">').join('')+'<div class="driver-document-ocr" data-document-type="'+type+'" data-input-id="'+id+'-front" data-extra-input-ids="'+(back?id+'-back':'')+'" data-field-map=\''+JSON.stringify(Object.fromEntries(fields.map(f=>[f,f])))+'\' data-field-sources=\''+JSON.stringify(owners)+'\'><div data-ocr-status>Old status</div><div data-ocr-results></div><button type="button" data-ocr-retry>Retry</button><button type="button" data-ocr-cancel hidden>Cancel</button></div></form>');
     await page.addScriptTag({content:common.slice(common.indexOf('window.resetFilePreview ='),common.indexOf('window.pdfPreviewPlaceholder ='))});
     await page.addScriptTag({path:'public/js/driver-document-ocr.js'});
     await page.evaluate(()=>{window.allowDelete=true;window.deleteSuccess=true;window.Swal={fire:async()=>({isConfirmed:window.allowDelete}),close:()=>{}};window.fetch=async()=>({json:async()=>({success:window.deleteSuccess})});});
     const own=fields.filter(key=>owners[key]===id+'-'+side);
     const manual=own[0];
     if(manual)await page.locator('[name="'+manual+'"]').fill('Manual correction');
     await page.evaluate(({id,side,method})=>{const input=document.getElementById(id+'-'+side);input.dataset.scannedDocumentNumber='OLD';if(method==='saved')window.deleteImageWithConfirm({url:'/mock-delete',csrfToken:'test',imageInputSelector:'#'+input.id});else window.clearImageSelection({imageInputSelector:'#'+input.id});},{id,side,method});
     await page.waitForTimeout(30);
     for(const key of fields){
      const expected=key===manual?'Manual correction':own.includes(key)?'':'Saved value';
      assert.equal(await page.locator('[name="'+key+'"]').inputValue(),expected,id+'/'+side+'/'+method+'/'+key);
     }
     assert.equal(await page.locator('[name="unrelated"]').inputValue(),'Keep unrelated data');
     assert.equal(await page.locator('[data-ocr-status]').textContent(),'');
     const sources=JSON.parse(await page.locator('[name="document_field_sources"]').inputValue());
     for(const key of fields)assert.equal(sources[key],own.includes(key)?undefined:owners[key]);
     for(const [confirmed,success] of [[false,true],[true,false]]){
      const keep=fields[0];await page.locator('[name="'+keep+'"]').fill('Keep on failure');
      await page.evaluate(({id,side,confirmed,success})=>{window.allowDelete=confirmed;window.deleteSuccess=success;window.deleteImageWithConfirm({url:'/mock-delete',csrfToken:'test',imageInputSelector:'#'+id+'-'+side});},{id,side,confirmed,success});
      await page.waitForTimeout(20);assert.equal(await page.locator('[name="'+keep+'"]').inputValue(),'Keep on failure');
     }
     checks++;
    }
   }
  }
  const bindings={driver:4,parent:4,child:2,vehicle:3};
  for(const [module,count] of Object.entries(bindings)){
   const view=fs.readFileSync(`resources/views/${module}/edit.blade.php`,'utf8');
   const blocks=[...view.matchAll(/window\.deleteImageWithConfirm\(\{([\s\S]*?)\}\);/g)];
   assert.equal(blocks.filter(block=>block[1].includes('imageInputSelector:')).length,count,module+' saved-document delete bindings');
  }
  console.log(`PASS: ${checks} source-specific deletions clear only owned fields; opposite-side and manual fields preserved; cancelled/failed deletes preserve data; all edit bindings covered`);
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exit(1)});
