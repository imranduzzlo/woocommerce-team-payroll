# ============================================================================
# GitHub Release Creation Script for WooCommerce Team Payroll
# ============================================================================
# This script automates the complete release process:
# 1. Validates version numbers
# 2. Commits all changes
# 3. Pushes to GitHub
# 4. Creates and pushes tag
# 5. Creates GitHub release with changelog
#
# Usage:
#   .\create_release.ps1              # Auto-detect version from plugin file
#   .\create_release.ps1 -Version 1.0.1  # Specify version manually
# ============================================================================

param(
    [string]$Version = "",
    [switch]$SkipValidation = $false
)

# Colors for output
$ColorInfo = "Cyan"
$ColorSuccess = "Green"
$ColorWarning = "Yellow"
$ColorError = "Red"

# Configuration
$PluginFile = "woocommerce-team-payroll.php"
$ChangelogFile = "CHANGELOG.md"
$RepoOwner = "imranduzzlo"
$RepoName = "woocommerce-team-payroll"
$Branch = "main"
$TokenFile = ".kiro/github-token.txt"

# ============================================================================
# FUNCTIONS
# ============================================================================

function Write-Step {
    param([string]$Message)
    Write-Host ""
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor $ColorInfo
    Write-Host $Message -ForegroundColor $ColorInfo
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor $ColorInfo
}

function Write-Success {
    param([string]$Message)
    Write-Host "✓ $Message" -ForegroundColor $ColorSuccess
}

function Write-Error-Exit {
    param([string]$Message)
    Write-Host ""
    Write-Host "✗ ERROR: $Message" -ForegroundColor $ColorError
    exit 1
}

function Write-Warning-Message {
    param([string]$Message)
    Write-Host "⚠ WARNING: $Message" -ForegroundColor $ColorWarning
}

# ============================================================================
# HEADER
# ============================================================================

Clear-Host
Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor $ColorInfo
Write-Host "║                                                            ║" -ForegroundColor $ColorInfo
Write-Host "║     WooCommerce Team Payroll - Release Creator            ║" -ForegroundColor $ColorInfo
Write-Host "║                                                            ║" -ForegroundColor $ColorInfo
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor $ColorInfo
Write-Host ""

# ============================================================================
# STEP 0: PRE-FLIGHT CHECKS
# ============================================================================

Write-Step "Step 0: Pre-flight Checks"

# Check if git is available
if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Error-Exit "Git is not installed or not in PATH"
}
Write-Success "Git is available"

# Check if we're in a git repository
if (-not (Test-Path ".git")) {
    Write-Error-Exit "Not in a git repository"
}
Write-Success "Git repository detected"

# Check if plugin file exists
if (-not (Test-Path $PluginFile)) {
    Write-Error-Exit "Plugin file not found: $PluginFile"
}
Write-Success "Plugin file found"

# Check if changelog exists
if (-not (Test-Path $ChangelogFile)) {
    Write-Error-Exit "Changelog file not found: $ChangelogFile"
}
Write-Success "Changelog file found"

# Check if token file exists
if (-not (Test-Path $TokenFile)) {
    Write-Error-Exit "GitHub token file not found at $TokenFile"
}
Write-Success "GitHub token file found"

# Read and validate token
$Token = (Get-Content $TokenFile -Raw).Trim()
if ([string]::IsNullOrWhiteSpace($Token)) {
    Write-Error-Exit "GitHub token is empty"
}
Write-Success "GitHub token loaded"

# ============================================================================
# STEP 1: VERSION DETECTION & VALIDATION
# ============================================================================

Write-Step "Step 1: Version Detection & Validation"

# Auto-detect version from plugin file if not provided
if ([string]::IsNullOrWhiteSpace($Version)) {
    $content = Get-Content $PluginFile -Raw
    if ($content -match 'Version:\s*(\d+\.\d+\.\d+)') {
        $Version = $Matches[1]
        Write-Success "Auto-detected version: $Version"
    } else {
        Write-Error-Exit "Could not detect version from $PluginFile"
    }
} else {
    Write-Success "Using specified version: $Version"
}

