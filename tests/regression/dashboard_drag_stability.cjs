const {chromium} = require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../..');
const source = fs.readFileSync(path.join(root, 'resources/views/admin_layout/admin_home.blade.php'), 'utf8');
const start = source.indexOf('            const setupSortableContainer =');
const setup = source.slice(start, source.indexOf('            const setupSwapContainer =', start));
(async () => {
    const browser = await chromium.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true});
    try {
        const page = await browser.newPage();
        for (const width of [1400, 850, 420]) {
            await page.setViewportSize({width, height: width < 500 ? 1600 : 1000});
            await page.setContent(`<style>
                .grid{display:grid;align-items:start;grid-template-columns:repeat(${width > 1000 ? 4 : width > 500 ? 2 : 1},1fr);gap:24px}
                .card{height:110px;background:#eee;padding:10px}.dashboard-drop-target>.card{outline:2px dashed blue}
                .dragging{opacity:.45}
                </style><div class="grid" data-save-url="/save">${Array.from({length:8},(_,i)=>
                    `<div class="item" data-card-key="card${i}"><div class="card" style="height:${width === 850 && i % 3 === 0 ? 180 : 110}px"><span>Card ${i}</span><a href="#next">Open</a></div></div>`).join('')}</div>`);
            await page.addScriptTag({content: `(() => {
                let suppressClickUntil = 0; const csrfToken='test';
                window.saves=[];window.fetch=async(url,options)=>{window.saves.push(JSON.parse(options.body));return {ok:true}};
                ${setup}
                setupSortableContainer(document.querySelector('.grid'),{itemSelector:'.item', keyAttribute:'cardKey', section:'cards', preferNative:true,dragAnywhere:true,cancelSelector:'a'});
            })();`});
            const results = await page.evaluate(async () => {
                const grid=document.querySelector('.grid'), items=[...grid.children];
                const original=items.map(item=>item.dataset.cardKey);
                const boxes=items.map(item=>item.getBoundingClientRect());
                const dataTransfer=new DataTransfer();
                const source=items[0], target=items[7];
                source.dispatchEvent(new DragEvent('dragstart',{bubbles:true,cancelable:true,dataTransfer}));
                const rect=target.getBoundingClientRect(), x=rect.left+rect.width/2,y=rect.top+rect.height/2;
                for(let i=0;i<100;i++) target.dispatchEvent(new DragEvent('dragover',{bubbles:true,cancelable:true,clientX:x,clientY:y,dataTransfer}));
                const stableOrder=[...grid.children].map(item=>item.dataset.cardKey);
                const stableBoxes=items.every((item,i)=>item.getBoundingClientRect().top===boxes[i].top&&item.getBoundingClientRect().left===boxes[i].left);
                const beforeDropSaves=window.saves.length;
                target.dispatchEvent(new DragEvent('drop',{bubbles:true,cancelable:true,clientX:x,clientY:y,dataTransfer}));
                source.dispatchEvent(new DragEvent('dragend',{bubbles:true,dataTransfer}));
                await new Promise(resolve=>setTimeout(resolve,230));
                const dropped=[...grid.children].map(item=>item.dataset.cardKey);
                const saved=window.saves.slice();
                // Cancelling a drag must neither reorder nor save.
                items[2].dispatchEvent(new DragEvent('dragstart',{bubbles:true,cancelable:true,dataTransfer}));
                grid.dispatchEvent(new DragEvent('dragover',{bubbles:true,cancelable:true,clientX:x,clientY:y,dataTransfer}));
                items[2].dispatchEvent(new DragEvent('dragend',{bubbles:true,dataTransfer}));
                await new Promise(resolve=>setTimeout(resolve,230));
                return {original,stableOrder,stableBoxes,beforeDropSaves,dropped,saved,
                    afterCancel:[...grid.children].map(item=>item.dataset.cardKey),saveCount:window.saves.length,
                    highlights:grid.querySelectorAll('.dashboard-drop-target,.dragging').length};
            });
            assert.deepEqual(results.stableOrder, results.original, 'No dragover reflow at '+width);
            assert.equal(results.stableBoxes,true);
            assert.equal(results.beforeDropSaves,0);
            assert.deepEqual(results.dropped,['card1','card2','card3','card4','card5','card6','card7','card0']);
            assert.equal(results.saved.length,1);
            assert.deepEqual(results.saved[0].order,results.dropped);
            assert.deepEqual(results.afterCancel,results.dropped);
            assert.equal(results.saveCount,1);
            assert.equal(results.highlights,0);
            await page.evaluate(() => {
                window.dragTrace = [];
                for (const name of ['dragstart','dragover','drop','dragend']) document.addEventListener(name,
                    event => window.dragTrace.push([name, event.target.closest('.item')?.dataset.cardKey]));
            });
            await page.locator('.item').last().dragTo(page.locator('.item').first(), {sourcePosition:{x:40,y:40},targetPosition:{x:40,y:40}});
            assert.equal(await page.locator('.item').first().getAttribute('data-card-key'), 'card0', width + ': ' + JSON.stringify(await page.evaluate(()=>window.dragTrace)));
            await page.waitForFunction(() => window.saves.length === 2);
            assert.equal(await page.locator('.dashboard-drop-target,.dragging').count(),0);
        }
        console.log('PASS: stable dashboard slots during 100 repeated dragovers; drop/cancel/save verified at desktop, tablet and mobile widths');
    } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exitCode=1});
