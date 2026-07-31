$ErrorActionPreference = "Stop"
$Url = "http://localhost:8080"

if (Get-Command php -ErrorAction SilentlyContinue) {
    Write-Host "Starting AccessHub with PHP at $Url" -ForegroundColor Cyan
    Start-Process $Url
    php -S localhost:8080 -t public
    exit $LASTEXITCODE
}

if (Get-Command docker -ErrorAction SilentlyContinue) {
    Write-Host "Starting AccessHub with Docker at $Url" -ForegroundColor Cyan
    Start-Process $Url
    docker compose up --build
    exit $LASTEXITCODE
}

Write-Host "Neither PHP nor Docker was found." -ForegroundColor Red
Write-Host "Install PHP 8.2+ or Docker Desktop and run this script again."
Read-Host "Press Enter to close"
exit 1