# Validate version format
if ($Version -notmatch '^\d+\.\d+\.\d+$') {
    Write-Error-Exit "Invalid version format: $Version (must be X.Y.Z)"
}

# Check if version exists in both locations in plugin file
$content = Get-Content $PluginFile -Raw
$headerVersion = ""
$constantVersion = ""

if ($content -match 'Version:\s*(\d+\.\d+\.\d+)') {
    $headerVersion = $Matches[1]
}

if ($content -match "define\(\s*'WC_TEAM_PAYROLL_VERSION',\s*'(\d+\.\d+\.\d+)'\s*\)") {
    $constantVersion = $Matches[1]
}

if ($headerVersion -ne $Version -or $constantVersion -ne $Version) {
    Write-Warning-Message "Version mismatch detected!"
    Write-Host "  Header Version:   $headerVersion" -ForegroundColor $ColorWarning
    Write-Host "  Constant Version: $constantVersion" -ForegroundColor $ColorWarning
    Write-Host "  Expected Version: $Version" -ForegroundColor $ColorWarning
    
    if (-not $SkipValidation) {
        Write-Error-Exit "Version numbers don't match. Fix them or use -SkipValidation"
    }
}

Write-Success "Version validation passed: $Version"

# Check if version exists in changelog
$changelogContent = Get-Content $ChangelogFile -Raw
if ($changelogContent -notmatch "\[$Version\]") {
    Write-Warning-Message "Version $Version not found in $ChangelogFile"
    Write-Host "  Please add a changelog entry for this version" -ForegroundColor $ColorWarning
    
    if (-not $SkipValidation) {
        $continue = Read-Host "Continue anyway? (y/N)"
        if ($continue -ne "y" -and $continue -ne "Y") {
            Write-Error-Exit "Release cancelled by user"
        }
    }
}

Write-Success "Version validation passed: $Version"

# Check if version exists in changelog
$changelogContent = Get-Content $ChangelogFile -Raw
if ($changelogContent -notmatch "\[$Version\]") {
    Write-Warning-Message "Version $Version not found in $ChangelogFile"
    Write-Host "  Please add a changelog entry for this version" -ForegroundColor $ColorWarning
    
    if (-not $SkipValidation) {
        $continue = Read-Host "Continue anyway? (y/N)"
        if ($continue -ne "y" -and $continue -ne "Y") {
            Write-Error-Exit "Release cancelled by user"
        }
    }
}

# ============================================================================
# STEP 2: GIT STATUS CHECK
# ============================================================================

Write-Step "Step 2: Git Status Check"

# Check current branch
$currentBranch = git rev-parse --abbrev-ref HEAD
if ($currentBranch -ne $Branch) {
    Write-Warning-Message "Current branch is '$currentBranch', expected '$Branch'"
    $continue = Read-Host "Continue anyway? (y/N)"
    if ($continue -ne "y" -and $continue -ne "Y") {
        Write-Error-Exit "Release cancelled by user"
    }
} else {
    Write-Success "On correct branch: $Branch"
}

# Show current status
Write-Host ""
Write-Host "Current git status:" -ForegroundColor $ColorInfo
git status --short

# Check if there are changes
$hasChanges = git status --porcelain
if ([string]::IsNullOrWhiteSpace($hasChanges)) {
    Write-Warning-Message "No changes detected in working directory"
    $continue = Read-Host "Continue with release anyway? (y/N)"
    if ($continue -ne "y" -and $continue -ne "Y") {
        Write-Error-Exit "Release cancelled by user"
    }
} else {
    Write-Success "Changes detected and ready to commit"
}

# ============================================================================
# STEP 3: COMMIT CHANGES
# ============================================================================

Write-Step "Step 3: Committing Changes"

# Add all changes
Write-Host "Adding all changes..." -ForegroundColor $ColorInfo
git add .

if ($LASTEXITCODE -ne 0) {
    Write-Error-Exit "Failed to add changes"
}
Write-Success "All changes staged"

