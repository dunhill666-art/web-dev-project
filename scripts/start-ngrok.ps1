# AeroGlide Aviation Platform - Ngrok Public Tunnel Launcher
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  AeroGlide Aviation Platform - Ngrok Public Tunnel" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Tunneling Docker web container (http://localhost:8080)..." -ForegroundColor Yellow
Write-Host "Share the generated public HTTPS URL with your professor." -ForegroundColor Green
Write-Host "Press Ctrl+C to terminate the tunnel." -ForegroundColor Gray
Write-Host ""

$ngrokCmd = Get-Command ngrok -ErrorAction SilentlyContinue
if (-not $ngrokCmd) {
    Write-Host "[ERROR] Ngrok is not found in your system PATH." -ForegroundColor Red
    Write-Host "Please install Ngrok or launch the dockerized tunnel:" -ForegroundColor White
    Write-Host "  docker compose --profile tunnel up -d" -ForegroundColor White
    exit 1
}

& ngrok http 8080

