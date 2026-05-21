@echo off
echo Starting Laravel with ngrok tunnel and QR code...
echo.
echo This requires:
echo   - ngrok installed (https://ngrok.com/download)
echo   - PHP (for Laravel artisan serve)
echo.
timeout /t 2 /nobreak >nul

powershell -ExecutionPolicy Bypass -File "%~dp0start-with-qr.ps1"
pause