# Create commit message
$commitMessage = "Release v$Version"
if ($changelogContent -match "## \[$Version\][^\n]*\n### ([^\n]+)") {
    $commitMessage = "Release v$Version - $($Matches[1])"
}

Write-Host "Commit message: $commitMessage" -ForegroundColor $ColorInfo

# Commit changes
git commit -m $commitMessage

if ($LASTEXITCODE -eq 0) {
    Write-Success "Changes committed successfully"
} else {
    Write-Warning-Message "No changes to commit or commit failed"
}

# ============================================================================
# STEP 4: PUSH TO GITHUB
# ============================================================================

Write-Step "Step 4: Pushing to GitHub"

Write-Host "Pushing to origin/$Branch..." -ForegroundColor $ColorInfo
git push origin $Branch

if ($LASTEXITCODE -ne 0) {
    Write-Error-Exit "Failed to push to GitHub"
}
Write-Success "Pushed to GitHub successfully"

# ============================================================================
# STEP 5: CREATE AND PUSH TAG
# ============================================================================

Write-Step "Step 5: Creating Git Tag"

$tagName = "v$Version"

# Check if tag already exists
$existingTag = git tag -l $tagName
if ($existingTag) {
    Write-Warning-Message "Tag $tagName already exists"
    $continue = Read-Host "Delete and recreate tag? (y/N)"
    if ($continue -ne "y" -and $continue -ne "Y") {
        Write-Error-Exit "Release cancelled by user"
    }
    
    # Delete local and remote tag
    Write-Host "Deleting existing tag..." -ForegroundColor $ColorInfo
    git tag -d $tagName
    git push origin --delete $tagName 2>$null
}

# Create new tag
Write-Host "Creating tag: $tagName" -ForegroundColor $ColorInfo
git tag -a $tagName -m "Version $Version"

if ($LASTEXITCODE -ne 0) {
    Write-Error-Exit "Failed to create tag"
}
Write-Success "Tag created: $tagName"

# Push tag
Write-Host "Pushing tag to GitHub..." -ForegroundColor $ColorInfo
git push origin $tagName

if ($LASTEXITCODE -ne 0) {
    Write-Error-Exit "Failed to push tag"
}
Write-Success "Tag pushed to GitHub"

# ============================================================================
# STEP 6: EXTRACT RELEASE NOTES
# ============================================================================

Write-Step "Step 6: Extracting Release Notes"

# Extract release notes for this version from CHANGELOG.md
$versionPattern = "## \[$Version\].*?(?=## \[|$)"
$releaseNotes = ""

if ($changelogContent -match $versionPattern) {
    $releaseNotes = $Matches[0].Trim()
    Write-Success "Release notes extracted from changelog"
    Write-Host ""
    Write-Host "Preview:" -ForegroundColor $ColorInfo
    Write-Host "─────────────────────────────────────────" -ForegroundColor DarkGray
    Write-Host $releaseNotes.Substring(0, [Math]::Min(500, $releaseNotes.Length))
    if ($releaseNotes.Length -gt 500) {
        Write-Host "..." -ForegroundColor DarkGray
    }
    Write-Host "─────────────────────────────────────────" -ForegroundColor DarkGray
} else {
    Write-Warning-Message "Could not extract release notes for version $Version"
    $releaseNotes = "Release version $Version`n`nSee [CHANGELOG.md](https://github.com/$RepoOwner/$RepoName/blob/$Branch/CHANGELOG.md) for details."
}

# ============================================================================
# STEP 7: CREATE GITHUB RELEASE
# ============================================================================

Write-Step "Step 7: Creating GitHub Release"

# Prepare release data
$releaseData = @{
    tag_name = $tagName
    target_commitish = $Branch
    name = "Version $Version"
    body = $releaseNotes
    draft = $false
    prerelease = $false
} | ConvertTo-Json -Depth 10

# Create release via GitHub API
$headers = @{
    "Authorization" = "Bearer $Token"
    "Accept" = "application/vnd.github.v3+json"
    "Content-Type" = "application/json"
    "User-Agent" = "WC-Team-Payroll-Release-Script"
}

