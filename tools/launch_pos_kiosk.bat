@echo off
REM Launches the POS checkout screen in a dedicated Chrome window with
REM --kiosk-printing, which sends window.print() straight to the default
REM printer with no print dialog. Uses a separate --user-data-dir so this
REM doesn't touch the cashier's normal Chrome profile/settings.
REM
REM To use: put a shortcut to this file on the POS till's desktop/startup
REM folder instead of opening the site in a regular Chrome window.

set "POS_URL=http://localhost/ShelfSense/public/?page=pos_checkout"
set "PROFILE_DIR=%LOCALAPPDATA%\ShelfSensePOSKiosk"

set "CHROME="
if exist "%ProgramFiles%\Google\Chrome\Application\chrome.exe" set "CHROME=%ProgramFiles%\Google\Chrome\Application\chrome.exe"
if exist "%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe" set "CHROME=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"

if "%CHROME%"=="" (
    echo Could not find chrome.exe. Edit this file and set CHROME to its path.
    pause
    exit /b 1
)

start "" "%CHROME%" --kiosk-printing --user-data-dir="%PROFILE_DIR%" --app="%POS_URL%"
