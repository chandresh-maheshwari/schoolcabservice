const {chromium} = require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const fs = require('node:fs');
const assert = require('node:assert/strict');
const source = fs.readFileSync('resources/views/admin_layout/admin_home.blade.php', 'utf8');
const start = source.indexOf('            const setupSortableContainer =');
const setup = source.slice(start, source.indexOf('            const setupSwapContainer =', start));
(async () => {
    const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe', headless:true});
    try {
        const page = await browser.newPage();
        page.on('pageerror', error => { throw error; });
        for (const width of [1400, 850, 420]) {
            await page.setViewportSize({width,height:1600});
            await page.setContent(`<style>.grid{display:grid;grid-template-columns:repeat(${width>1000?4:width>500?2:1},1fr);gap:24px}.item{height:110px;background:#eee}.dragging{opacity:.45}</style><div class="grid" data-save-url="/save">${Array.from({length:8},(_,i)=>`<div class="item" data-card-key="${i}">Card ${i}<button>Open</button></div>`).join('')}</div>`);
            await page.addScriptTag({content:`(()=>{let suppressClickUntil=0;const csrfToken='test';window.saves=[];window.fetch=async(u,o)=>{saves.push(JSON.parse(o.body));return {ok:true}};${setup};setupSortableContainer(document.querySelector('.grid'),{itemSelector:'.item',keyAttribute:'cardKey',section:'cards',dragAnywhere:true,preferPointer:true,cancelSelector:'button'});})();`});
            const first = await page.locator('.item').first().boundingBox();
            const last = await page.locator('.item').last().boundingBox();
            await page.mouse.move(first.x+20,first.y+60);
            await page.mouse.down();
            await page.mouse.move(last.x+last.width/2,last.y+last.height/2,{steps:30});
            assert.equal(await page.locator('[aria-hidden="true"]').count(),1,'Preview must follow pointer');
            assert.equal(await page.locator('.grid>.item').last().getAttribute('data-card-key'),'0','Cards make space before drop');
            const hoveringOrder = await page.locator('.grid>.item').evaluateAll(items=>items.map(item=>item.dataset.cardKey));
            for (let i=0;i<25;i++) await page.mouse.move(last.x+last.width/2,last.y+last.height/2);
            assert.deepEqual(await page.locator('.grid>.item').evaluateAll(items=>items.map(item=>item.dataset.cardKey)),hoveringOrder,'Stationary pointer does not cause dancing');
            await page.mouse.up();
            await page.waitForTimeout(350);
            assert.equal(await page.locator('.grid>.item').last().getAttribute('data-card-key'),'0');
            assert.equal(await page.locator('[aria-hidden="true"]').count(),0,'Preview removed');
            assert.equal(await page.evaluate(()=>saves.length),1,'Save exactly once');
            const before = await page.locator('.grid').innerHTML();
            await page.locator('.grid button').first().click();
            assert.equal(await page.locator('.grid').innerHTML(),before,'Buttons do not reorder cards');
            const orderBeforeCancel = await page.locator('.grid>.item').evaluateAll(items=>items.map(item=>item.dataset.cardKey));
            const cancelStart = await page.locator('.grid>.item').first().boundingBox();
            const cancelEnd = await page.locator('.grid>.item').last().boundingBox();
            await page.mouse.move(cancelStart.x+20,cancelStart.y+60);
            await page.mouse.down();
            await page.mouse.move(cancelEnd.x+cancelEnd.width/2,cancelEnd.y+cancelEnd.height/2,{steps:20});
            await page.locator('.grid').evaluate(grid=>grid.dispatchEvent(new PointerEvent('pointercancel',{pointerId:1,bubbles:true})));
            await page.mouse.up();
            await page.waitForTimeout(300);
            assert.deepEqual(await page.locator('.grid>.item').evaluateAll(items=>items.map(item=>item.dataset.cardKey)),orderBeforeCancel,'Cancelled drag restores order');
            assert.equal(await page.evaluate(()=>saves.length),1,'Cancelled drag does not save');
        }
        console.log('PASS: live animated reorder, stationary pointer stability, cancel restores order, preview cleanup, save once and buttons at desktop/tablet/mobile widths');
    } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exit(1)});

