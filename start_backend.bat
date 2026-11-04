@echo off
title ResolveAI - Backend (PHP 8.3)
cd /d "%~dp0backend\public"
echo Starting PHP REST API Server at http://127.0.0.1:5000...
php -S 127.0.0.1:5000 router.php
pause
