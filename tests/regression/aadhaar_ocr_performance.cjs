// Run with front/back image paths. Compares working-tree OCR with git HEAD.
// Personal field values are compared by hash, never printed or saved.
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const path = require('node:path');
const files = process.argv.slice(2);
assert.ok(files.length > 0, 'Pass at least one Aadhaar image path');
function run(baseline) {
    const result = spawnSync(process.execPath, [path.join(__dirname, 'aadhaar_real_replacement.cjs'), ...files], {
        cwd:path.resolve(__dirname, '../..'), encoding:'utf8', timeout:360000,
        env:{...process.env,TEST_TIMING:'1',TEST_BASELINE:baseline?'1':'0',TEST_FADED_HEADING:'0'}
    });
    assert.equal(result.status, 0, result.stderr || result.error?.message || 'Browser OCR failed');
    return result.stdout.split(/\r?\n/).filter(line => line.startsWith('{"side":')).map(line => JSON.parse(line));
}
const before = run(true);
console.log('Baseline OCR finished; comparing optimized field values');
const after = run(false);
assert.equal(after.length, before.length);
after.forEach((scan,index) => {
    assert.equal(scan.side,before[index].side);
    assert.deepEqual(scan.fieldHashes,before[index].fieldHashes,'Existing autofilled values must remain identical');
    assert.ok(scan.passes <= before[index].passes, 'Optimization must not add OCR passes for this image');
    console.log(`${scan.side}: ${before[index].seconds}s/${before[index].passes} passes -> ${scan.seconds}s/${scan.passes} passes; field values unchanged`);
});
