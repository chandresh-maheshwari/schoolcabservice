const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

for (const mode of ['create', 'edit']) {
    const source = fs.readFileSync(path.resolve(__dirname, `../../resources/views/parent/${mode}.blade.php`), 'utf8');
    const start = mode === 'create' ? source.indexOf('            let parentCityRequestVersion') : source.indexOf('            function normalizeCityValue');
    const end = mode === 'create' ? source.indexOf("            $('input[name=\"existing_registered_parent\"]')", start) : source.indexOf('\n        });', start);
    const requests = [];
    const city = {dataset: {ocrPendingCity: 'Pali'}, innerHTML: ''};
    let state = 'Rajasthan', handler;
    const control = {
        on(event, callback) { handler = callback; }, val() { return state; },
        html(value) { city.innerHTML = value; return this; }, empty() { city.innerHTML = ''; return this; },
        append(value) { city.innerHTML += value; return this; }, prop() { return this; }
    };
    const jquery = () => control;
    jquery.ajax = options => requests.push(options);
    const context = {$: jquery, document: {getElementById: () => city}, console,
        patchParentSpecialState() {}, getParentDraftState: () => ({}),
        syncCitySelection(value) { city.innerHTML += value; },
        isParentCityLoading: false, setParentSubmitState() {}};
    vm.createContext(context);
    vm.runInContext(source.slice(start, end), context);
    handler.call({});
    state = 'Gujarat'; city.dataset.ocrPendingCity = 'Ahmedabad';
    handler.call({});
    requests[1].success(['Ahmedabad']);
    const latest = city.innerHTML;
    assert.match(latest, /Ahmedabad/);
    requests[0].success(['Pali']);
    assert.equal(city.innerHTML, latest, `${mode}: older response must not overwrite the city`);
    requests[0].error({}, 'timeout');
    assert.equal(city.innerHTML, latest, `${mode}: older error must not clear the city`);
}
