# GitHub Release Creator for v1.6.41
# This script creates a GitHub release using the GitHub API

# Configuration
$owner = "imranduzzlo"
$repo = "woocommerce-team-payroll"
$tag = "v1.6.41"
$releaseTitle = "v1.6.41 - CSV, PDF, and Print Export for Reports"

# Release notes
$releaseNotes = @"
## 🎉 What's New

### ✨ Feature - CSV, PDF, and Print Export for Reports

Comprehensive export functionality for performance reports with professional formatting and multiple output formats.

## 📊 CSV Export

Download filtered report data as CSV with proper formatting:
- Summary section (employee, period, generated date)
- Summary statistics (total orders, order value, commission)
- Detailed commission history with all columns
- UTF-8 BOM for Excel compatibility
- Proper currency formatting

## 📄 PDF Export

Generate professional PDF reports with modern styling:
- Beautiful header with company branding
- Summary cards with key metrics
- Formatted commission history table
- Professional footer with disclaimer
- Client-side PDF generation using html2pdf.js
- Automatic download with proper filename

## 🖨️ Print Functionality

Print-friendly report view with optimized layout:
- Custom print stylesheet
- Hides filters and export buttons
- Proper page breaks for multi-page reports
- Professional header and footer
- Optimized for both color and B&W printing

## 🎯 User Experience Improvements

- Loading indicators during export
- User-friendly error messages
- Responsive design for all screen sizes
- Mobile-friendly export buttons

## 🔧 Technical Details

### Files Modified
- `includes/class-myaccount.php` - Enhanced export functions
- `assets/js/reports.js` - Export button handlers
- `assets/css/reports.css` - Print media queries

### Libraries Used
- html2pdf.js (v0.10.1) - Client-side HTML to PDF conversion
- Native PHP - CSV generation
- Native JavaScript - Print functionality

## 📋 How to Use

### CSV Export
1. Navigate to Reports page
2. Apply filters as needed
3. Click "CSV" button
4. File downloads automatically

### PDF Export
1. Navigate to Reports page
2. Apply filters as needed
3. Click "PDF" button
4. PDF opens in new window
5. Save or print from browser

### Print Report
1. Navigate to Reports page
2. Apply filters as needed
3. Click "Print" button
4. Print dialog opens
5. Configure print settings and print

## 🐛 Previous Bug Fixes (v1.6.37-v1.6.40)

- Fixed profile badge showing locked when achievements exist
- Removed old SVG badge styles and cleaned up CSS
- Updated My Account badges with modern 3D coin design
- Redesigned locked badge with Phosphor icons

## 📈 Performance

- Client-side PDF generation (no server load)
- Fast CSV export
- Optimized print rendering
- Minimal file sizes

## 🔐 Security

- Proper nonce verification for AJAX requests
- User capability checks
- Sanitized output
- XSS protection

---

**Release Date:** April 20, 2026  
**Status:** Stable  
**Compatibility:** WooCommerce 5.0+, WordPress 5.0+
"@

# Create the release payload
$payload = @{
    tag_name = $tag
    target_commitish = "main"
    name = $releaseTitle
    body = $releaseNotes
    draft = $false
    prerelease = $false
} | ConvertTo-Json

Write-Host "Creating GitHub Release: $releaseTitle"
Write-Host "Tag: $tag"
Write-Host ""
Write-Host "Release Notes:"
Write-Host $releaseNotes
Write-Host ""
Write-Host "To create the release, you need to:"
Write-Host "1. Go to: https://github.com/$owner/$repo/releases/new"
Write-Host "2. Select tag: $tag"
Write-Host "3. Title: $releaseTitle"
Write-Host "4. Copy the release notes above into the description"
Write-Host "5. Click 'Publish release'"
Write-Host ""
Write-Host "Or use GitHub CLI:"
Write-Host "gh release create $tag --title '$releaseTitle' --notes-file RELEASE_NOTES_v1.6.41.md"
