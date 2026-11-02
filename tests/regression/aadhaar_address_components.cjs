const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const parser=require('../../public/js/driver-document-parser.js');
const source=fs.readFileSync(require.resolve('../../public/js/driver-document-ocr.js'),'utf8');
const context={DriverDocumentParser:parser};vm.createContext(context);
vm.runInContext(source.slice(source.indexOf('    function readableAadhaarValue('),source.indexOf('    function init(panel)')),context);
const readable=context.readableAadhaarValue;
const reading=(text,confidence=95)=>({text,confidence});
assert.equal(readable('Ahmedabad',[reading('Ahmadabad')],'city'),true,'Known printed city spelling must match the canonical dropdown option');
assert.equal(readable('Ahmedabad',[reading('Ahmadabad')],'driver_name'),false,'City aliases must not affect owner-name checks');
assert.equal(readable('Ahmedabad',[reading('Ahmadabd')],'city'),false,'Do not guess arbitrary city spellings');
assert.equal(readable('Ahmedabad',[reading('Gujarat 380001')],'city'),false,'Pin alone cannot establish city evidence');
assert.equal(readable('S/O TEST PERSON, 12 Lake Road',[reading('S/O TEST PERSON'),reading('12 Lake Road')],'address_1'),true,'Separate printed rows can corroborate address components');
assert.equal(readable('TEST PERSON, Unknown Road',[reading('TEST PERSON'),reading('12 Lake Road')],'address_1'),false,'Every component must be observed');
assert.equal(readable('12 Lake Road',[reading('12 Lake Road',45)],'address_component'),false,'A single weak reading must not auto-fill');
assert.equal(readable('12 Lake Road',[reading('12 Lake Road',45),reading('12 Lake Road',45)],'address_component'),true,'Repeated matching address evidence can corroborate a weak pass');
assert.equal(readable('TEST PERSON',[reading('TEST PERSON',45),reading('TEST PERSON',45)],'father_name'),false,'Address corroboration must not weaken names');
assert.equal(readable('Gujarat - 380001',[reading('Gujarat'),reading('380001')],'address_component'),true,'State and pin may be printed in separate copies on the same card');
assert.equal(readable('Gujarat - 380001',[reading('Gujarat')],'address_component'),false);
for(const value of ['House 12 Lake Road Central Area Ahmedabad Gujarat 380001','House 12, Lake Road, Area 3, Ahmedabad, Gujarat 380001']){
    const parsed=parser.parse('Address: '+value+'\n2345 6789 0123','aadhaar');
    assert.ok(parsed.address_1&&parsed.address_2,'A sufficiently detailed address must populate both address fields');
    const joined=parsed.address_1+(value.includes(',')?', ':' ')+parsed.address_2;
    assert.equal(joined,parsed.current_address,'Splitting must preserve the complete address');
}
console.log('City aliases, independently read address rows, confidence safeguards and two-field address splitting passed');

assert.equal(context.reviewableAddress('12 Lake Road CENTRAL LOCALITY',[reading('12 Lake Road')],['CENTRAL','LOCALITY']),true);
assert.equal(context.reviewableAddress('12 Missing Road CENTRAL LOCALITY',[reading('12 Lake Road')],['CENTRAL','LOCALITY']),false);
assert.equal(context.reviewableAddress('12 Lake Road AAAAA BBBBB CCCCC',[reading('12 Lake Road')],['AAAAA','BBBBB','CCCCC']),false);
