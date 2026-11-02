const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require.resolve('../../public/js/driver-document-ocr.js'),'utf8');
const code = source.slice(source.indexOf('    let standardSimdSupported;'),source.indexOf('    async function imageCanvas('));
for (const support of [true,false,'error']) {
    const context = {WebAssembly:{validate:()=>{if(support==='error')throw Error('Unsupported');return support;}}};
    vm.createContext(context);vm.runInContext(code,context);
    const expected = '/vendor/core/tesseract-core-' + (support===true?'simd-':'') + 'lstm.wasm.js';
    assert.equal(context.aadhaarCorePath('/vendor/'),expected);
    assert.equal(context.aadhaarCorePath('/vendor/'),expected,'Repeated selection must stay stable');
    assert.ok(!expected.includes('relaxed'));
}
console.log('Standard SIMD selected when supported; unsupported/error cases retain portable fallback');
