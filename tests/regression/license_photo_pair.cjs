// Uses only synthetic documents and local assets; never submits a driver form.
const {chromium} = require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../../public');
const maps = {
  license: {driver_name: 'Driver Name', license_no: 'License Number', license_expiry_date: 'Expiry Date'},
  aadhaar: {driver_name: 'Driver Name', adher_no: 'Aadhaar Number', current_address: 'Current Address'},
  father: {father_name: 'Father Name', father_aadhaar_number: 'Father Aadhaar Number', current_address: 'Current Address'},
  mother: {mother_name: 'Mother Name', mother_aadhaar_number: 'Mother Aadhaar Number', current_address: 'Current Address'},
  rc: {vehicle_number: 'Vehicle Number', rc_number: 'RC Number', rc_expiry_date: 'RC Expiry Date'},
  insurance: {insurance_number: 'Insurance Number', insurance_expiry_date: 'Insurance Expiry Date'}
};
const panel = (id, type, map, extraId = '') => `<input type="file" id="${id}" name="${id}">${extraId ? `<input type="file" id="${extraId}" name="${extraId}">` : ''}<div class="driver-document-ocr" data-document-type="${type}" data-input-id="${id}" ${extraId ? `data-extra-input-ids="${extraId}"` : ''} data-field-map='${JSON.stringify(map)}' data-vendor-base="/vendor/license-ocr/"><div data-ocr-status></div><div data-ocr-results></div><button type="button" data-ocr-retry hidden>Retry</button><button type="button" data-ocr-cancel hidden>Cancel</button></div>`;
const html = `<form>${['driver_name','license_no','license_expiry_date','adher_no','father_name','father_aadhaar_number','mother_name','mother_aadhaar_number','vehicle_number','rc_number','rc_expiry_date','insurance_number','insurance_expiry_date','current_address'].map(name => `<input name="${name}">`).join('')}${panel('license','license',maps.license,'license_back')}${panel('aadhaar','aadhaar',maps.aadhaar,'aadhaar_back')}${panel('father','aadhaar',maps.father)}${panel('mother','aadhaar',maps.mother)}${panel('rc','vehicle-rc',maps.rc)}${panel('insurance','vehicle-insurance',maps.insurance)}</form><script src="/js/driver-document-parser.js"></script><script src="/js/driver-document-ocr.js"></script>`;
const server = http.createServer((req,res) => {
 if (req.url === '/') {res.setHeader('Content-Type','text/html'); return res.end(html);}
 const file = path.resolve(root, '.' + decodeURIComponent(req.url.split('?')[0]));
 if (!file.startsWith(root + path.sep) || !fs.existsSync(file)) {res.statusCode=404;return res.end();}
 res.setHeader('Content-Type', ({'.js':'text/javascript','.mjs':'text/javascript','.wasm':'application/wasm'})[path.extname(file)] || 'application/octet-stream');
 fs.createReadStream(file).pipe(res);
});
function pdf(lines) {
 const stream = `BT /F1 18 Tf 50 750 Td ${lines.map((line,i) => `${i ? '0 -35 Td ' : ''}(${line}) Tj`).join('\n')} ET`;
 const objects = ['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 600 800] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>','<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',`<< /Length ${stream.length} >>\nstream\n${stream}\nendstream`];
 let text='%PDF-1.4\n', offsets=[0];
 objects.forEach((o,i)=>{offsets.push(text.length);text+=`${i+1} 0 obj\n${o}\nendobj\n`;});
 const xref=text.length;
 text+=`xref\n0 6\n0000000000 65535 f \n${offsets.slice(1).map(n=>String(n).padStart(10,'0')+' 00000 n \n').join('')}trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF`;
 return Buffer.from(text);
}

// Supply front/back photos as command arguments; identity images are not stored in the repo.
(async()=>{
 const [front,back]=process.argv.slice(2);
 if(!front||!back)throw new Error('Usage: node tests/regression/license_photo_pair.cjs <front-image> <back-image>');
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 try {
  const page=await browser.newPage();const errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://127.0.0.1:'+server.address().port);
  const scan=async(id,file)=>{
   await page.locator('#'+id).setInputFiles(file);
   await page.waitForFunction(id=>{
    const panel=[...document.querySelectorAll('.driver-document-ocr')].find(panel=>panel.dataset.inputId===id||String(panel.dataset.extraInputIds||'').split(',').includes(id));
    return panel.querySelector('[data-ocr-cancel]').hidden && document.getElementById(id).dataset.documentValidation !== 'pending';
   },id,{timeout:180000});
   const state=await page.locator('#'+id).getAttribute('data-document-validation');
   assert.equal(state,'verified',id+': '+await page.locator('[data-ocr-status]').first().textContent());
  };
  await scan('license',front);
  const number=await page.locator('[name=license_no]').inputValue();
  assert.ok(number,'Front must extract licence number');
  const expiry=await page.locator('[name=license_expiry_date]').inputValue();
  if(process.argv[4]) assert.equal(expiry,process.argv[4],'Front expiry must be extracted');
  await scan('license_back',back);
  assert.equal(await page.locator('[name=license_no]').inputValue(),number,'Back preserves front number');
  assert.equal(await page.locator('[name=license_expiry_date]').inputValue(),expiry,'Back preserves front expiry');
  assert.equal(await page.locator('#license_back').getAttribute('data-scanned-document-number'),number,'Both sides identify same licence');
  if (process.argv[5] && process.argv[6]) {
   await scan('aadhaar',process.argv[5]);
   await scan('aadhaar_back',process.argv[6]);
   const layout=fs.readFileSync(path.resolve(root,'../resources/views/admin_layout/index.blade.php'),'utf8');
   const validation=layout.slice(layout.indexOf('    window.normalizeDocumentPersonName ='),layout.indexOf('    window.validateParentAadhaarOwners ='));
   await page.addScriptTag({content:'window.nameErrors=[];window.showDocumentValidationMessage=message=>nameErrors.push(message);'+validation});
   const result=await page.evaluate(()=>({
    valid:window.validateDriverDocumentNames({querySelector:selector=>document.querySelector(selector==='#license_image'?'#license':selector==='#adher_card_iamge'?'#aadhaar':selector)}),
    errors:window.nameErrors,
    licenceName:document.querySelector('#license').dataset.scannedDriverName,
   }));
   if(process.argv[7])assert.equal(String(result.licenceName||'').replace(/\s/g,''),process.argv[7].replace(/\s/g,''),'Licence name read from owner-name area');
   assert.equal(result.valid,true,JSON.stringify(result));
  }
  assert.deepEqual(errors,[]);
  console.log('PASS: real front/back licence photos verified; matching number; '+(process.argv[4]?'expected expiry extracted; ':'')+'front fields preserved');
 }finally{await browser.close();server.close()}
})().catch(error=>{console.error(error);server.close();process.exit(1)});
