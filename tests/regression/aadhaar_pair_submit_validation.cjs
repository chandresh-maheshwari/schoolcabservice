const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(require.resolve('../../resources/views/admin_layout/index.blade.php'), 'utf8');
assert.ok(!source.includes('rejectMismatchedAadhaarUpload'), 'Replacement uploads must not be rejected against an old paired file');
const front = {files: [{}], dataset: {scannedAadhaarNumber: '234567890123', aadhaarVerified: 'true', aadhaarSide: 'front'}};
const back = {files: [{}], dataset: {scannedAadhaarNumber: '345678901234', aadhaarVerified: 'true', aadhaarSide: 'back'}};
let message = '';
const scope = {querySelectorAll: () => [back]};
const context = {document: scope, window: {
    findFrontDocumentInput: () => front,
    normalizeAadhaarDigits: value => String(value).replace(/\D/g, ''),
    showDocumentValidationMessage: value => { message = value; }
}};
vm.createContext(context);
vm.runInContext(source.slice(source.indexOf('    window.validateAadhaarDocumentPairs ='), source.indexOf('    window.validateMatchingDocumentPairs =')), context);
assert.equal(context.window.validateAadhaarDocumentPairs(scope), false);
assert.match(message, /numbers do not match/);
for (const owner of ['Father', 'Mother', 'Child', 'Driver']) {
    front.id = owner === 'Driver' ? 'adher_card_iamge' : `${owner.toLowerCase()}_adhaar_card_image`;
    assert.equal(context.window.validateAadhaarDocumentPairs(scope), false);
    assert.ok(message.startsWith(`${owner} Aadhaar front and back`), `${owner}: mismatch must identify the affected field`);
}
assert.equal(front.files.length, 1, 'Validation must preserve selected replacement files');
back.files = [];
assert.equal(context.window.validateAadhaarDocumentPairs(scope), true, 'One selected side must not trigger mismatch with old scan metadata');
back.files = [{}];
back.dataset.scannedAadhaarNumber = front.dataset.scannedAadhaarNumber;
assert.equal(context.window.validateAadhaarDocumentPairs(scope), true);
delete front.dataset.aadhaarVerified;
assert.equal(context.window.validateAadhaarDocumentPairs(scope), false);
assert.match(message, /wait for Driver Aadhaar verification/);
console.log('Replacement files preserved; submit still requires verified matching sides');
const handlers = {};
context.document.addEventListener = (name, handler) => { handlers[name] = handler; };
const calls = [];
context.window.validateAadhaarDocumentPairs = form => { calls.push(form); return true; };
vm.runInContext(source.slice(source.indexOf("    document.addEventListener('submit', function (event)"),
    source.indexOf("    document.addEventListener('DOMContentLoaded', function ()", source.indexOf("    document.addEventListener('submit', function (event)"))), context);
const click = button => handlers.click({target: {closest: () => button}});
click({type: 'submit', id: '', form: null});
click({type: 'button', id: 'fatherAdherImageBtn', form: scope});
for (const module of ['parent', 'child', 'driver']) {
    for (const mode of ['create', 'edit']) {
        const view = fs.readFileSync(path.resolve(__dirname, `../../resources/views/${module}/${mode}.blade.php`), 'utf8');
        const uploadButtons = [...view.matchAll(/<button\b[^>]*>/g)].map(match => match[0])
            .filter(tag => /onclick\s*=/.test(tag) && /(?:adhaar|adher|childAdher|father|mother|ImageBtn)/i.test(tag));
        for (const tag of uploadButtons) {
            assert.match(tag, /type="button"/, `${module}/${mode}: uploads must not submit`);
            const id = (tag.match(/id="([^"]+)"/) || [])[1] || '';
            click({type: 'button', id, form: scope});
        }
    }
}
assert.equal(calls.length, 0, 'Popup and upload buttons must not run pair validation');
click({type: 'button', id: 'submitBtn', form: scope});
assert.deepEqual(calls, [scope], 'Submit must validate only its own form');
console.log('Upload/popup clicks ignored; submit scoped to its own form');