$apiUrl = "https://api.github.com/repos/$RepoOwner/$RepoName/releases"

Write-Host "Calling GitHub API..." -ForegroundColor $ColorInfo

try {
    $response = Invoke-RestMethod -Uri $apiUrl -Method Post -Headers $headers -Body $releaseData
    
    Write-Host ""
    Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor $ColorSuccess
    Write-Host "║                                                            ║" -ForegroundColor $ColorSuccess
    Write-Host "║                  RELEASE CREATED SUCCESSFULLY!             ║" -ForegroundColor $ColorSuccess
    Write-Host "║                                                            ║" -ForegroundColor $ColorSuccess
    Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor $ColorSuccess
    Write-Host ""
    Write-Host "Release Details:" -ForegroundColor $ColorInfo
    Write-Host "  Version:      $Version" -ForegroundColor White
    Write-Host "  Tag:          $tagName" -ForegroundColor White
    Write-Host "  Release URL:  $($response.html_url)" -ForegroundColor Cyan
    Write-Host "  Published:    $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor White
    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor $ColorInfo
    Write-Host "  1. WordPress sites will check for updates within 12 hours" -ForegroundColor White
    Write-Host "  2. Users can click 'Check Updates' for immediate check" -ForegroundColor White
    Write-Host "  3. Monitor GitHub for any issues or feedback" -ForegroundColor White
    Write-Host ""
    
    # Open browser to the release
    Write-Host "Opening release in browser..." -ForegroundColor $ColorInfo
    Start-Process $response.html_url
    
    Write-Host ""
    Write-Success "Release process completed successfully!"
    Write-Host ""
    
} catch {
    Write-Host ""
    Write-Host "✗ ERROR: Failed to create GitHub release" -ForegroundColor $ColorError
    Write-Host ""
    
    if ($_.Exception.Response) {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "HTTP Status Code: $statusCode" -ForegroundColor $ColorError
        
        try {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $responseBody = $reader.ReadToEnd() | ConvertFrom-Json
            Write-Host "Error Message: $($responseBody.message)" -ForegroundColor $ColorError
            
            if ($responseBody.errors) {
                Write-Host "Details:" -ForegroundColor $ColorError
                $responseBody.errors | ForEach-Object {
                    Write-Host "  - $($_.message)" -ForegroundColor $ColorError
                }
            }
        } catch {
            Write-Host "Error: $($_.Exception.Message)" -ForegroundColor $ColorError
        }
    } else {
        Write-Host "Error: $($_.Exception.Message)" -ForegroundColor $ColorError
    }
    
    Write-Host ""
    Write-Host "Troubleshooting:" -ForegroundColor $ColorWarning
    Write-Host "  1. Check if GitHub token has 'repo' permissions" -ForegroundColor White
    Write-Host "  2. Verify tag was pushed: git ls-remote --tags origin" -ForegroundColor White
    Write-Host "  3. Check if release already exists on GitHub" -ForegroundColor White
    Write-Host "  4. Try creating release manually on GitHub" -ForegroundColor White
    Write-Host ""
    
    exit 1
}

# ============================================================================
# CLEANUP & SUMMARY
# ============================================================================

Write-Host ""
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor $ColorSuccess
Write-Host "                    RELEASE SUMMARY                         " -ForegroundColor $ColorSuccess
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor $ColorSuccess
Write-Host ""
Write-Host "✓ Version:        $Version" -ForegroundColor $ColorSuccess
Write-Host "✓ Tag:            $tagName" -ForegroundColor $ColorSuccess
Write-Host "✓ Branch:         $Branch" -ForegroundColor $ColorSuccess
Write-Host "✓ Repository:     $RepoOwner/$RepoName" -ForegroundColor $ColorSuccess
Write-Host "✓ Status:         Published" -ForegroundColor $ColorSuccess
Write-Host ""
Write-Host "════════════════════════════════════════════════════════════" -ForegroundColor $ColorSuccess
Write-Host ""
Write-Host "Made with ❤️ by Imran" -ForegroundColor $ColorInfo
Write-Host ""
