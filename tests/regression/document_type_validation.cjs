const assert = require('node:assert/strict');
const {validateDocument, matchingSideNumber} = require('../../public/js/driver-document-parser');
const documents = {
    license: 'DRIVING LICENCE\nDL No: GJ0120200012345\nName: AMIT PATEL\nDOB: 01/01/1990\nValid Till: 31/12/2030',
    'vehicle-rc': 'CERTIFICATE OF REGISTRATION\nRegistration No: GJ01AB1234\nOwner: AMIT PATEL\nChassis: ABC123\nEngine: XYZ123',
    aadhaar: 'Government of India\nAADHAAR\nAMIT PATEL\nDOB: 01/01/1990\nMale\n2345 6789 0123',
    'vehicle-insurance': 'CERTIFICATE OF INSURANCE\nPolicy Number: 123456789012\nRegistration No: GJ01AB1234\nExpiry: 31/12/2030'
};
assert.equal(validateDocument(documents['vehicle-insurance'] + '\nAadhar No: xxxx xxxx 1234\nDrivers must hold a valid driving licence', 'vehicle-insurance', 'front').valid, true);
assert.equal(validateDocument(documents.aadhaar + '\nPolicy Number: 123456789012', 'vehicle-insurance', 'front').valid, false);
for (const product of ['1 Year Liability Only', '2W 1 Year Liability Only', 'Comprehensive']) {
    const text = `CERTIFICATE OF INSURANCE\nPolicy No: 12345678901234567890 ${product} from 04/04/2026 to Midnight of 03/04/2027\nAadhar: xxxx xxxx 1234`;
    const parsed = require('../../public/js/driver-document-parser').parse(text, 'vehicle-insurance');
    assert.equal(parsed.insurance_number, '12345678901234567890');
    assert.equal(validateDocument(text, 'vehicle-insurance', 'front').valid, true);
}
for (const [actual, text] of Object.entries(documents)) {
    for (const expected of Object.keys(documents)) {
        assert.equal(validateDocument(text, expected, 'front').valid, actual === expected, `${actual} uploaded as ${expected}`);
    }
}
assert.equal(validateDocument(documents.license, 'license', 'back').valid, false);
assert.equal(validateDocument(documents.license + '\nCOV: LMV', 'license', 'back').valid, false);
assert.equal(validateDocument(documents['vehicle-rc'], 'vehicle-rc', 'back').valid, false);
const rcBack = 'CERTIFICATE OF REGISTRATION\nRegistration No: GJ01AB1234\nChassis: ABC123\nEngine: XYZ123\nSeating: 7';
assert.equal(validateDocument(rcBack, 'vehicle-rc', 'back').valid, true);
for (const [actual, text] of Object.entries(documents)) {
    for (const expected of ['aadhaar', 'license', 'vehicle-rc']) {
        if (actual !== expected) assert.equal(validateDocument(text, expected, 'back').valid, false, `${actual} in ${expected} back`);
    }
}
assert.equal(validateDocument(documents.aadhaar, 'aadhaar', 'back').valid, false);
assert.equal(validateDocument('Address: Ahmedabad Gujarat 380001\n2345 6789 0123', 'aadhaar', 'back').valid, false);
assert.equal(validateDocument('UIDAI\nAddress: Ahmedabad Gujarat 380001\n2345 6789 0123', 'aadhaar', 'back').valid, true);
assert.equal(validateDocument('DRIVING LICENCE\nDL No: GJ0120200012345\nCOV: LMV', 'license', 'back').valid, true);
for (const type of Object.keys(documents)) {
    assert.equal(validateDocument('Name: AMIT PATEL\nValid Till: 31/12/2030', type, 'front').valid, false);
    assert.equal(validateDocument('', type, 'back').valid, false);
}
console.log('Document type matrix, wrong sides, unidentified documents and valid backs passed');
assert.equal(validateDocument(documents['vehicle-rc'] + '\nKeep insurance current', 'vehicle-rc', 'front').valid, true);
assert.equal(validateDocument(documents.license + '\nCarry insurance documents', 'license', 'front').valid, true);
assert.equal(matchingSideNumber('front', 'GJ01AB1234', 'GJ01XY5678', true, ''), false, 'Replacement front must match the selected back');
assert.equal(matchingSideNumber('front', 'GJ01AB1234', 'GJ01XY5678', false, ''), true, 'Removed back metadata must be ignored');
assert.equal(matchingSideNumber('back', 'GJ01AB1234', 'GJ01XY5678', true, ''), false, 'Wrong back still fails');
assert.equal(matchingSideNumber('back', 'GJ01AB1234', 'GJ01AB1234', true, ''), true);
assert.equal(matchingSideNumber('back', 'GJ01AB1234', '', false, 'GJ01XY5678'), false, 'Single replacement must match the saved document');
console.log('Mismatches blocked for front and back replacements');
for (const heading of ['Govt. of India', 'भारत सरकार', 'ભારત સરકાર']) {
    assert.equal(validateDocument(documents.aadhaar.replace('Government of India\nAADHAAR', heading), 'aadhaar', 'front').valid, true, heading);
}
const ambiguousText = documents.aadhaar + '\n3456 7890 1234';
const {parse} = require('../../public/js/driver-document-parser');
const resolved = parse(ambiguousText, 'aadhaar');
assert.equal(resolved.adher_no, '');
resolved.adher_no = '2345 6789 0123';
assert.equal(validateDocument(ambiguousText, 'aadhaar', 'front', resolved).valid, true);
assert.equal(validateDocument(ambiguousText, 'aadhaar', 'back', resolved).valid, false);
console.log('Regional Aadhaar headings and focused OCR number resolution passed');
const {repeatedAadhaarNumber} = require('../../public/js/driver-document-parser');
assert.equal(repeatedAadhaarNumber('2345 6789 0123\n2345 6789 0123\n2345 6789 0123\n2345 6789 0128\nVID: 3456 7890 1234 5678'), '2345 6789 0123');
assert.equal(repeatedAadhaarNumber('2345 6789 0123\n2345 6789 0128'), '');
assert.equal(repeatedAadhaarNumber('2345 6789 0123\n2345 6789 0123\n2345 6789 0128\n2345 6789 0128'), '');
assert.equal(repeatedAadhaarNumber('VID: 3456 7890 1234\nVID: 3456 7890 1234\nVID: 3456 7890 1234'), '');
console.log('Repeated OCR number consensus rejects ties and VID lines');
const smartCardBack = 'GJ0120200012345\nAadhaar No.\nLMV\nMCWG\nPermanent Address\nHolder\'s\nSignature\nLicencing Authority';
const issuingAuthorityBack = 'RJ0120230012345\nLMV\nMCWG\nPermanent Address\nIssuing Authority Sign\nDTO Example';
assert.equal(validateDocument(issuingAuthorityBack, 'license', 'back').valid, true);
assert.equal(validateDocument(issuingAuthorityBack, 'license', 'front').valid, false);
assert.equal(validateDocument('RJ0120230012345\nPermanent Address\nIssuing Authority', 'license', 'back').valid, false, 'Authority label alone does not identify a licence back');
assert.equal(validateDocument(smartCardBack, 'license', 'back').valid, true, 'Licence reverse need not repeat driving licence heading; Aadhaar reference is allowed');
assert.equal(validateDocument(smartCardBack, 'aadhaar', 'back').valid, false);
assert.equal(validateDocument(smartCardBack, 'license', 'front').valid, false);
assert.equal(validateDocument('GJ0120200012345\nAddress', 'license', 'back').valid, false, 'Number and address alone do not establish licence identity');
console.log('Smart-card licence reverse and Aadhaar-reference classification passed');
const fadedHeading = documents.aadhaar.replace('Government of India\nAADHAAR', 'GOVERNMENT OF iva.');
assert.equal(validateDocument(fadedHeading, 'aadhaar', 'front').valid, true);
assert.equal(validateDocument(fadedHeading, 'aadhaar', 'back').valid, false);
assert.equal(validateDocument(fadedHeading.replace('Male', ''), 'aadhaar', 'front').valid, false, 'Faded heading requires corroborating gender');
assert.equal(validateDocument(fadedHeading.replace('DOB: 01/01/1990', ''), 'aadhaar', 'front').valid, false, 'Faded heading requires corroborating DOB');
assert.equal(validateDocument(fadedHeading + '\nDRIVING LICENCE\nDL No: GJ0120200012345', 'aadhaar', 'front').valid, false);
assert.equal(validateDocument(fadedHeading.replace('iva', 'Canada'), 'aadhaar', 'front').valid, false);
console.log('Faded Indian heading requires number, DOB, gender and correct side; conflicting document types stay rejected');
const wornBack = 'GESEEentncatomAuthorty of india\nAddress: House 12, Ahmedabad, Gujarat 382210\n2345 6789 0123';
assert.equal(validateDocument(wornBack,'aadhaar','back').valid,true);
assert.equal(validateDocument(wornBack,'aadhaar','front').valid,false);
assert.equal(validateDocument(wornBack.replace('382210',''),'aadhaar','back').valid,false);
assert.equal(validateDocument(wornBack.replace('GESEEentncatomAuthorty of india',''),'aadhaar','back').valid,false);
assert.equal(validateDocument(wornBack + '\nDRIVING LICENCE\nDL No: GJ0120200012345','aadhaar','back').valid,false);
console.log('Partially readable Aadhaar back issuer requires address/pincode and number; wrong side and competing documents rejected');
