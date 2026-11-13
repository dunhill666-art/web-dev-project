@echo off
title AeroGlide Ngrok Tunnel
echo ============================================================
echo   AeroGlide Aviation Platform - Ngrok Public Tunnel
echo ============================================================
echo.
echo Tunneling Docker web container (http://localhost:8080)...
echo Share the generated public HTTPS URL with your professor.
echo.
echo Press Ctrl+C in this terminal to terminate the tunnel.
echo ============================================================
echo.

where ngrok >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Ngrok is not found in your system PATH.
    echo Please install Ngrok from https://ngrok.com/download or run via Docker:
    echo   docker compose --profile tunnel up -d
    echo.
    pause
    exit /b 1
)

ngrok http 8080

