// Synthetic documents only. Compare real OCR against git HEAD without submitting forms.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const http = require('node:http');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {chromium} = require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const root = path.resolve(__dirname, '../../public');
const baseline = process.env.TEST_BASELINE_FILE ? fs.readFileSync(process.env.TEST_BASELINE_FILE,'utf8')
    : execFileSync('git',['show','HEAD:public/js/driver-document-ocr.js'],{cwd:path.resolve(root,'..'),encoding:'utf8'});
const cases = [
    {type:'license',map:{license_no:'Number',license_expiry_date:'Expiry'},front:['DRIVING LICENCE','DL No: GJ0120200012345','Name: AMIT PATEL','DOB: 01/01/1990','Valid Till: 31/12/2030'],back:['DRIVING LICENCE','DL No: GJ0120200012345','COV: LMV MCWG',"HOLDER'S SIGNATURE",'LICENSING AUTHORITY']},
    {type:'vehicle-rc',map:{vehicle_number:'Vehicle',rc_number:'Number',rc_expiry_date:'Expiry'},front:['CERTIFICATE OF REGISTRATION','Registration No: GJ01AB1234','Owner Name: AMIT PATEL','Chassis No: ABC123456789','Engine No: XYZ123456789','Registration Valid Upto: 31/12/2030'],back:['Registration No: GJ01AB1234','Chassis No: ABC123456789','Engine No: XYZ123456789','Cubic Capacity: 1197 CC','Seating Capacity: 5','Unladen Weight: 935 KG','Fitness Valid Upto: 31/12/2030']},
    {type:'vehicle-insurance',map:{insurance_number:'Policy',insurance_expiry_date:'Expiry'},front:['CERTIFICATE OF INSURANCE','Policy No: 300112345678','Registration No: GJ01AB1234','Expiry Date: 31/12/2030']}
];
const html = (entry,old) => `<form>${Object.keys(entry.map).map(key=>`<input name="${key}">`).join('')}<input type="file" id="front"><input type="file" id="back"><div class="driver-document-ocr" data-input-id="front" data-extra-input-ids="${entry.back?'back':''}" data-document-type="${entry.type}" data-field-map='${JSON.stringify(entry.map)}' data-vendor-base="/vendor/license-ocr/"><div data-ocr-status></div><div data-ocr-results></div><button type="button" data-ocr-retry></button><button type="button" data-ocr-cancel></button></div></form><script>window.popups=[];window.Swal={fire:o=>window.popups.push(o.text)};</script><script src="/js/driver-document-parser.js"></script><script src="/js/driver-document-ocr.js${old?'?baseline=1':''}"></script>`;
const server = http.createServer((req,res)=>{
    const url = new URL(req.url,'http://localhost');
    if (url.pathname === '/') {res.setHeader('Content-Type','text/html');return res.end(html(cases[Number(url.searchParams.get('case'))],url.searchParams.has('baseline')));}
    const file = path.resolve(root,'.'+url.pathname);
    if (!file.startsWith(root+path.sep)||!fs.existsSync(file)){res.statusCode=404;return res.end();}
    res.setHeader('Content-Type',({'.js':'text/javascript','.mjs':'text/javascript','.wasm':'application/wasm'})[path.extname(file)]||'application/octet-stream');
    if (url.pathname.endsWith('driver-document-ocr.js')&&url.searchParams.has('baseline'))return res.end(baseline);
    if (url.pathname.endsWith('tesseract.min.js'))return res.end(fs.readFileSync(file,'utf8')+`\n;(()=>{const create=Tesseract.createWorker;Tesseract.createWorker=async(...a)=>{const w=await create(...a),read=w.recognize.bind(w);w.recognize=async(...args)=>{window.passes=(window.passes||0)+1;const result=await read(...args);if(${process.env.DEBUG_PERFORMANCE === '1'}){window.metrics=window.metrics||[];window.metrics.push({confidence:result.data.confidence,text:result.data.text,words:String(result.data.tsv||'').split('\\n').map(r=>r.split('\\t')).filter(r=>r[0]==='5'&&r[11]&&Number(r[10])<80).map(r=>[r[11],r[10]])})}return result};return w}})();`);
    fs.createReadStream(file).pipe(res);
});
(async()=>{
    let browser;
    try{
        await new Promise(r=>server.listen(0,'127.0.0.1',r));
        browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
        for(let index=0;index<cases.length;index++){
            const entry=cases[index],snapshots=[];
            if (process.env.DOCUMENT_TYPE && process.env.DOCUMENT_TYPE !== entry.type) continue;
            for(const old of [true,false]){
                const page=await browser.newPage();
                await page.goto(`http://127.0.0.1:${server.address().port}/?case=${index}${old?'&baseline=1':''}`);
                const scans=[];
                for(const side of ['front','back']){
                    if(!entry[side])continue;
                    const buffer=Buffer.from(await page.evaluate(lines=>{const c=document.createElement('canvas');c.width=1600;c.height=1000;const x=c.getContext('2d');x.fillStyle='white';x.fillRect(0,0,c.width,c.height);x.fillStyle='black';x.font='40px Arial';lines.forEach((line,i)=>x.fillText(line,80,100+i*100));return c.toDataURL().split(',')[1]},entry[side]),'base64');
                    await page.evaluate(()=>{window.passes=0});
                    const started=Date.now();
                    await page.locator('#'+side).setInputFiles({name:side+'.png',mimeType:'image/png',buffer});
                    await page.waitForFunction(side=>['verified','invalid'].includes(document.getElementById(side).dataset.documentValidation),side,{timeout:150000});
                    assert.equal(await page.locator('#'+side).getAttribute('data-document-validation'),'verified',`${old?'baseline':'optimized'} ${entry.type} ${side}: ${await page.locator('[data-ocr-status]').textContent()}`);
                    if (process.env.DEBUG_PERFORMANCE === '1') console.log(JSON.stringify({baseline:old,side,reads:await page.evaluate(()=>window.metrics)}));
                    scans.push({side,seconds:Number(((Date.now()-started)/1000).toFixed(2)),passes:await page.evaluate(()=>window.passes),values:await page.locator('form').evaluate(form=>Object.fromEntries([...form.querySelectorAll('input[name]')].map(field=>[field.name,field.value])))});
                }
                assert.equal(await page.evaluate(()=>window.popups.length),0);
                if(entry.back)assert.equal(await page.evaluate(()=>validateDriverDocumentPairs(document.querySelector('form')).valid),true,'Pair must still match');
                snapshots.push(scans);await page.close();
            }
            snapshots[1].forEach((scan,i)=>{
                assert.deepEqual(scan.values,snapshots[0][i].values,`${entry.type} ${scan.side}: field values must stay identical`);
                assert.ok(scan.passes<=snapshots[0][i].passes,`${entry.type} ${scan.side}: must not add passes`);
                console.log(`${entry.type} ${scan.side}: ${snapshots[0][i].seconds}s/${snapshots[0][i].passes} passes -> ${scan.seconds}s/${scan.passes} passes; fields and matching unchanged`);
            });
        }
    }finally{if(browser)await browser.close();await new Promise(r=>server.close(r));}
})().catch(error=>{console.error(error);process.exitCode=1});
