const {chromium}=require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const fs=require('node:fs');
const assert=require('node:assert/strict');
const common=fs.readFileSync('public/js/common_js.js','utf8');
const handlers=common.slice(common.indexOf('window.resetFilePreview ='),common.indexOf('window.pdfPreviewPlaceholder ='));
const render=common.slice(common.indexOf('function renderSelectedFilePreview('),common.indexOf('function previewImage('));
const ids=['rc_image','rc_back_image','insurance_image','vehicle_image','license_image','license_back_image','adher_card_iamge','adher_card_back_image','driver_image','father_adhaar_card_image','father_adhaar_card_back_image','mother_adhaar_card_image','mother_adhaar_card_back_image','child_adhaar_card_image','child_adhaar_card_back_image','child_image','cms_image'];
const png=Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jYwAAAABJRU5ErkJggg==','base64');
(async()=>{
 const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 try{
  const page=await browser.newPage();
  for(const id of ids){
   await page.setContent(`<style>#dlt_btn_div{display:ruby!important}img{display:block!important;width:100px;height:100px}</style><input type="file" id="${id}"><span id="name">Old file.jpg</span><div id="dlt_btn_div"><img id="preview" src="data:image/png;base64,${png.toString('base64')}" alt="Image Preview"><button id="remove" style="display:none">Remove</button><button id="delete">Delete</button></div>`);
   await page.addScriptTag({content:handlers+render});
   await page.evaluate(()=>{window.Swal={fire:async()=>({isConfirmed:true}),close:()=>{}};window.fetch=async()=>({json:async()=>({success:true})});});
   await page.evaluate(id=>window.deleteImageWithConfirm({url:'/mock-delete',csrfToken:'test',imageInputSelector:'#'+id,imagePreviewSelector:'#preview',buttonSelector:'#delete',nameSelector:'#name'}),id);
   await page.waitForFunction(()=>document.getElementById('name').textContent==='No image');
   assert.equal(await page.locator('#preview').isVisible(),false,id+': no broken image');
   assert.equal(await page.locator('#preview').getAttribute('src'),null);
   assert.equal(await page.locator('#delete').isVisible(),false);
   await page.evaluate(id=>{document.getElementById(id).onchange=event=>renderSelectedFilePreview(event,{previewId:'preview',nameId:'name',removeBtnId:'remove',deleteBtnId:'delete'});},id);
   await page.locator('#'+id).setInputFiles({name:'new.png',mimeType:'image/png',buffer:png});
   await page.waitForFunction(()=>document.getElementById('preview').complete&&document.getElementById('preview').naturalWidth>0);
   assert.equal(await page.locator('#preview').isVisible(),true,id+': new upload visible again');
   await page.evaluate(id=>window.clearImageSelection({imageInputSelector:'#'+id,imagePreviewSelector:'#preview',imageNameSelector:'#name',removeImageBtnSelector:'#remove'}),id);
   assert.equal(await page.locator('#preview').isVisible(),false);
   assert.equal(await page.locator('#preview').getAttribute('src'),null);
   assert.equal(await page.locator('#name').textContent(),'No image');
  }
  console.log(`PASS: ${ids.length} upload types: delete/remove shows No image, no empty src/broken preview, replacement upload visible`);
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exit(1)});
