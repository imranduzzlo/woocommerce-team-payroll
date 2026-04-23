# GitHub Release Creation Script for WooCommerce Team Payroll
# Version: 1.7.8
# Uses GitHub API to automatically create releases

param(
    [string]$Version = "1.7.8"
)

# Configuration
$RepoOwner = "imranduzzlo"
$RepoName = "woocommerce-team-payroll"
$Branch = "main"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "GitHub Release Creator v1.7.8" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Read GitHub token from .kiro/github-token.txt
$TokenFile = ".kiro/github-token.txt"
if (-not (Test-Path $TokenFile)) {
    Write-Host "ERROR: GitHub token file not found at $TokenFile" -ForegroundColor Red
    exit 1
}

$Token = (Get-Content $TokenFile -Raw).Trim()
if ([string]::IsNullOrWhiteSpace($Token)) {
    Write-Host "ERROR: GitHub token is empty" -ForegroundColor Red
    exit 1
}

Write-Host "✓ GitHub token loaded" -ForegroundColor Green

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

Write-Host ""
Write-Host "Step 1: Checking current status..." -ForegroundColor Yellow
git status --short

Write-Host ""
Write-Host "Step 2: Adding all changes..." -ForegroundColor Yellow
git add .

Write-Host ""
Write-Host "Step 3: Creating commit..." -ForegroundColor Yellow
$commitMessage = "Release v$Version - Fix Leaderboard Earnings Calculation"
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
git tag -a "v$Version" -m "Version $Version" -f
git push origin "v$Version" -f

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to create or push tag" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Step 6: Reading release notes from CHANGELOG.md..." -ForegroundColor Yellow

# Extract release notes for this version from CHANGELOG.md
$changelogContent = Get-Content "CHANGELOG.md" -Raw
$versionPattern = "## \[$Version\].*?(?=## \[|$)"
$releaseNotes = ""

if ($changelogContent -match $versionPattern) {
    $releaseNotes = $Matches[0]
    Write-Host "✓ Release notes extracted" -ForegroundColor Green
} else {
    Write-Host "WARNING: Could not extract release notes for version $Version" -ForegroundColor Yellow
    $releaseNotes = "Release version $Version"
}

Write-Host ""
Write-Host "Step 7: Creating GitHub Release via API..." -ForegroundColor Yellow

# Prepare release data
$releaseData = @{
    tag_name = "v$Version"
    target_commitish = $Branch
    name = "Version $Version"
    body = $releaseNotes
    draft = $false
    prerelease = $false
} | ConvertTo-Json

# Create release via GitHub API
$headers = @{
    "Authorization" = "token $Token"
    "Accept" = "application/vnd.github.v3+json"
    "Content-Type" = "application/json"
}

$apiUrl = "https://api.github.com/repos/$RepoOwner/$RepoName/releases"

try {
    $response = Invoke-RestMethod -Uri $apiUrl -Method Post -Headers $headers -Body $releaseData
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "SUCCESS! Release v$Version created" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Release URL: $($response.html_url)" -ForegroundColor Cyan
    Write-Host ""
    
    # Open browser to the release
    Start-Process $response.html_url
    
} catch {
    Write-Host ""
    Write-Host "ERROR: Failed to create GitHub release" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "Response: $responseBody" -ForegroundColor Red
    }
    
    exit 1
}

Write-Host "Release created successfully!" -ForegroundColor Green
