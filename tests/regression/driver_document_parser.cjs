const assert = require('node:assert/strict');
const {parse, date} = require('../../public/js/driver-document-parser');
for (const state of 'GJ MH DL KA TN UP WB RJ KL TS AP PB HR BR OR OD UK UT'.split(' ')) {
 const actual = parse(`DRIVING LICENCE\nDL No: ${state}-01-2020-0012345\nName: AMIT PATEL\nFather Name: OTHER PERSON\nDOB: 01/01/1990\nValid Till: 31-12-2030`, 'license');
 assert.equal(actual.license_no, state + '0120200012345');
 assert.equal(actual.driver_name, 'AMIT PATEL');
 assert.equal(actual.license_expiry_date, '31/12/2030');
}
assert.equal(parse('Name\nAMIT PATEL\nValidity: 01/01/2020 to 01/01/2040', 'license').license_expiry_date, '01/01/2040');
assert.equal(parse('NT Valid: 01/01/2040\nTR Valid: 01/01/2030', 'license').license_expiry_date, '');
assert.equal(parse(`Government of India
CRISTIANO RONALDO
DOB: 01/01/1990
9876 5432 1098`, 'aadhaar').child_name, 'CRISTIANO RONALDO');

const aadhaarDobGender = parse(`Government of India
ALEX STUDENT
DOB: 05/06/2015
Male
2345 6789 0123`, 'aadhaar');
assert.equal(aadhaarDobGender.child_name, 'ALEX STUDENT');
assert.equal(aadhaarDobGender.date_of_birth, '05/06/2015');
assert.equal(aadhaarDobGender.gender, 'Male');

const aadhaarNameWithNoise = parse(`Government of India
K K HUB
DOB: 01/01/1990
Some Random Colony
7002 7542 9745`, 'aadhaar');
assert.equal(aadhaarNameWithNoise.driver_name, 'K K HUB');

const splitAddressAadhaar = parse(`Government of India
RAHUL SHARMA
DOB: 01/01/1990
Address: House 1, Street 2, Area 3, Ahmedabad, Gujarat 380001
2345 6789 0123`, 'aadhaar');
assert.equal(splitAddressAadhaar.address_1, 'House 1, Street 2, Area 3');
assert.equal(splitAddressAadhaar.address_2, 'Ahmedabad, Gujarat 380001');
assert.equal(splitAddressAadhaar.aadhaar_side, 'front');

const backSideAadhaar = parse(`Address: House 1, Street 2, Area 3, Ahmedabad, Gujarat 380001
2345 6789 0123`, 'aadhaar');
assert.equal(backSideAadhaar.aadhaar_side, 'back');

const unreadableAadhaarSide = parse(`Government of India
RAHUL SHARMA
2345 6789 0123`, 'aadhaar');
assert.equal(unreadableAadhaarSide.aadhaar_side, 'unknown');

const insuranceVehicle = parse(`POLICY NO 300112345678
Vehicle Number: GJ-01-AB-1234
Policy End Date 30/11/2031`, 'vehicle-insurance');
assert.equal(insuranceVehicle.document_vehicle_number, 'GJ01AB1234');

const insurancePolicyHolderNoise = parse(`Policy No US151741POLICYHOLDER
Policy End Date 30/11/2031`, 'vehicle-insurance');
assert.equal(insurancePolicyHolderNoise.insurance_number, 'US151741');

const insurancePolicyHolderWithSpace = parse(`Policy Number: US151741 POLICY HOLDER
Policy End Date 30/11/2031`, 'vehicle-insurance');
assert.equal(insurancePolicyHolderWithSpace.insurance_number, 'US151741');

const insuranceHeadingBeforeProvisions = parse(`INSURANCE
PROVISIONS 14 CLAIM PROVISIONS 15
Policy Number: US151741
Policy End Date 30/11/2031`, 'vehicle-insurance');
assert.equal(insuranceHeadingBeforeProvisions.insurance_number, 'US151741');

