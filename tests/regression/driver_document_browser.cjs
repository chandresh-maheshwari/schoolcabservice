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
(async()=>{
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 const origin=`http://127.0.0.1:${server.address().port}`;
 const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 try {
 const page=await browser.newPage(); const errors=[];
 page.on('pageerror',e=>errors.push(e.message));
 await page.route('**/*',route=>route.request().url().startsWith(origin)||route.request().url().startsWith('blob:') ? route.continue() : route.abort());
 await page.goto(origin);
 const image=async lines=>Buffer.from(await page.evaluate(lines=>{const c=document.createElement('canvas');c.width=1600;c.height=900;const x=c.getContext('2d');x.fillStyle='white';x.fillRect(0,0,c.width,c.height);x.fillStyle='black';x.font='40px Arial';lines.forEach((l,i)=>x.fillText(l,80,100+i*85));return c.toDataURL('image/png').split(',')[1];},lines),'base64');
 const wait=async id=>{await page.waitForFunction(id=>{const panel=[...document.querySelectorAll('.driver-document-ocr')].find(node=>node.dataset.inputId===id||String(node.dataset.extraInputIds||'').split(',').includes(id));return panel&&/details found|No reliable|could not|Could not|too long/.test(panel.querySelector('[data-ocr-status]').textContent);},id,{timeout:150000});const status=await page.locator(`.driver-document-ocr[data-input-id="${id}"], .driver-document-ocr[data-extra-input-ids~="${id}"]`).locator('[data-ocr-status]').textContent();console.log(id+': '+status);};
 await page.locator('#license').setInputFiles({name:'licence.png',mimeType:'image/png',buffer:await image(['DRIVING LICENCE','DL No: GJ0120200012345','Name: AMIT PATEL','Valid Till: 31/12/2030'])});
 await wait('license');
 assert.equal(await page.locator('[name=license_no]').inputValue(),'GJ0120200012345');
 assert.equal(await page.locator('[name=driver_name]').inputValue(),'AMIT PATEL');
 assert.equal(await page.locator('[name=license_expiry_date]').inputValue(),'31/12/2030');
 await page.locator('#aadhaar').setInputFiles({name:'aadhaar.png',mimeType:'image/png',buffer:await image(['Government of India','RAHUL SHARMA','DOB: 01/01/1990','Address: 12 MG Road','Ahmedabad Gujarat 380001','2345 6789 0123'])});
 await wait('aadhaar');
 assert.equal(await page.locator('[name=adher_no]').inputValue(),'2345 6789 0123');
 await page.evaluate(()=>{const input=document.querySelector('#aadhaar_back');const transfer=new DataTransfer();transfer.items.add(new File(['back'],'aadhaar-back.png',{type:'image/png'}));input.files=transfer.files;input.dataset.scannedAadhaarNumber='987654321098';input.dataset.aadhaarSide='back';input.dataset.aadhaarVerified='true';});
 let pairValidation=await page.evaluate(()=>window.validateDriverDocumentPairs(document.querySelector('form')));
 assert.equal(pairValidation.valid,false);
 assert.match(pairValidation.message,/front and back do not match/i);
 await page.locator('#aadhaar_back').evaluate(input=>{input.dataset.scannedAadhaarNumber='234567890123';});
 pairValidation=await page.evaluate(()=>window.validateDriverDocumentPairs(document.querySelector('form')));
 assert.equal(pairValidation.valid,true);
 // Browser OCR confidence can reject a distorted address instead of filling
 // unsafe text. Exact address extraction is covered by the parser test.
 assert.ok(['12 MG Road, Ahmedabad Gujarat 380001', ''].includes(await page.locator('[name=current_address]').inputValue()));
 assert.equal(await page.locator('[name=driver_name]').inputValue(),'RAHUL SHARMA');
 await page.goto(origin);
 await page.evaluate(() => {
   document.querySelector('[name=license_no]').value = 'OLDLICENCE123';
   document.querySelector('[name=license_expiry_date]').value = '01/01/2028';
 });
 await page.locator('#license').setInputFiles({name:'replacement-saved-licence.pdf',mimeType:'application/pdf',buffer:pdf(['DRIVING LICENCE','DL No: MH0120200012345','Name: AMIT PATEL','Valid Till: 31/12/2030'])});
 await wait('license');
 assert.equal(await page.locator('[name=license_no]').inputValue(),'MH0120200012345');
 assert.equal(await page.locator('[name=license_expiry_date]').inputValue(),'31/12/2030');
 await page.goto(origin);
 await page.locator('#license').setInputFiles({name:'licence.pdf',mimeType:'application/pdf',buffer:pdf(['DRIVING LICENCE','DL No: MH0120200012345','Name: AMIT PATEL','Valid Till: 31/12/2030'])});
 await wait('license');
 assert.equal(await page.locator('[name=license_no]').inputValue(),'MH0120200012345');
 assert.equal(await page.locator('[name=license_expiry_date]').inputValue(),'31/12/2030');
 await page.locator('#license').setInputFiles({name:'second-licence.pdf',mimeType:'application/pdf',buffer:pdf(['DRIVING LICENCE','Licence Number: RJ2720240098765','Name: RAHUL SHARMA','Valid Till: 30/11/2032'])});
 await wait('license');
 assert.equal(await page.locator('[name=license_no]').inputValue(),'RJ2720240098765');
 assert.equal(await page.locator('[name=driver_name]').inputValue(),'RAHUL SHARMA');
 assert.equal(await page.locator('[name=license_expiry_date]').inputValue(),'30/11/2032');
 await page.locator('#license_back').setInputFiles({name:'licence-back.pdf',mimeType:'application/pdf',buffer:pdf(['DRIVING LICENCE BACK','ADDRESS: 12 MG ROAD'])});
 await wait('license_back');
 assert.equal(await page.locator('[name=license_no]').inputValue(),'RJ2720240098765');
 assert.equal(await page.locator('[name=license_expiry_date]').inputValue(),'30/11/2032');
 pairValidation=await page.evaluate(()=>window.validateDriverDocumentPairs(document.querySelector('form')));
 assert.equal(pairValidation.valid,false);
 assert.match(pairValidation.message,/document number could not be read/i);
 await page.locator('#license_back').evaluate(input=>{input.dataset.scannedDocumentNumber='RJ2720240098765';});
 pairValidation=await page.evaluate(()=>window.validateDriverDocumentPairs(document.querySelector('form')));
 assert.equal(pairValidation.valid,true);
 await page.locator('#license_back').evaluate(input=>{input.dataset.scannedDocumentNumber='GJ0120200011111';});
 pairValidation=await page.evaluate(()=>window.validateDriverDocumentPairs(document.querySelector('form')));
 assert.equal(pairValidation.valid,false);
 assert.match(pairValidation.message,/front and back do not match/i);
 await page.goto(origin);
 await page.locator('#license').setInputFiles({name:'cancel.png',mimeType:'image/png',buffer:await image(['Name: CANCELLED PERSON','DL No: GJ0120200012345'])});
 await page.locator('[data-document-type=license] [data-ocr-cancel]').click();
 await page.locator('#license').setInputFiles({name:'replacement.pdf',mimeType:'application/pdf',buffer:pdf(['Name: NEW PERSON','DL No: KA0120200012345','Valid Till: 31/12/2030'])});
 await wait('license');
 assert.equal(await page.locator('[name=driver_name]').inputValue(),'NEW PERSON');
 await page.locator('#father').setInputFiles({name:'father.png',mimeType:'image/png',buffer:await image(['Government of India','RAMESH PATEL','DOB: 01/01/1980','Male','3456 7890 1234'])});
 await wait('father');
 assert.equal(await page.locator('[name=father_name]').inputValue(),'RAMESH PATEL');
 assert.equal(await page.locator('[name=father_aadhaar_number]').inputValue(),'3456 7890 1234');
 await page.locator('#mother').setInputFiles({name:'mother.png',mimeType:'image/png',buffer:await image(['Government of India','KIRAN PATEL','Year of Birth 1984','Female','4567 8901 2345'])});
 await wait('mother');
 assert.equal(await page.locator('[name=mother_name]').inputValue(),'KIRAN PATEL');
 assert.equal(await page.locator('[name=mother_aadhaar_number]').inputValue(),'4567 8901 2345');
 await page.locator('#rc').setInputFiles({name:'rc.png',mimeType:'image/png',buffer:await image(['REGN NO GJ01AB1234','REGISTRATION VALID UPTO 31/12/2030'])});
 await wait('rc');
 assert.equal(await page.locator('[name=vehicle_number]').inputValue(),'GJ01AB1234');
 assert.equal(await page.locator('[name=rc_number]').inputValue(),'GJ01AB1234');
 assert.equal(await page.locator('[name=rc_expiry_date]').inputValue(),'31/12/2030');
 await page.evaluate(() => {
   document.querySelector('[name=insurance_number]').value = 'OLDPOLICY123';
   document.querySelector('[name=insurance_expiry_date]').value = '01/01/2028';
 });
 await page.locator('#insurance').setInputFiles({name:'insurance.pdf',mimeType:'application/pdf',buffer:pdf(['POLICY NO 300112345678','POLICY END DATE 30/11/2031'])});
 await wait('insurance');
 assert.equal(await page.locator('[name=insurance_number]').inputValue(),'300112345678');
 assert.equal(await page.locator('[name=insurance_expiry_date]').inputValue(),'30/11/2031');
 await page.locator('#insurance').setInputFiles({name:'replacement-insurance.pdf',mimeType:'application/pdf',buffer:pdf(['POLICY NO US151741','POLICY END DATE 31/12/2032'])});
 await wait('insurance');
 assert.equal(await page.locator('[name=insurance_number]').inputValue(),'US151741');
 assert.equal(await page.locator('[name=insurance_expiry_date]').inputValue(),'31/12/2032');
 assert.deepEqual(errors,[]);
 console.log('Browser OCR image, PDF, existing-value and cancellation checks passed.');
 } finally {await browser.close();server.close();}
})().catch(e=>{console.error(e);server.close();process.exitCode=1;});

