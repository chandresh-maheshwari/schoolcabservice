const assert = require('node:assert/strict');
const {validateDocument} = require('../../public/js/driver-document-parser.js');
// Synthetic number with a valid Verhoeff check digit; no real identity data.
const number = '2345 6789 0124';
const front = (heading, value = number, copies = 3) => `${heading}\nTEST PERSON\nDOB: 01/01/1990\nFEMALE\n${(value + '\n').repeat(copies)}`;
for (const fragment of ['A', 'IA', 'DIA', 'NDIA', 'IND', 'IN']) {
    assert.equal(validateDocument(front(`GOVERNMENT OF ${fragment}`), 'aadhaar', 'front').valid, true, `Faded INDIA fragment ${fragment} should be corroborated`);
}
assert.equal(validateDocument(front('GOVERNMENT OF A', '2345 6789 0123'), 'aadhaar', 'front').valid, false, 'A mistyped number must not support a partial heading');
assert.equal(validateDocument(front('GOVERNMENT OF A', number, 1), 'aadhaar', 'front').valid, false, 'One number reading is insufficient');
assert.equal(validateDocument(front('GOVERNMENT OF A', number, 2), 'aadhaar', 'front').valid, false, 'At least three agreeing number readings are required');
assert.equal(validateDocument(front(''), 'aadhaar', 'front').valid, false, 'Missing issuer is still rejected');
assert.equal(validateDocument(front('GOVERNMENT OF CANADA'), 'aadhaar', 'front').valid, false, 'Other countries must not be accepted');
assert.equal(validateDocument(front('GOVERNMENT OF A').replace('FEMALE', ''), 'aadhaar', 'front').valid, false, 'Gender is required for the partial-heading fallback');
assert.equal(validateDocument(front('GOVERNMENT OF A').replace('DOB: 01/01/1990', ''), 'aadhaar', 'front').valid, false, 'DOB is required for the partial-heading fallback');
assert.equal(validateDocument(front('GOVERNMENT OF A') + '\nDRIVING LICENCE', 'aadhaar', 'front').valid, false, 'A different identity document is still rejected');
assert.equal(validateDocument(front('GOVERNMENT OF A'), 'aadhaar', 'back').valid, false, 'Front cannot be uploaded as back');
for (let index = 0; index < 12; index++) {
    const digits = number.replace(/ /g, '');
    const altered = digits.slice(0, index) + ((Number(digits[index]) + 1) % 10) + digits.slice(index + 1);
    const spaced = altered.match(/.{4}/g).join(' ');
    assert.equal(validateDocument(front('GOVERNMENT OF A', spaced), 'aadhaar', 'front').valid, false, 'Every single-digit change must fail checksum');
}
console.log('Partial issuer accepted only with complete front details and repeated checksum-valid number; negative cases passed');