const insurancePolicyProvisionsAfterRealNumber = parse(`Policy Number DEMO-MOTOR-2026-123456
Policy Provisions 14 Claim Provisions 15
Policy End Date 30/11/2031`, 'vehicle-insurance');
assert.equal(insurancePolicyProvisionsAfterRealNumber.insurance_number, 'DEMOMOTOR2026123456');

const shortPolicyBeforeProvisions = parse(`Policy Number: US151741
Policy Provisions14ClaimProvisions15
Policy End Date 30/11/2031`, 'vehicle-insurance');
assert.equal(shortPolicyBeforeProvisions.insurance_number, 'US151741');

const multilineInsurancePeriod = parse(`Policy No: MULTI12345
Period of Insurance
From 01/01/2026
To Midnight of
31/12/2026`, 'vehicle-insurance');
assert.equal(multilineInsurancePeriod.insurance_expiry_date, '31/12/2026');

const shortYearInsuranceExpiry = parse(`Policy No: SHORT12345
Expiry Date: 31-Dec-27`, 'vehicle-insurance');
assert.equal(shortYearInsuranceExpiry.insurance_expiry_date, '31/12/2027');

const insuranceDateFallback = parse(`Policy No: FALLBACK12345
Issued 05/01/2026
Coverage closes 04/01/2027`, 'vehicle-insurance');
assert.equal(insuranceDateFallback.insurance_expiry_date, '04/01/2027');

const monthFirstInsuranceDates = parse(`Policy Number: US151741
Policy Effective Date: August 1, 2013
Policy Expiration Date: August 1, 2014`, 'vehicle-insurance');
assert.equal(monthFirstInsuranceDates.insurance_number, 'US151741');
assert.equal(monthFirstInsuranceDates.insurance_expiry_date, '01/08/2014');

const pincodeFallbackAadhaar = parse(`Government of India
RAVI KUMAR
DOB: 01/01/1990
Address: House 12, Raje Bazar Road, Near School, 800014
2345 6789 0123`, 'aadhaar');
assert.equal(pincodeFallbackAadhaar.pincode, '800014');
assert.equal(pincodeFallbackAadhaar.state, 'Bihar');
assert.equal(pincodeFallbackAadhaar.city, 'Patna');

const aadhaarNameWithLowercaseNoise = parse(`Government of India
b Kriti Kumari
DOB: 01/01/1990
2345 6789 0123`, 'aadhaar');
assert.equal(aadhaarNameWithLowercaseNoise.father_name, 'Kriti Kumari');
assert.equal(aadhaarNameWithLowercaseNoise.child_name, 'Kriti Kumari');

const garbledAddressAadhaar = parse(`Government of India
KRITI KUMARI
DOB: 01/01/1990
Address: gar Address, STETIAT HAT THTT, ones Corony, Raje Bazar By., 252/170
fore wt T, sng i i, FIAT, TZ4T, fazTT - 800014
2345 6789 0123`, 'aadhaar');
assert.equal(garbledAddressAadhaar.address_1, '');
assert.equal(garbledAddressAadhaar.address_2, '');
assert.equal(garbledAddressAadhaar.address_requires_manual_review, true);
assert.equal(garbledAddressAadhaar.pincode, '800014');
assert.equal(garbledAddressAadhaar.state, 'Bihar');
assert.equal(garbledAddressAadhaar.city, 'Patna');

const hindiAddressAadhaar = parse(`\u092d\u093e\u0930\u0924 \u0938\u0930\u0915\u093e\u0930
\u0930\u0935\u093f \u0915\u0941\u092e\u093e\u0930
\u091c\u0928\u094d\u092e \u0924\u093f\u0925\u093f 01/01/1990
\u092a\u0924\u093e: \u092e\u0915\u093e\u0928 12, \u0930\u093e\u091c\u0947 \u092c\u093e\u091c\u093e\u0930 \u0930\u094b\u0921, \u092a\u091f\u0928\u093e 800014
2345 6789 0123`, 'aadhaar');
assert.equal(hindiAddressAadhaar.pincode, '800014');
assert.equal(hindiAddressAadhaar.state, 'Bihar');
assert.equal(hindiAddressAadhaar.city, 'Patna');
assert.notEqual(hindiAddressAadhaar.address_1, '');
