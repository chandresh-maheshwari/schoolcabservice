const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync(require.resolve('../../public/js/driver-document-ocr.js'), 'utf8');
const showValues = source.slice(source.indexOf('        function showValues('), source.indexOf('        function start()'));
const file = {};
const input = {files: [file], dataset: {}, dispatchEvent() {}};
const field = {value: 'Saved address', type: 'text', tagName: 'INPUT', classList: {add() {}, remove() {}}, dispatchEvent() {}};
const context = {
    input, activeInput: input, type: 'aadhaar', generation: 1,
    fields: {address_1: field}, labels: {address_1: 'Address 1'},
    autoFilled: {}, autoFilledAtEdit: {}, autoFilledByInput: {}, edits: {address_1: 0},
    form: {}, results: {replaceChildren() {}, appendChild() {}}, status: {},
    document: {createElement: () => ({appendChild() {}, addEventListener() {}})},
    window: {}, Event: class {}, CustomEvent: class {}, message() {}
};
vm.createContext(context);
vm.runInContext(showValues, context);
const parsed = address => ({address_1: address, adher_no: '2345 6789 0123', aadhaar_side: 'front', ambiguous: []});
context.showValues(parsed('First address'), {address_1: 'Saved address'}, {address_1: 0}, file, 1);
assert.equal(field.value, 'First address', 'Edit form saved address must be replaced');
context.edits.address_1 = 2;
context.showValues(parsed('Second address'), {address_1: 'First address'}, {address_1: 2}, file, 1);
assert.equal(field.value, 'Second address', 'Subsequent scan must replace previous address even after earlier edits');
field.value = 'Edited while reading';
context.edits.address_1 = 3;
context.showValues(parsed('Third address'), {address_1: 'Second address'}, {address_1: 2}, file, 1);
assert.equal(field.value, 'Edited while reading', 'Edits during OCR must be preserved');
context.showValues(parsed('Another panel address'), {address_1: field.value}, {address_1: 3}, file, 1);
assert.equal(field.value, 'Another panel address', 'Shared address must update from another scan');
for (const role of ['father', 'mother']) {
    const key = `${role}_name`;
    const name = {...field, value: 'Previous person'};
    context.fields = {[key]: name, [`${role}_aadhaar_number`]: {...field, value: ''}};
    context.labels = {[key]: 'Name'};
    context.edits = {[key]: 0};
    context.showValues({...parsed(''), adher_no: '', [key]: 'New person',
        gender: role === 'mother' ? 'Female' : 'Male'}, {[key]: 'Previous person'}, {[key]: 0}, file, 1);
    assert.equal(name.value, 'New person', `${role}: readable name must update even if number OCR fails`);
    assert.equal(input.dataset.aadhaarVerified, undefined, 'Missing number must still block verified submission');
}

const timers = [];
const city = {value: 'Saved city', dataset: {}, type: 'select-one', tagName: 'SELECT', options: [{value: ''}],
    classList: field.classList, add(option) { this.options.push(option); },
    dispatchEvent(event) { if (event.type === 'input') context.edits.city++; }};
const state = {value: 'Old state', tagName: 'INPUT', classList: field.classList,
    dispatchEvent(event) { if (event.type === 'change') city.value = ''; }};
context.Event = class { constructor(type) { this.type = type; } };
context.Option = class { constructor(text, value) { this.value = value; } };
context.setTimeout = callback => timers.push(callback);
context.fields = {state, city};
context.labels = {state: 'State', city: 'City'};
context.edits = {state: 0, city: 0};
context.showValues({...parsed(''), state: 'Rajasthan', city: 'Pali'}, {state: 'Old state', city: 'Saved city'}, {state: 0, city: 0}, file, 1);
assert.equal(city.value, 'Pali', 'State loading must not block replacing a saved city');
city.value = '';
timers.shift()();
assert.equal(city.value, 'Pali', 'Delayed restore must preserve the scanned city');
assert.equal(context.autoFilledAtEdit.city, context.edits.city);

let alertMessage = '', clearedInput;
context.window.alert = message => { alertMessage = message; };
context.clearScannedPreview = clear => { if (clear) { clearedInput = context.activeInput; context.activeInput.value = ''; } };
context.retry = {};
context.activeInput = input;
input.value = 'wrong-back.png';
context.showValues({...parsed('Wrong address'), aadhaar_side: 'back'}, {}, {}, file, 1);
assert.match(alertMessage, /Only the Aadhaar front side/);
assert.equal(input.value, '');
assert.equal(input.dataset.aadhaarVerified, undefined);
const backInput = {value: 'wrong-front.png', dataset: {}, files: [file]};
context.activeInput = backInput;
input.value = 'correct-front.png';
context.showValues(parsed('Wrong address'), {}, {}, file, 1);
assert.match(alertMessage, /Only the Aadhaar back side/);
assert.equal(clearedInput, backInput);
assert.equal(backInput.value, '');
assert.equal(input.value, 'correct-front.png', 'Rejecting back must preserve the front upload');
