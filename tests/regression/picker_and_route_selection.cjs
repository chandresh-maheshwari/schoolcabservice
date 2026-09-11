const fs = require('fs');
const vm = require('vm');
const path = require('path');
const root = path.resolve(__dirname, '../..');
function extract(source, name, next) {
    const start = source.indexOf(`function ${name}(`);
    const end = source.indexOf(`function ${next}(`, start);
    if (start < 0 || end < 0) throw new Error(`Function missing: ${name}`);
    return source.slice(start, end);
}
function check(ok, message) { if (!ok) throw new Error(message); }
const layout = fs.readFileSync(path.join(root, 'resources/views/admin_layout/index.blade.php'), 'utf8');
const pickerSource = extract(layout, 'positionDatepickerNearTrigger', 'isFutureOnlyDateField');
for (const [height, width, top, above] of [[768, 1200, 660, true], [768, 1200, 80, false], [300, 240, 220, true]]) {
    const css = {};
    const trigger = { length: 1, 0: { getBoundingClientRect: () => ({top, bottom: top + 30, right: width - 10}) } };
    const input = { closest: () => ({find: () => trigger}), is: () => above };
    const widget = { length: 1, css: values => Object.assign(css, values),
        outerWidth: () => Math.min(280, parseFloat(css.maxWidth)),
        outerHeight: () => Math.min(320, parseFloat(css.maxHeight)) };
    const sandbox = { window: {innerHeight: height}, document: {documentElement: {clientWidth: width}},
        setTimeout: callback => callback(), $: () => input };
    vm.runInNewContext(pickerSource, sandbox);
    sandbox.positionDatepickerNearTrigger({}, {dpDiv: widget});
    check(css.position === 'fixed', 'Picker must escape form scroll clipping');
    check(parseFloat(css.top) >= 8 && parseFloat(css.top) + widget.outerHeight() <= height - 8, 'Picker clipped vertically');
    check(parseFloat(css.left) >= 8 && parseFloat(css.left) + widget.outerWidth() <= width - 8, 'Picker clipped horizontally');
    if (above && height > 500) check(parseFloat(css.top) + widget.outerHeight() < top, 'Joining picker must open above');
}
const routeSource = fs.readFileSync(path.join(root, 'resources/views/routes/partials/form.blade.php'), 'utf8');
function select() { return {options: [], value: '', set innerHTML(value) {this.options = [];}, appendChild(option) {this.options.push(option);}}; }
const fields = {driver_id: select(), bus_id: select()};
const sandbox = {
    window: {routeIsEditMode: true},
    document: {getElementById: id => fields[id], createElement: () => ({dataset: {}})},
    destroyNiceSelect: () => {}, syncNiceSelect: () => {}, cloneOption: option => ({...option}),
    initialDriverId: '4', initialVehicleId: '21', initialSchoolId: '1',
    cachedDriverOptions: [{value: '4', dataset: {schoolId: '0'}}],
    cachedVehicleOptions: [{value: '21', textContent: 'Saved vehicle', dataset: {availabilityStatus: 'emergency'}}],
    getLinkedVehicleIdForSelectedDriver: () => ''
};
vm.runInNewContext(extract(routeSource, 'renderDriverOptionsBySchool', 'cloneOption')
    + extract(routeSource, 'renderVehicleOptionsFromList', 'getFallbackVehicleOptionsForDriver'), sandbox);
sandbox.renderDriverOptionsBySchool('1', '4');
check(fields.driver_id.value === '4' && !fields.driver_id.disabled, 'Saved driver lost after school removal');
sandbox.renderVehicleOptionsFromList([], '21', '4');
check(fields.bus_id.value === '21' && !fields.bus_id.disabled, 'Saved vehicle lost when availability lookup excludes it');
sandbox.renderDriverOptionsBySchool('2', '');
check(fields.driver_id.value === '', 'Changing school must not silently retain unrelated driver');
sandbox.renderVehicleOptionsFromList([], '', '5');
check(fields.bus_id.value === '', 'Changing driver must not retain previous route vehicle');
console.log('PASS: picker placement and viewport bounds; route saved selections and explicit selection changes');
