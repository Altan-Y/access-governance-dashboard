@echo off
setlocal
cd /d "%~dp0"
set "URL=http://localhost:8080"

echo ==========================================
echo   AccessHub Portfolio Demo - Local Start
echo ==========================================
echo.

where php >nul 2>nul
if %errorlevel%==0 goto start_php

where docker >nul 2>nul
if %errorlevel%==0 goto start_docker

echo [FEHLER] Weder PHP noch Docker wurde gefunden.
echo.
echo Installiere eine der folgenden Optionen:
echo   1. PHP 8.2 oder neuer
echo  2. Docker Desktop
echo.
echo Danach diese Datei erneut doppelt anklicken.
echo.
pause
exit /b 1

:start_php
echo [OK] PHP wurde gefunden.
echo Starte AccessHub unter %URL%
start "" "%URL%"
php -S localhost:8080 -t public
set "EXITCODE=%errorlevel%"
echo.
echo Der Server wurde beendet. Fehlercode: %EXITCODE%
pause
exit /b %EXITCODE%

:start_docker
echo [OK] Docker wurde gefunden.
echo Starte AccessHub unter %URL%
start "" "%URL%"
docker compose up --build
set "EXITCODE=%errorlevel%"
echo.
echo Docker wurde beendet. Fehlercode: %EXITCODE%
pause
exit /b %EXITCODE%
