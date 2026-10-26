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
for (const offset of [0, 300]) {
    const layout = ['header', word('Issue',100+offset,100,80,20),
        word('29/11/2023',60+offset,130,140,25),
        word('17/09/2001',60+offset,230,140,25),
        word('16/09/5041',300+offset,120,150,35)].join('\n');
    assert.deepEqual(licenseValidityRegions(layout), [{x:300+offset,y:120,width:150,height:35,labelled:true,afterYear:2023}], 'Obscured validity label: locate adjacent date for two independent numeric reads');
    const missingBirth = layout.split('\n').filter(line=>!line.includes('17/09/2001')).join('\n');
    assert.ok(licenseValidityRegions(missingBirth).every(region=>!region.labelled), 'Unlabelled date without birth/issue layout remains unverified');
    const ambiguous = layout+'\n'+word('20/10/2042',500+offset,120,150,35);
    assert.ok(licenseValidityRegions(ambiguous).every(region=>!region.labelled), 'Two candidate validity dates remain unverified');
}
console.log('Expiry labels, changing dates and moved layouts passed; DOB/issue dates are not expiry');
