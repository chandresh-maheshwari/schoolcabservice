const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(require.resolve('../../public/js/datatables_cherrypik.js'), 'utf8');
const start = source.indexOf('    function getDatatableColumns(');
const code = source.slice(start, source.indexOf("    $(document).on('click', '#deleteuser'", start));
const context = {
    canModuleAction: action => action === 'update', canRoute: () => false,
    panelBase: '/admin', deleteRoute: 'example', numberOfActivePost: 10,
    schoolSlug: '', isSchoolPanel: false, showPackageStatusToggle: true,
    escapeHtml: String, escapeJsString: String,
};
vm.createContext(context);
vm.runInContext(code, context);
const tables = [...new Set([...code.matchAll(/tableId == "([^"]+)"/g)].map(match => match[1]))];
let tested = 0;
for (const slug of ['', 'school-example']) {
    context.schoolSlug = slug;
    context.panelBase = slug ? '/school-example' : '/admin';
    context.showPackageStatusToggle = !slug;
    for (const table of tables) {
        for (const column of context.getDatatableColumns(table) || []) {
            if (!column.render || !column.render.toString().includes('Number(row.status) === 1')) continue;
            for (const status of [0, '0', 1, '1', null]) {
                const html = column.render(null, 'display', {id: 123, status}, {});
                const toggles = [...html.matchAll(/<input[^>]*type="checkbox"[^>]*>/g)]
                    .filter(match => /toggleData|toggleUserStatus/.test(match[0]));
                for (const toggle of toggles) {
                    assert.equal(/\schecked(?:\s|>)/.test(toggle[0]), Number(status) === 1, table + '/' + slug + '/' + status);
                    tested++;
                }
            }
        }
    }
}
assert.ok(tested > 100);
console.log(`PASS: ${tested} status checkbox render cases across admin/school listings`);
