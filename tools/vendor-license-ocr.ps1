$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$assetRoot = Join-Path $projectRoot 'public/vendor/license-ocr'
$cacheRoot = Join-Path $projectRoot 'storage/app/ocr-vendor-cache'
New-Item -ItemType Directory -Force -Path $assetRoot, $cacheRoot | Out-Null
$packages = @(
    @{ Name = 'tesseract.js'; Version = '7.0.0'; Target = 'tesseract'; Files = @('dist/tesseract.min.js', 'dist/worker.min.js', 'LICENSE*') },
    @{ Name = 'tesseract.js-core'; Version = '7.0.0'; Target = 'core'; Files = @('*.wasm.js', '*.wasm', 'LICENSE*') },
    @{ Name = 'pdfjs-dist'; Version = '6.3.289'; Target = 'pdf'; Files = @('build/pdf.min.mjs', 'build/pdf.worker.min.mjs', 'cmaps', 'standard_fonts', 'wasm', 'LICENSE') },
    @{ Name = '@tesseract.js-data/eng'; Version = '1.0.0'; Target = 'lang'; Files = @('4.0.0_best_int/eng.traineddata.gz', 'LICENSE*') }
)
foreach ($package in $packages) {
    $metadata = Invoke-RestMethod ('https://registry.npmjs.org/' + $package.Name + '/' + $package.Version)
    $packageDir = Join-Path $cacheRoot ($package.Target + '-' + $package.Version)
    New-Item -ItemType Directory -Force -Path $packageDir | Out-Null
    $archive = Join-Path $packageDir 'download.tgz'
    Invoke-WebRequest -UseBasicParsing -Uri $metadata.dist.tarball -OutFile $archive
    $sha = [System.Security.Cryptography.SHA512]::Create()
    $hash = 'sha512-' + [Convert]::ToBase64String($sha.ComputeHash([IO.File]::ReadAllBytes($archive)))
    $sha.Dispose()
    if ($hash -ne $metadata.dist.integrity) { throw ('Integrity mismatch: ' + $package.Name) }
    & tar -xzf $archive -C $packageDir
    if ($LASTEXITCODE -ne 0) { throw ('Extraction failed: ' + $package.Name) }
    $destination = Join-Path $assetRoot $package.Target
    New-Item -ItemType Directory -Force -Path $destination | Out-Null
    foreach ($file in $package.Files) {
        $source = Join-Path (Join-Path $packageDir 'package') $file
        Copy-Item -Path $source -Destination $destination -Recurse -Force
    }
    Write-Output ($package.Name + '@' + $package.Version + ' verified and copied')
}
Get-ChildItem -LiteralPath $assetRoot -Recurse -File | Get-FileHash -Algorithm SHA256 |
    Select-Object @{Name='File';Expression={$_.Path.Substring($assetRoot.Length + 1)}}, Hash |
    ConvertTo-Json | Set-Content -LiteralPath (Join-Path $assetRoot 'checksums.json') -Encoding UTF8
