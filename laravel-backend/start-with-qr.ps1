# PowerShell script to start Laravel with ngrok tunnel and QR code

# Check if ngrok is installed
$ngrokCheck = ngrok version 2>$null
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: ngrok is not installed!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Install ngrok:" -ForegroundColor Yellow
    Write-Host "  1. Download: https://ngrok.com/download"
    Write-Host "  2. Or: choco install ngrok  (if you have Chocolatey)"
    Write-Host "  3. Or: scoop install ngrok  (if you have Scoop)"
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    Laravel with ngrok + QR Code" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Start Laravel in background
Write-Host "Starting Laravel server on port 8000..." -ForegroundColor Green
Start-Process -NoNewWindow -FilePath "php" -ArgumentList "artisan", "serve"

Write-Host "Waiting for Laravel to start..." -ForegroundColor Yellow
Start-Sleep -Seconds 3

# Start ngrok in background and capture the tunnel URL
Write-Host "Starting ngrok tunnel..." -ForegroundColor Green
$ngrokProcess = Start-Process -NoNewWindow -PassThru -FilePath "C:\Users\ACER NITRO AN515-52\AppData\Roaming\npm\ngrok.cmd" -ArgumentList "http", "8000", "--log=stdout"

# Wait for ngrok to initialize
Start-Sleep -Seconds 2

# Get the public URL from ngrok API
$maxAttempts = 10
$attempt = 0
$publicUrl = $null

while ($attempt -lt $maxAttempts -and -not $publicUrl) {
    try {
        $response = Invoke-RestMethod -Uri "http://127.0.0.1:4040/api/tunnels" -ErrorAction SilentlyContinue
        if ($response.tunnels.Count -gt 0) {
            $publicUrl = $response.tunnels[0].public_url
        }
    } catch {
        # API not ready yet
    }
    
    if (-not $publicUrl) {
        Start-Sleep -Seconds 1
        $attempt++
    }
}

if (-not $publicUrl) {
    Write-Host "ERROR: Could not get ngrok tunnel URL" -ForegroundColor Red
    exit 1
}

# Remove http:// or https:// for cleaner QR code
$urlForQr = $publicUrl -replace "https?://", ""

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "  Your Laravel is now accessible online!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Public URL: $publicUrl" -ForegroundColor Cyan
Write-Host ""

# Generate QR code using online service and open in browser
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=$([System.Uri]::EscapeDataString($publicUrl))"

Write-Host "Generating QR Code..." -ForegroundColor Yellow
Write-Host ""
Write-Host "Display options:" -ForegroundColor Yellow
Write-Host "  1. Opening QR code in browser..." -ForegroundColor Gray
Start-Process $qrUrl

Write-Host ""
Write-Host "Share this URL with your phone:" -ForegroundColor Green
Write-Host $publicUrl -ForegroundColor Yellow
Write-Host ""
Write-Host "Or scan the QR code that just opened!" -ForegroundColor Green
Write-Host ""
Write-Host "Press Ctrl+C in ngrok window to stop all services" -ForegroundColor Gray
Write-Host ""

# Keep script running
Write-Host "Tunnel is running..." -ForegroundColor Cyan
while ($true) {
    Start-Sleep -Seconds 60
}
