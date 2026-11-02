const assert=require('node:assert/strict');
const {validateDocument}=require('../../public/js/driver-document-parser.js');
// Synthetic checksum-valid number and address; no identity images/data embedded.
const number='2345 6789 0124';
const back=(issuer='CRSEeIaNoAutonty of India',value=number,copies=3)=>`${issuer}\nAddress: House 12, Lake Road, Ahmedabad, Gujarat 380001\n${(value+'\n').repeat(copies)}`;
assert.equal(validateDocument(back(),'aadhaar','back').valid,true,'A bounded damaged issuer must be corroborated by address and checked number');
assert.equal(validateDocument(back('Autonty of India'),'aadhaar','back').valid,true);
assert.equal(validateDocument(back('Of India'),'aadhaar','back').valid,false,'Country alone does not identify Aadhaar');
assert.equal(validateDocument(back('Automotive of India'),'aadhaar','back').valid,false,'Unrelated AU words must not match');
assert.equal(validateDocument(back('Austerity of India'),'aadhaar','back').valid,false,'Similar unrelated wording must not match');
assert.equal(validateDocument(back('Autonty of Canada'),'aadhaar','back').valid,false);
assert.equal(validateDocument(back('Autonty of India','2345 6789 0123'),'aadhaar','back').valid,false,'Checksum must be valid for a damaged issuer');
assert.equal(validateDocument(back('Autonty of India',number,2),'aadhaar','back').valid,false,'At least three agreeing number readings are required');
assert.equal(validateDocument(back().replace('380001',''),'aadhaar','back').valid,false,'Pincode required');
assert.equal(validateDocument(back().replace('Address: House 12, Lake Road, Ahmedabad, Gujarat 380001',''),'aadhaar','back').valid,false,'Address required');
assert.equal(validateDocument(back(),'aadhaar','front').valid,false,'Back cannot verify as front');
assert.equal(validateDocument(back()+'\nDRIVING LICENCE','aadhaar','back').valid,false,'Other document types remain rejected');
console.log('Highlighted-back issuer requires checked repeated number/address; missing, unrelated and conflicting evidence rejected');
