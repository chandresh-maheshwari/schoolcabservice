const {chromium} = require('../../storage/app/ocr-browser-test/node_modules/playwright-core');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../..');
const source = fs.readFileSync(path.join(root, 'public/js/datatables_cherrypik.js'), 'utf8');
const start = source.indexOf('    function getColumnNames(');
const end = source.indexOf('    function getDatatableColumns(', start);
const context = {schoolSlug: ''};
vm.createContext(context);
vm.runInContext(source.slice(start, end), context);
const tables = [...new Set([...source.slice(start, end).matchAll(/tableId == "([^"]+)"/g)].map(match => match[1]))]
    .filter(table => !process.env.SORT_TABLE || table === process.env.SORT_TABLE);
(async () => {
    const browser = await chromium.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true});
    try {
        const page = await browser.newPage({ignoreHTTPSErrors: true});
        await page.setContent('<meta name="school-slug" content=""><div id="fixture"></div>');
        await page.addScriptTag({path: path.join(root, 'public/js/jquery-3.2.1.min.js')});
        const library = path.join(root, 'storage/app/ocr-browser-test/datatables-1.11.5.min.js');
        await page.addScriptTag(fs.existsSync(library) ? {path: library}
            : {url: 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js'});
        await page.addScriptTag({path: path.join(root, 'public/js/datatables_cherrypik.js')});
        await page.evaluate(() => {
            window.lastRequest = {};
            window.requestCount = 0;
            $.ajax = options => {
                window.requestCount++;
                window.lastRequest = Object.fromEntries(options.data.map(item => [item.name, item.value]));
                options.success({draw: Number(window.lastRequest.sEcho), recordsTotal: 0, recordsFiltered: 0, data: []});
                return {};
            };
        });
        let cases = 0;
        for (const slug of ['', 'school-example']) {
            context.schoolSlug = slug;
            await page.locator('meta[name=school-slug]').evaluate((meta, value) => meta.content = value, slug);
            for (const table of tables) {
                const columns = context.getColumnNames(table, 'example');
                await page.evaluate(({table, columns, multiDelete}) => {
                    document.getElementById('fixture').innerHTML = `<table id="${table.slice(1)}"><thead><tr>${columns.map(column => `<th>${column.mDataProp}</th>`).join('')}</tr></thead><tbody></tbody></table>`;
                    DatatableRenderFunction(table, '/test-list', 'POST', '', true, null, null, true, true, true, multiDelete, 'example', 10);
                }, {table, columns: Array.from(columns), multiDelete: process.env.CHECK_SELECT_ALL === '1'});
                for (let index = 0; index < columns.length; index++) {
                    if (columns[index].mDataProp === 'Actions') continue;
                    await page.evaluate(({table, index}) => $(table).DataTable().order([index, 'desc']).draw(), {table, index});
                    for (const direction of ['asc', 'desc']) {
                        await page.locator(table + ' th').nth(index).click();
                        await page.waitForFunction(({index, direction}) =>
                            Number(window.lastRequest.iSortCol_0) === index && window.lastRequest.sSortDir_0 === direction,
                            {index, direction});
                        const request = await page.evaluate(() => window.lastRequest);
                        assert.equal(request.sSortDir_0, direction, table + '/' + index);
                        assert.equal(Number(request.iSortCol_0), index);
                        assert.equal(request['mDataProp_' + index], columns[index].mDataProp);
                        cases++;
                    }
                }
                if (process.env.CHECK_SELECT_ALL === '1') {
                    const before = await page.evaluate(() => window.requestCount);
                    await page.locator(table + ' .select-all-checkbox').click();
                    await page.waitForTimeout(60);
                    assert.equal(await page.evaluate(() => window.requestCount), before, 'Select-all must not sort the table');
                }
                await page.evaluate(table => $(table).DataTable().destroy(), table);
            }
        }
        console.log(`PASS: ${cases} real DataTables ASC/DESC header clicks across ${tables.length} admin/school tables`);
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
