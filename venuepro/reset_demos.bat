@echo off
title VenuePro - Reset & Regenerate Demo Data
color 0A
echo ============================================================
echo   VenuePro Enterprise Event Management
echo   Demo Data Regeneration Utility
echo ============================================================
echo.

:: Detect PHP executable
set PHP_BIN=php
where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    if exist "C:\xampp\php\php.exe" (
        set PHP_BIN="C:\xampp\php\php.exe"
    ) else (
        echo [ERROR] PHP executable not found in PATH or at C:\xampp\php\php.exe
        echo Please ensure XAMPP is installed or add PHP to your Windows PATH.
        echo.
        pause
        exit /b 1
    )
)

echo [INFO] Using PHP: %PHP_BIN%
echo [INFO] Connecting to MySQL and populating fresh demonstration data...
echo.

%PHP_BIN% "%~dp0database\seed_demos.php"

if %ERRORLEVEL% equ 0 (
    echo.
    echo ============================================================
    echo   SUCCESS: Demo data successfully regenerated!
    echo   You can now open http://localhost/ in your browser.
    echo ============================================================
) else (
    echo.
    echo [ERROR] Failed to regenerate demo data.
    echo Please ensure MySQL service is running in your XAMPP Control Panel.
)

echo.
pause
