@echo off
title VenuePro - Regenerate All Demo Data
echo ============================================================
echo   VenuePro Demo Data Reset Utility
echo ============================================================
echo.

IF EXIST "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" "%~dp0seed_demos.php"
) ELSE (
    php "%~dp0seed_demos.php"
)

echo.
echo ============================================================
echo Press any key to exit...
pause >nul
