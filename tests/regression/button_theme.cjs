const fs=require('node:fs');
const path=require('node:path');
const assert=require('node:assert/strict');
const {chromium}=require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const files=dir=>fs.readdirSync(dir,{withFileTypes:true}).flatMap(entry=>entry.isDirectory()?files(path.join(dir,entry.name)):[path.join(dir,entry.name)]);
const actions=[];
for(const file of files('resources/views').filter(file=>file.endsWith('.blade.php'))){
 const source=fs.readFileSync(file,'utf8');
 for(const match of source.matchAll(/<(button|a)\b([^<]*?)>([^<]*)<\/\1>/gi)){
  const label=match[3].trim();
  const required=/^(Submit|Update|Save|Save Changes|Cancel|Apply|Reset)$/i.test(label);
  if(!required&&!/app-(submit|cancel)-button/.test(match[2]))continue;
  const secondary=/^(Cancel|Reset)$/i.test(label);
  assert.ok(match[2].includes(secondary?'app-cancel-button':'app-submit-button'),file+': '+label);
  actions.push({tag:match[1],classes:match[2].match(/class="([^"]*)"/)[1],label,secondary,file,originalId:match[2].match(/\bid="([^"]+)"/)?.[1]||''});
 }
}
(async()=>{
 const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 try{
  const page=await browser.newPage({viewport:{width:1200,height:900}});
  await page.setContent('<style>body{font-family:Arial}.examples{display:flex;gap:12px;flex-wrap:wrap} .support-filter-actions .btn{min-height:48px;border-radius:10px;font-weight:700} #modal .btn{min-width:88px;border-radius:10px}</style><form class="examples support-filter-actions">'+actions.map((action,index)=>`<${action.tag} data-action="${index}" id="${action.originalId}" href="#" type="button" class="${action.classes}" style="background-color:#2C9DD4;color:red">${action.label}</${action.tag}>`).join('')+'</form><div class="swal2-actions"><button class="swal2-confirm swal2-styled" style="background-color:red">OK</button><button class="swal2-deny" style="background-color:purple">Deny</button><button class="swal2-cancel swal2-styled" style="background-color:gray">Cancel</button><button class="swal2-confirm swal2-styled hidden-action" style="display:none">Hidden</button></div><div class="swal-footer"><button class="swal-button swal-button--confirm swal-button--danger">OK</button><button class="swal-button swal-button--cancel">Cancel</button></div><div id="modal"><div class="modal-footer"><button class="btn btn-danger">Delete</button><button class="btn btn-secondary">Cancel</button></div></div>');
  for(const file of ['public/assets/css/adminStyle.css','public/assets/css/cherrypik-custom-css/custom.css','public/assets/css/cherrypik-custom-css/buttons.css'])await page.addStyleTag({path:file});
  await page.waitForTimeout(500);
  const styles=await page.locator('[data-action]').evaluateAll(nodes=>nodes.map(node=>{const s=getComputedStyle(node);return {background:s.backgroundColor,color:s.color,height:node.getBoundingClientRect().height,font:s.fontSize,radius:s.borderRadius}}));
  styles.forEach((style,index)=>assert.deepEqual(style,{background:actions[index].secondary?'rgb(238, 240, 248)':'rgb(45, 51, 107)',color:actions[index].secondary?'rgb(45, 51, 107)':'rgb(255, 255, 255)',height:40,font:'14px',radius:'6px'},actions[index].file+JSON.stringify(style)));
  for(const selector of ['.swal2-confirm:not(.hidden-action)','.swal2-deny','.swal-button--confirm','.modal-footer .btn-danger']){
   assert.equal(await page.locator(selector).evaluate(node=>getComputedStyle(node).backgroundColor),'rgb(45, 51, 107)',selector);
  }
  for(const selector of ['.swal2-cancel','.swal-button--cancel','.modal-footer .btn-secondary']){
   assert.equal(await page.locator(selector).evaluate(node=>getComputedStyle(node).color),'rgb(45, 51, 107)',selector);
  }
  assert.equal(await page.locator('.hidden-action').evaluate(node=>getComputedStyle(node).display),'none','Hidden popup actions remain hidden');
  await page.locator('[data-action="0"]').focus();
  assert.equal(await page.locator('[data-action="0"]').evaluate(node=>getComputedStyle(node).outlineStyle),'solid','Keyboard focus remains visible');
  await page.locator('.swal2-confirm:not(.hidden-action)').evaluate(node=>node.disabled=true);
  assert.equal(await page.locator('.swal2-confirm:not(.hidden-action)').isDisabled(),true,'Disabled action remains disabled');
  await page.setViewportSize({width:390,height:900});
  assert.equal(await page.locator('[data-action="0"]').evaluate(node=>node.getBoundingClientRect().height),40);
  console.log(`PASS: ${actions.length} form actions share sizing/colors; both popup libraries and modal actions themed; hidden/disabled buttons, focus and mobile sizing preserved`);
 }finally{await browser.close();}
})().catch(error=>{console.error(error);process.exit(1)});
