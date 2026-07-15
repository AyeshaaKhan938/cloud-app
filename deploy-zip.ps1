# Run from anywhere: powershell -ExecutionPolicy Bypass -File deploy-zip.ps1
# Creates C:\Users\BNC\Downloads\vms-cloud-deploy.tar.gz excluding vendor, node_modules, .git, and runtime caches.

$ErrorActionPreference = 'Stop'

$source     = 'C:\laragon\www\vms-cloud'
$output     = 'C:\Users\BNC\Downloads\vms-cloud-deploy.tar.gz'
$workingDir = 'C:\laragon\www'

if (-not (Test-Path $source)) {
    Write-Error "Source folder not found: $source"
    exit 1
}

# Build the exclude flags as separate arguments to dodge line-mangling issues.
$excludes = @(
    '--exclude=vms-cloud/vendor',
    '--exclude=vms-cloud/node_modules',
    '--exclude=vms-cloud/.git',
    '--exclude=vms-cloud/storage/logs',
    '--exclude=vms-cloud/storage/framework/cache',
    '--exclude=vms-cloud/storage/framework/sessions',
    '--exclude=vms-cloud/storage/framework/views'
)

Set-Location $workingDir

# --force-local stops tar from treating 'C:' as a remote host.
$tarArgs = @('--force-local', '-czf', $output) + $excludes + @('vms-cloud')

Write-Host 'Compressing... (this takes 30s-2min, no progress output is normal)'
& tar @tarArgs

if ($LASTEXITCODE -ne 0) {
    Write-Error "tar failed with exit code $LASTEXITCODE"
    exit $LASTEXITCODE
}

$info = Get-Item $output
$mb = [math]::Round($info.Length / 1MB, 1)
Write-Host ''
Write-Host "SUCCESS — created $output"
Write-Host "Size: $mb MB"
Write-Host ''
Write-Host 'Next: upload this file via cPanel File Manager to /home/cloudvmfs/'
