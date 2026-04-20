# GitHub Release Creation Script for WooCommerce Team Payroll
# Version: 1.6.43

param(
    [string]$Version = "1.6.43",
    [string]$Token = ""
)

# Configuration
$RepoOwner = "imranduzzlo"
$RepoName = "pv-team-payroll"
$Branch = "main"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "GitHub Release Creator v1.6.43" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if git is available
if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "ERROR: Git is not installed or not in PATH" -ForegroundColor Red
    exit 1
}

# Check if we're in a git repository
if (-not (Test-Path ".git")) {
    Write-Host "ERROR: Not in a git repository" -ForegroundColor Red
    exit 1
}

Write-Host "Step 1: Checking current status..." -ForegroundColor Yellow
git status --short

Write-Host ""
Write-Host "Step 2: Adding all changes..." -ForegroundColor Yellow
git add .

Write-Host ""
Write-Host "Step 3: Creating commit..." -ForegroundColor Yellow
$commitMessage = "Release v$Version - Badge Stars & Cache Fix"
git commit -m $commitMessage

if ($LASTEXITCODE -ne 0) {
    Write-Host "WARNING: No changes to commit or commit failed" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Step 4: Pushing to GitHub..." -ForegroundColor Yellow
git push origin $Branch

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to push to GitHub" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Step 5: Creating Git Tag..." -ForegroundColor Yellow
git tag -a "v$Version" -m "Version $Version"
git push origin "v$Version"

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to create or push tag" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "SUCCESS! Release v$Version created" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Go to: https://github.com/$RepoOwner/$RepoName/releases/new" -ForegroundColor White
Write-Host "2. Select tag: v$Version" -ForegroundColor White
Write-Host "3. Title: Version $Version" -ForegroundColor White
Write-Host "4. Copy release notes from CHANGELOG.md" -ForegroundColor White
Write-Host "5. Click 'Publish release'" -ForegroundColor White
Write-Host ""

# Open browser to releases page
$releasesUrl = "https://github.com/$RepoOwner/$RepoName/releases/new?tag=v$Version"
Write-Host "Opening browser to create release..." -ForegroundColor Yellow
Start-Process $releasesUrl

Write-Host ""
Write-Host "Release notes for v${Version}:" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Get-Content CHANGELOG.md -Head 50
