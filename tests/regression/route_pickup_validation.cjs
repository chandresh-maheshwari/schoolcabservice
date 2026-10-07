const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(require.resolve('../../public/js/route-builder.js'), 'utf8');
let popup;
const context = {
    RouteBuilder: function () {},
    window: {Swal: {fire: options => { popup = options.text; }}, FormData: class { get() { return 'value'; } }},
    document: {getElementById: () => null}
};
vm.createContext(context);
vm.runInContext(source.slice(source.indexOf('    RouteBuilder.prototype.validateForm ='), source.indexOf('    RouteBuilder.prototype.submitForm =')), context);
const builder = {pickupEntries: [], form: {querySelectorAll: () => []}, startBindings: {point: {}}, endBindings: {point: {}}};
const validate = () => context.RouteBuilder.prototype.validateForm.call(builder);
assert.equal(validate(), false);
assert.equal(popup, 'Please select at least one pickup point.');
builder.pickupEntries = [{point: null}];
assert.equal(validate(), false);
builder.pickupEntries = [{point: {lat: 23, lng: 72}}];
assert.equal(validate(), true);
delete context.window.Swal;
context.window.alert = message => { popup = message; };
builder.pickupEntries = [];
assert.equal(validate(), false);
assert.equal(popup, 'Please select at least one pickup point.');
