const {chromium}=require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const fs=require('node:fs');
const assert=require('node:assert/strict');
const common=fs.readFileSync('public/js/common_js.js','utf8');
const code=common.slice(common.indexOf('window.resetFilePreview ='),common.indexOf('window.pdfPreviewPlaceholder ='));
const ids=['rc_image','rc_back_image','insurance_image','vehicle_image','license_image','license_back_image','adher_card_iamge','adher_card_back_image','father_adhaar_card_image','father_adhaar_card_back_image','mother_adhaar_card_image','mother_adhaar_card_back_image','child_adhaar_card_image','child_adhaar_card_back_image'];
(async()=>{
 const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 try{
  const page=await browser.newPage();
  for(const id of ids){
   await page.setContent(`<form><div class="form-group"><label>Upload <span class="text-danger">*</span></label><input type="file" id="${id}" class="is-invalid" aria-invalid="true"><span class="error-message document-pair-error">Please upload the matching back side again after changing the front image.</span><div class="invalid-feedback">Image required</div></div><span id="${id}-error">Old plugin validation</span><div class="form-group" id="other"><span class="error-message">Other document required</span></div></form>`);
   await page.evaluate(()=>{window.fileUploadErrorCleanupBound=false;});
   await page.addScriptTag({content:code});
   await page.locator('#'+id).setInputFiles({name:'selected.png',mimeType:'image/png',buffer:Buffer.from('test')});
   assert.equal(await page.locator('.document-pair-error').count(),0,id);
   assert.equal(await page.locator('.invalid-feedback').count(),0,id);
   assert.equal(await page.locator('#'+id+'-error').count(),0,id);
   assert.equal(await page.locator('#'+id).getAttribute('aria-invalid'),null);
   assert.equal(await page.locator('#other .error-message').count(),1,'Unrelated validation stays');
   assert.equal(await page.locator('label .text-danger').textContent(),'*','Required marker stays');
   await page.evaluate(id=>{const input=document.getElementById(id);input.value='';input.insertAdjacentHTML('afterend','<span class="error-message document-pair-error">Still required</span>');input.dispatchEvent(new Event('change',{bubbles:true}));},id);
   assert.equal(await page.locator('.document-pair-error').count(),1,'Cancelling picker does not hide required validation');
  }
  console.log(`PASS: ${ids.length} upload fields remove their stale red validation on selection; unrelated errors/required markers and empty selection preserved`);
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exit(1)});
