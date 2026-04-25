# ============================================================================
# Create Manual Installation ZIP
# Creates a properly named ZIP file for manual WordPress installation
# ============================================================================

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host "  Creating Manual Installation ZIP" -ForegroundColor Cyan
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host ""

# Get version from plugin file
$pluginFile = "woocommerce-team-payroll.php"
if (-not (Test-Path $pluginFile)) {
    Write-Host "✗ Error: Plugin file not found: $pluginFile" -ForegroundColor Red
    exit 1
}

$content = Get-Content $pluginFile -Raw
if ($content -match 'Version:\s*([0-9.]+)') {
    $version = $matches[1]
    Write-Host "✓ Detected version: $version" -ForegroundColor Green
} else {
    Write-Host "✗ Error: Could not detect version from plugin file" -ForegroundColor Red
    exit 1
}

# Define paths
$tempDir = "temp_build"
$pluginDir = "woocommerce-team-payroll"
$zipName = "woocommerce-team-payroll-v$version-manual-install.zip"

Write-Host ""
Write-Host "Creating temporary build directory..." -ForegroundColor Yellow

# Clean up old temp directory if exists
if (Test-Path $tempDir) {
    Remove-Item -Path $tempDir -Recurse -Force
}

# Create temp directory structure
New-Item -ItemType Directory -Path "$tempDir\$pluginDir" -Force | Out-Null

Write-Host "✓ Temporary directory created" -ForegroundColor Green

Write-Host ""
Write-Host "Copying plugin files..." -ForegroundColor Yellow

# Files and folders to include
$includes = @(
    "assets",
    "includes",
    "languages",
    "woocommerce-team-payroll.php",
    "README.md",
    "CHANGELOG.md",
    "DOCUMENTATION.md"
)

# Files and folders to exclude
$excludes = @(
    ".git",
    ".gitignore",
    "node_modules",
    "*.ps1",
    "temp_build",
    ".vscode",
    ".idea"
)

foreach ($item in $includes) {
    if (Test-Path $item) {
        $destination = "$tempDir\$pluginDir\$item"
        if (Test-Path $item -PathType Container) {
            # Copy directory
            Copy-Item -Path $item -Destination $destination -Recurse -Force
            Write-Host "  ✓ Copied: $item/" -ForegroundColor Gray
        } else {
            # Copy file
            Copy-Item -Path $item -Destination $destination -Force
            Write-Host "  ✓ Copied: $item" -ForegroundColor Gray
        }
    }
}

Write-Host "✓ All files copied" -ForegroundColor Green

Write-Host ""
Write-Host "Creating ZIP file..." -ForegroundColor Yellow

# Remove old zip if exists
if (Test-Path $zipName) {
    Remove-Item -Path $zipName -Force
}

# Create ZIP file
Compress-Archive -Path "$tempDir\$pluginDir" -DestinationPath $zipName -Force

Write-Host "✓ ZIP file created: $zipName" -ForegroundColor Green

Write-Host ""
Write-Host "Cleaning up..." -ForegroundColor Yellow

# Clean up temp directory
Remove-Item -Path $tempDir -Recurse -Force

Write-Host "✓ Cleanup complete" -ForegroundColor Green

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║                                                            ║" -ForegroundColor Green
Write-Host "║            MANUAL INSTALLATION ZIP CREATED!                ║" -ForegroundColor Green
Write-Host "║                                                            ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "File Details:" -ForegroundColor Cyan
Write-Host "  Filename:     $zipName" -ForegroundColor White
Write-Host "  Version:      $version" -ForegroundColor White
Write-Host "  Folder Name:  $pluginDir (correct for WordPress)" -ForegroundColor White
Write-Host ""
Write-Host "Installation Instructions:" -ForegroundColor Cyan
Write-Host "  1. Upload this ZIP file to WordPress (Plugins > Add New > Upload)" -ForegroundColor White
Write-Host "  2. Click 'Install Now'" -ForegroundColor White
Write-Host "  3. Click 'Activate'" -ForegroundColor White
Write-Host "  4. No renaming needed - folder is already correctly named!" -ForegroundColor Green
Write-Host ""
Write-Host "✓ Ready for distribution!" -ForegroundColor Green
Write-Host ""
