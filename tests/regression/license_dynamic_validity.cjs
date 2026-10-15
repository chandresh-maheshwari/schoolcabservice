const assert = require('node:assert/strict');
const {parse, licenseValidityRegions} = require('../../public/js/driver-document-parser');
const word = (text,x,y,width=150,height=25) => `5\t1\t1\t1\t1\t1\t${x}\t${y}\t${width}\t${height}\t90\t${text}`;
for (const [x,y] of [[100,100],[600,250],[40,900]]) {
    const tsv = ['header', word('DOB',20,20), word('01/01/1990',20,55), word('Validitv',x,y), word('14/08/2037',x,y+35)].join('\n');
    assert.deepEqual(licenseValidityRegions(tsv), [{x,y:y+35,width:150,height:25,labelled:true}]);
}
for (const [label,expiry] of [['Validity','14/08/2037'], ['Validitv','30/11/2046'], ['Expiry','21/03/2043']]) {
    const result = parse(`DRIVING LICENCE\nDL No: MH0120200012345\nName: TEST PERSON\nDOB: 01/01/1990\n${label}\n${expiry}`, 'license');
    assert.equal(result.license_expiry_date, expiry);
}
assert.equal(parse('DRIVING LICENCE\nDOB: 01/01/1990\nDate of First Issue: 06/09/2021', 'license').license_expiry_date, '');
assert.equal(licenseValidityRegions(['header',word('06/09/2021',200,300)].join('\n'))[0].labelled, false);
console.log('Expiry labels, changing dates and moved layouts passed; DOB/issue dates are not expiry');
