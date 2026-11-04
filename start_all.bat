@echo off
title ResolveAI — Service Desk & Operations Platform Launcher
echo ========================================================
echo        Starting ResolveAI (Full Stack + AI)
echo ========================================================
echo.

cd /d "%~dp0"

echo [1/4] Ensuring ports 5000 and 5173 are free...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :5000') do taskkill /f /pid %%a >nul 2>&1
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :5173') do taskkill /f /pid %%a >nul 2>&1

echo [2/4] Starting PHP 8.3 Backend Server (Port 5000)...
start "ResolveAI - Backend (PHP 8.3)" cmd /k "cd backend\public && php -S 127.0.0.1:5000 router.php"

echo [3/4] Starting Vite Frontend Server (Port 5173)...
start "ResolveAI - Frontend (React/Vite)" cmd /k "cd frontend && npm run dev"

echo [4/4] Opening ResolveAI Operations Center in browser...
timeout /t 4 /nobreak >nul
start http://localhost:5173

echo.
echo ========================================================
echo ResolveAI is running!
echo Frontend: http://localhost:5173
echo Backend:  http://localhost:5000
echo.
echo Pre-configured Demo Accounts:
echo   ADMIN:
echo     Email:    admin@smartcampus.local
echo     Password: Admin@123
echo.
echo   DEPARTMENT STAFF:
echo     CSE:      cse.staff@smartcampus.local  (Password: Staff@123)
echo.
echo   STUDENT:
echo     Email:    student@smartcampus.local
echo     Password: Student@123
echo ========================================================
echo.
pause
